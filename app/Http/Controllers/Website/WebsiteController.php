<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewEnquiryNotificationJob;
use App\Models\AddOn;
use App\Models\Amenity;
use App\Models\Enquiry;
use App\Models\FoodAndDrink;
use App\Models\KnowledgeBaseArticle;
use App\Models\PlacesOfInterest;
use App\Models\Property;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Area\AreaIntelligenceService;
use App\Services\Availability\AvailabilityService;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Throwable;

class WebsiteController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly NotificationService $notifications,
    ) {}

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
        } catch (Throwable) {
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

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /control-hub-q91x',
            'Disallow: /account',
            'Disallow: /register',
            'Disallow: /book/pay',
            'Disallow: /book/confirmation',
            'Disallow: /book/room',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }

    public function sitemap(): Response
    {
        $staticRoutes = [
            'home', 'about', 'property', 'amenities', 'gallery', 'location',
            'area-guide', 'faq', 'food-drink', 'places', 'contact',
            'privacy', 'terms', 'cancellation-policy', 'booking.search',
        ];

        $urls = collect($staticRoutes)->map(fn (string $name) => [
            'loc' => route($name),
            'lastmod' => now()->toAtomString(),
        ]);

        $rooms = Room::query()->where('status', 'active')->get(['id', 'updated_at']);
        $urls = $urls->concat($rooms->map(fn (Room $room) => [
            'loc' => route('property.room', $room),
            'lastmod' => $room->updated_at?->toAtomString() ?? now()->toAtomString(),
        ]));

        $xml = view('website.sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $enquiry = Enquiry::create([
            'type' => Enquiry::TYPE_CONTACT,
            'name' => $data['name'],
            'email' => $data['email'],
            'message' => $data['message'],
        ]);

        SendNewEnquiryNotificationJob::dispatch($enquiry->id);

        $this->notifications->sendEnquiryAcknowledgement($enquiry);

        return back()->with('status', 'Thank you. We will get back to you shortly.');
    }

    /**
     * Accept a booking enquiry from the single-page enquiry form.
     *
     * @return JsonResponse
     */
    public function enquiry(Request $request)
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

        $enquiry = Enquiry::create([
            'type' => Enquiry::TYPE_BOOKING,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'guests' => $data['guests'] ?? null,
            'check_in' => $data['checkIn'] ?? null,
            'check_out' => $data['checkOut'] ?? null,
            'nights' => $data['nights'] ?? null,
            'message' => $data['message'] ?? null,
            'drinks_package' => (bool) ($data['drinksPackage'] ?? false),
            'terms_accepted' => (bool) ($data['acceptedTerms'] ?? false),
        ]);

        SendNewEnquiryNotificationJob::dispatch($enquiry->id);

        $this->notifications->sendEnquiryAcknowledgement($enquiry);

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
        $primaryRoom = Room::defaultForDirectBookings();
        $propertyId = $primaryRoom?->property_id ?? Property::query()->where('status', 'active')->value('id');

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
