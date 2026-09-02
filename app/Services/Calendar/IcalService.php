<?php

namespace App\Services\Calendar;

use App\Models\CalendarBlock;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Carbon;

class IcalService
{
    public function exportForRoom(Room $room): string
    {
        $from = now()->subYear()->startOfMonth();
        $to = now()->addYear()->endOfMonth();

        $entries = array_merge(
            $this->reservationEvents($room, $from, $to),
            $this->blockEvents($room, $from, $to),
        );

        usort($entries, static fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return $this->buildCalendar($entries);
    }

    /**
     * @param  array<int, array{uid: string, dtstart: string, dtend: string, summary: string, description: string, sort: string}>  $entries
     */
    private function buildCalendar(array $entries): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Corner House//Calendar Feed//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Corner House',
            'X-WR-TIMEZONE:Europe/London',
        ];

        foreach ($entries as $entry) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.$entry['uid'];
            $lines[] = 'DTSTAMP:'.now()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;VALUE=DATE:'.$entry['dtstart'];
            $lines[] = 'DTEND;VALUE=DATE:'.$entry['dtend'];
            $lines[] = 'SUMMARY:'.self::escapeText($entry['summary']);
            if ($entry['description'] !== '') {
                $lines[] = 'DESCRIPTION:'.self::escapeText($entry['description']);
            }
            $lines[] = 'TRANSP:OPAQUE';
            $lines[] = 'STATUS:CONFIRMED';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * @return array<int, array{uid: string, dtstart: string, dtend: string, summary: string, description: string, sort: string}>
     */
    private function reservationEvents(Room $room, Carbon $from, Carbon $to): array
    {
        $reservations = Reservation::query()
            ->active()
            ->where('room_id', $room->id)
            ->whereDate('check_out', '>=', $from->toDateString())
            ->whereDate('check_in', '<=', $to->toDateString())
            ->with('guest')
            ->get();

        $events = [];

        foreach ($reservations as $reservation) {
            $checkIn = $reservation->check_in instanceof Carbon
                ? $reservation->check_in
                : Carbon::parse($reservation->check_in);
            $checkOut = $reservation->check_out instanceof Carbon
                ? $reservation->check_out
                : Carbon::parse($reservation->check_out);

            $guestName = trim(implode(' ', array_filter([
                $reservation->guest?->first_name,
                $reservation->guest?->last_name,
            ])));

            $summary = $reservation->reference;
            if ($guestName !== '') {
                $summary .= ' - '.$guestName;
            }

            $description = sprintf(
                "Status: %s\nGuests: %d\nRoom: %s",
                ucfirst($reservation->status),
                $reservation->guests_count,
                $room->name,
            );

            $events[] = [
                'uid' => 'res-'.$reservation->id.'@corner-house',
                'dtstart' => $checkIn->format('Ymd'),
                'dtend' => $checkOut->copy()->addDay()->format('Ymd'),
                'summary' => $summary,
                'description' => $description,
                'sort' => $checkIn->format('Ymd').'0',
            ];
        }

        return $events;
    }

    /**
     * @return array<int, array{uid: string, dtstart: string, dtend: string, summary: string, description: string, sort: string}>
     */
    private function blockEvents(Room $room, Carbon $from, Carbon $to): array
    {
        $blocks = CalendarBlock::query()
            ->blockingInventory()
            ->where(function ($query) use ($room): void {
                $query->where('room_id', $room->id)
                    ->orWhere(fn ($q) => $q->whereNull('room_id')->where('property_id', $room->property_id));
            })
            ->whereDate('end_date', '>=', $from->toDateString())
            ->whereDate('start_date', '<=', $to->toDateString())
            ->get();

        $events = [];

        foreach ($blocks as $block) {
            $startDate = $block->start_date instanceof Carbon
                ? $block->start_date
                : Carbon::parse($block->start_date);
            $endDate = $block->end_date instanceof Carbon
                ? $block->end_date
                : Carbon::parse($block->end_date);

            $summary = $block->title ?? 'Not available';
            $description = sprintf("Type: %s\nRoom: %s", ucfirst($block->type), $room->name);

            $events[] = [
                'uid' => 'block-'.$block->id.'@corner-house',
                'dtstart' => $startDate->format('Ymd'),
                'dtend' => $endDate->copy()->addDay()->format('Ymd'),
                'summary' => $summary,
                'description' => $description,
                'sort' => $startDate->format('Ymd').'1',
            ];
        }

        return $events;
    }

    private static function escapeText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace("\n", "\\n", $text);
        $text = str_replace("\r", '', $text);

        return $text;
    }
}
