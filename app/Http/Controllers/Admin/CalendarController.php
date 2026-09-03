<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingHold;
use App\Models\CalendarBlock;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /**
     * Beds24-aligned calendar block types.
     *
     * @var array<string, string>
     */
    private const BLOCK_TYPES = [
        'availability' => 'Availability',
        'min_stay' => 'Min Stay',
        'max_stay' => 'Max Stay',
        'daily_price' => 'Daily Price',
        'fixed_prices' => 'Fixed Prices',
        'multiplier' => 'Multiplier',
        'manual' => 'Manual',
    ];

    /**
     * Backward-compatible aliases for older internal labels.
     *
     * @var array<string, string>
     */
    private const BLOCK_TYPE_ALIASES = [
        'owner' => 'availability',
        'maintenance' => 'manual',
        'seasonal' => 'daily_price',
        'rates' => 'daily_price',
        'restrictions' => 'min_stay',
    ];

    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $properties = Property::query()->where('status', 'active')->get();

        $selectedProperty = $request->query('property_id')
            ? Property::find($request->query('property_id'))
            : $properties->first();

        $rooms = $selectedProperty
            ? Room::where('property_id', $selectedProperty->id)->get()
            : collect();

        $selectedRoomId = $request->query('room_id');

        return view('admin.calendar', [
            'properties' => $properties,
            'selectedProperty' => $selectedProperty,
            'rooms' => $rooms,
            'selectedRoomId' => $selectedRoomId,
            'blockTypes' => $this->blockTypes(),
            'initialMonth' => $request->query('month', now()->format('Y-m')),
        ]);
    }

    /**
     * FullCalendar JSON event source.
     */
    public function events(Request $request): JsonResponse
    {
        $start = $request->query('start');
        $end = $request->query('end');
        $propertyId = $request->query('property_id');
        $roomId = $request->query('room_id');

        $events = collect();

        $reservations = Reservation::query()
            ->active()
            ->with(['room', 'guest'])
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->when($start, fn ($q) => $q->whereDate('check_out', '>=', $start))
            ->when($end, fn ($q) => $q->whereDate('check_in', '<=', $end))
            ->get();

        foreach ($reservations as $reservation) {
            $roomName = $reservation->room?->name ?? 'Unassigned';
            $guestName = $reservation->guest?->full_name ?? 'Guest';
            $events->push([
                'id' => 'res-'.$reservation->id,
                'title' => $guestName.' · '.$reservation->reference,
                'start' => $reservation->check_in->toDateString(),
                'end' => $reservation->check_out->copy()->addDay()->toDateString(),
                'className' => $this->reservationCalendarClass($reservation),
                'extendedProps' => [
                    'type' => 'reservation',
                    'status' => $reservation->status,
                    'amount' => $reservation->total_amount,
                    'room_id' => $reservation->room_id,
                    'room_name' => $roomName,
                    'guest_name' => $guestName,
                    'reference' => $reservation->reference,
                    'url' => route('admin.reservations.show', $reservation),
                ],
            ]);
        }

        $blocks = CalendarBlock::query()
            ->with('room')
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->when($start, fn ($q) => $q->whereDate('end_date', '>=', $start))
            ->when($end, fn ($q) => $q->whereDate('start_date', '<=', $end))
            ->get();

        $holds = BookingHold::query()
            ->active()
            ->with('room')
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->when($start, fn ($q) => $q->whereDate('check_out', '>=', $start))
            ->when($end, fn ($q) => $q->whereDate('check_in', '<=', $end))
            ->get();

        foreach ($holds as $hold) {
            $roomName = $hold->room?->name ?? 'Room';
            $events->push([
                'id' => 'hold-'.$hold->id,
                'title' => 'Hold · '.$roomName,
                'start' => $hold->check_in->toDateString(),
                'end' => $hold->check_out->toDateString(),
                'className' => 'fc-event--hold',
                'extendedProps' => ['type' => 'hold', 'room_id' => $hold->room_id, 'room_name' => $roomName],
            ]);
        }

        foreach ($blocks as $block) {
            $roomName = $block->room?->name ?? 'All rooms';
            $events->push([
                'id' => 'block-'.$block->id,
                'title' => $this->blockTitle($block),
                'start' => $block->start_date->toDateString(),
                'end' => $block->end_date->copy()->addDay()->toDateString(),
                'className' => $this->blockCalendarClass($block),
                'extendedProps' => [
                    'type' => 'block',
                    'block_id' => $block->id,
                    'block_type' => $block->type,
                    'block_title' => $block->title,
                    'block_value' => $block->value,
                    'block_min_stay' => $block->min_stay,
                    'block_max_stay' => $block->max_stay,
                    'block_active' => $block->is_active,
                    'room_id' => $block->room_id,
                    'room_name' => $roomName,
                ],
            ]);
        }

        return response()->json($events);
    }

    public function storeBlock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'title' => ['nullable', 'string'],
            'type' => ['required', 'in:availability,min_stay,max_stay,daily_price,fixed_prices,multiplier,manual,owner,maintenance,seasonal,rates,restrictions'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'min_stay' => ['nullable', 'integer', 'min:1'],
            'max_stay' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['type'] = $this->normalizeBlockType((string) $validated['type']);

        $block = CalendarBlock::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        $this->auditLogger->log('calendar.block_created', 'calendar', 'calendar_block', (string) $block->id, newValues: $validated);

        return response()->json(['ok' => true, 'block' => $block]);
    }

    public function updateBlock(Request $request, CalendarBlock $block): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['sometimes', 'exists:properties,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'title' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:availability,min_stay,max_stay,daily_price,fixed_prices,multiplier,manual,owner,maintenance,seasonal,rates,restrictions'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'min_stay' => ['nullable', 'integer', 'min:1'],
            'max_stay' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['type'])) {
            $validated['type'] = $this->normalizeBlockType((string) $validated['type']);
        }

        $block->update($validated);

        $this->auditLogger->log('calendar.block_updated', 'calendar', 'calendar_block', (string) $block->id, newValues: $validated);

        return response()->json(['ok' => true, 'block' => $block->fresh()]);
    }

    public function toggleBlock(CalendarBlock $block): JsonResponse
    {
        $block->update(['is_active' => ! $block->is_active]);

        $this->auditLogger->log('calendar.block_toggled', 'calendar', 'calendar_block', (string) $block->id, newValues: ['is_active' => $block->is_active]);

        return response()->json(['ok' => true, 'block' => $block->fresh()]);
    }

    public function destroyBlock(CalendarBlock $block): JsonResponse
    {
        $block->delete();

        $this->auditLogger->log('calendar.block_deleted', 'calendar', 'calendar_block', (string) $block->id);

        return response()->json(['ok' => true]);
    }

    private function reservationCalendarClass(Reservation $reservation): string
    {
        return match ($reservation->status) {
            'confirmed' => 'fc-event--confirmed',
            'pending' => 'fc-event--pending',
            'hold' => 'fc-event--hold',
            'checked_in' => 'fc-event--checked-in',
            default => 'fc-event--default',
        };
    }

    private function blockCalendarClass(CalendarBlock $block): string
    {
        return match ($this->normalizeBlockType($block->type)) {
            'availability' => 'fc-event--block fc-event--block-availability',
            'min_stay', 'max_stay' => 'fc-event--block fc-event--block-restrictions',
            'daily_price', 'fixed_prices', 'multiplier' => 'fc-event--block fc-event--block-rates',
            default => 'fc-event--block fc-event--block-manual',
        };
    }

    /**
     * @return array<string, string>
     */
    private function blockTypes(): array
    {
        return self::BLOCK_TYPES;
    }

    /**
     * @return array<string, string>
     */
    public static function blockTypesPublic(): array
    {
        return self::BLOCK_TYPES;
    }

    private function normalizeBlockType(string $type): string
    {
        return self::BLOCK_TYPE_ALIASES[$type] ?? $type;
    }

    private function blockTitle(CalendarBlock $block): string
    {
        $type = $this->normalizeBlockType($block->type);
        $label = self::BLOCK_TYPES[$type] ?? ucfirst($type);
        $roomLabel = $block->room ? ' · '.$block->room->name : '';

        if ($block->title) {
            return $block->title.$roomLabel;
        }

        return match ($type) {
            'daily_price', 'fixed_prices' => $block->value ? $label.': £'.number_format($block->value, 2).$roomLabel : $label.$roomLabel,
            'multiplier' => $block->value ? $label.': '.$block->value.'x'.$roomLabel : $label.$roomLabel,
            'min_stay' => $block->min_stay ? $label.': '.$block->min_stay.' nights'.$roomLabel : $label.$roomLabel,
            'max_stay' => $block->max_stay ? $label.': '.$block->max_stay.' nights'.$roomLabel : $label.$roomLabel,
            'availability' => $label.($block->is_active ? ' (Open)' : ' (Closed)').$roomLabel,
            default => $label.$roomLabel,
        };
    }
}
