<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Models\Amenity;
use App\Models\FoodAndDrink;
use App\Models\KnowledgeBaseArticle;
use App\Models\PlacesOfInterest;
use App\Models\Property;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Area\AreaIntelligenceService;
use App\Services\Availability\AvailabilityService;
use App\Services\System\MailConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function home(): View
    {
        return view('website.home', $this->propertyData());
    }

    public function about(): View
    {
        return view('website.about', $this->propertyData());
    }

    public function property(): View
    {
        return view('website.property', $this->propertyData());
    }

    public function room(Room $room): View
    {
        abort_unless($room->isActive(), 404);

        $room->load(['images', 'property.amenities', 'property.policies']);

        $data = $this->propertyData();
        $data['room'] = $room;
        $data['siblingRooms'] = Room::query()
            ->where('id', '!=', $room->id)
            ->where('status', 'active')
            ->with('images')
            ->get();

        return view('website.room', $data);
    }

    public function amenities(): View
    {
        return view('website.amenities', $this->propertyData());
    }

    public function gallery(): View
    {
        return view('website.gallery', $this->propertyData());
    }

    public function location(AreaIntelligenceService $areaIntelligence): View
    {
        return view('website.location', $this->propertyData());
    }

    public function areaGuide(Request $request, AreaIntelligenceService $areaIntelligence): View
    {
        $data = $this->propertyData();
        $period = $request->string('period')->lower()->toString();
        $period = in_array($period, ['week', 'month'], true) ? $period : 'month';
        try {
            $anchorDate = $request->filled('date')
                ? Carbon::parse($request->string('date')->toString())
                : now();
        } catch (\Throwable) {
            $anchorDate = now();
        }
        $windowStart = $period === 'week'
            ? $anchorDate->copy()->startOfWeek(Carbon::MONDAY)
            : $anchorDate->copy()->startOfMonth();
        $windowEnd = $period === 'week'
            ? $anchorDate->copy()->endOfWeek(Carbon::SUNDAY)
            : $anchorDate->copy()->endOfMonth();

        $data['weatherForecast'] = $areaIntelligence->weatherForecast($data['property']);
        $data['localEvents'] = $areaIntelligence->nearbyEvents($data['property'], $windowStart, $windowEnd);
        $data['selectedPeriod'] = $period;
        $data['anchorDate'] = $anchorDate;
        $data['windowLabel'] = $period === 'week'
            ? sprintf('Week of %s', $windowStart->format('d M Y'))
            : $windowStart->format('F Y');

        return view('website.area-guide', $data);
    }

    public function faq(): View
    {
        $data = $this->propertyData();
        $data['articles'] = KnowledgeBaseArticle::query()
            ->where('status', 'active')
            ->where('show_on_website', true)
            ->orderByDesc('priority')
            ->orderBy('title')
            ->get()
            ->groupBy('category');

        return view('website.faq', $data);
    }

    public function foodAndDrink(): View
    {
        $data = $this->propertyData();
        $data['establishments'] = FoodAndDrink::query()->where('is_active', true)->orderBy('sort_order')->get();
        $data['addons'] = AddOn::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('website.food-drink', $data);
    }

    public function places(): View
    {
        $data = $this->propertyData();
        $data['places'] = PlacesOfInterest::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('website.places', $data);
    }

    public function contact(): View
    {
        return view('website.contact', $this->propertyData());
    }

    public function submitContact(Request $request, MailConfigurationService $mailConfigurationService): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $mailConfigurationService->apply();

        Mail::raw(
            "From: {$data['name']} <{$data['email']}>\n\n{$data['message']}",
            function ($message) use ($data): void {
                $message->to(config('mail.from.address'))
                    ->subject('Website enquiry from '.$data['name']);
            },
        );

        return back()->with('status', 'Thank you. We will get back to you shortly.');
    }

    /**
     * Accept a booking enquiry from the single-page enquiry form.
     *
     * @return JsonResponse
     */
    public function enquiry(Request $request, MailConfigurationService $mailConfigurationService)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:60'],
            'guests' => ['nullable', 'string', 'max:60'],
            'checkIn' => ['nullable', 'date'],
            'checkOut' => ['nullable', 'date'],
            'nights' => ['nullable', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:5000'],
            'drinksPackage' => ['nullable', 'boolean'],
            'acceptedTerms' => ['nullable', 'boolean'],
        ]);

        $mailConfigurationService->apply();

        $lines = [
            'New booking enquiry from the website',
            '---',
            "Name: {$data['name']}",
            "Email: {$data['email']}",
            $data['phone'] ? "Phone: {$data['phone']}" : '',
            $data['guests'] ? "Guests: {$data['guests']}" : '',
            $data['checkIn'] ? "Check in: {$data['checkIn']}" : '',
            $data['checkOut'] ? "Check out: {$data['checkOut']}" : '',
            $data['nights'] ? "Nights: {$data['nights']}" : '',
            ($data['drinksPackage'] ?? false) ? 'Drinks package: requested' : '',
            ($data['acceptedTerms'] ?? false) ? 'Terms and house rules: accepted' : '',
            '---',
            $data['message'] ?: 'No message.',
        ];

        Mail::raw(
            implode("\n", array_filter($lines)),
            function ($message): void {
                $message->to(Setting::getValue('website_contact_email', config('mail.from.address')))
                    ->subject('Booking enquiry');
            },
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Return the blocked-date ranges for the booking widget.
     *
     * The front end expects an array of {start, end} objects where "end" is
     * exclusive, so each blocked night is one range. Blocked nights come
     * from reservations, holds, calendar blocks and the website
     * blocked-dates setting for the active property.
     *
     * @return JsonResponse
     */
    public function availability(Request $request)
    {
        $propertyId = Property::query()->where('status', 'active')->value('id');

        return response()->json($this->availability->websiteBlockedRanges($propertyId ? (int) $propertyId : null));
    }

    public function privacy(): View
    {
        return view('website.legal', ['title' => 'Privacy Policy', 'heading' => 'Privacy Policy'] + $this->propertyData());
    }

    public function terms(): View
    {
        return view('website.legal', ['title' => 'Terms', 'heading' => 'Terms of Stay'] + $this->propertyData());
    }

    public function cancellation(): View
    {
        return view('website.legal', ['title' => 'Cancellation Policy', 'heading' => 'Cancellation Policy'] + $this->propertyData());
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyData(): array
    {
        $property = Property::query()->where('status', 'active')->with(['amenities', 'policies', 'rooms.images'])->first();

        return [
            'property' => $property,
            'rooms' => $property?->rooms ?? collect(),
            'amenities' => $property?->amenities ?? Amenity::query()->orderBy('name')->get(),
        ];
    }
}
