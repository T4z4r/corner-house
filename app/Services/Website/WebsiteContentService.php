<?php

namespace App\Services\Website;

use App\Models\Amenity;
use App\Models\PlacesOfInterest;
use App\Models\Property;
use App\Models\Review;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;

class WebsiteContentService
{
    /**
     * Build the full data set used across the public website.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $property = Property::query()->where('status', 'active')->first();

        $rooms = $property
            ? Room::query()->where('property_id', $property->id)->where('status', 'active')->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        $rooms->each(function (Room $room): void {
            if (! $room->relationLoaded('images')) {
                $room->load('images');
            }
        });

        $facilityAmenities = $property?->amenities ?? Amenity::query()->where('is_active', true)->orderBy('name')->get();

        return [
            'property' => $property,
            'rooms' => $rooms,
            'amenities' => $this->amenityNames($facilityAmenities),
            'placesFood' => $this->places('Food & drink'),
            'placesDays' => $this->places('Days out'),
            'walks' => $this->walks(),
            'spacesInside' => $this->spaces('website_spaces_inside', $this->defaultInside()),
            'spacesOutside' => $this->spaces('website_spaces_outside', $this->defaultOutside()),
            'heroFacts' => $this->heroFacts($property, $rooms->count()),
            'reviews' => $this->reviews(),
            'logo' => Setting::getValue('website_logo'),
            'footer_logo' => Setting::getValue('website_footer_logo') ?: Setting::getValue('website_logo'),
            'favicon' => Setting::getValue('website_favicon'),
            'og_image' => Setting::getValue('website_og_image'),
            'footer_address' => Setting::getValue('website_address', 'Braunston, Northamptonshire'),
            'footer_capacity_note' => Setting::getValue('website_footer_capacity', 'Sleeps 12 adults and 2 children in five ensuite bedrooms.'),
            'contact_email' => Setting::getValue('website_contact_email', 'bookings@example.com'),
            'platforms' => $this->platforms(),
            'spirits_website' => Setting::getValue('spirits_website', 'https://www.serengetispirits.com'),
            'video_url' => Setting::getValue('website_video_url'),
            'bookingRules' => $this->rules(Setting::getValue('website_booking_rules'), $this->defaultBookingRules()),
            'houseRules' => $this->rules(Setting::getValue('website_house_rules'), $this->defaultHouseRules()),
            'config' => $this->config(),
        ];
    }

    /**
     * JSON handed to the browser for the availability calculator and enquiry form.
     *
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $availability = Setting::getValue('website_blocked_dates', []) ?: [];

        return [
            'enquiryEmail' => Setting::getValue('website_contact_email', 'bookings@example.com'),
            'bookingEndpoint' => Route::has('booking.enquiry') ? route('booking.enquiry') : '',
            'availabilityUrl' => Route::has('booking.availability') ? route('booking.availability') : '',
            'nightlyRate' => (int) Setting::getValue('nightly_rate', 950),
            'securityDeposit' => (int) Setting::getValue('damage_deposit', Setting::getValue('nightly_rate', 950)),
            'cleaningFee' => (int) Setting::getValue('cleaning_fee', 250),
            'minNights' => (int) Setting::getValue('min_stay_nights', 2),
            'monthsAhead' => (int) Setting::getValue('website_months_ahead', 18),
            'sampleBlocked' => is_array($availability) ? array_values($availability) : [],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Amenity>  $amenities
     * @return array<int, string>
     */
    private function amenityNames($amenities): array
    {
        $names = $amenities->pluck('name')->filter()->values()->all();

        return $names ?: [
            'Five ensuite bedrooms',
            'Hot tub on the patio',
            'Cinema room in the cellar',
            'Games room',
            'Garden bar and Kadai BBQ',
            'Fully equipped gym',
            'Hard-wired office',
            'Garden room and first-floor balcony',
            'Landscaped garden',
            'Private gated parking for 6 cars',
            'Sky TV in every bedroom',
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function heroFacts(?Property $property, int $roomCount): array
    {
        $reviewStats = $this->reviewStats();

        return [
            ['value' => (string) Setting::getValue('hero_bedrooms', $roomCount ?: ($property?->bedrooms ?? 5)), 'label' => 'ensuite bedrooms'],
            ['value' => (string) Setting::getValue('hero_guests', $property?->capacity ?? '12'), 'label' => 'adults + 2 children'],
            ['value' => (string) Setting::getValue('hero_square_feet', '4,000'), 'label' => 'square feet'],
            ['value' => (string) Setting::getValue('hero_kitchen', '25 ft'), 'label' => 'centrepiece kitchen'],
            ['value' => (string) Setting::getValue('hero_built', '1850'), 'label' => 'the year it was built'],
            ['value' => (string) number_format($reviewStats['score'], 2), 'label' => sprintf('average from %d %s', $reviewStats['count'], $reviewStats['count'] === 1 ? 'Airbnb review' : 'Airbnb reviews')],
        ];
    }

    /**
     * Places for a tab, or the full active list when no matching category exists.
     *
     * @return Collection<int, PlacesOfInterest>
     */
    private function places(string $category): Collection
    {
        $query = PlacesOfInterest::query()->where('is_active', true)->orderBy('sort_order');

        if (in_array(strtolower($category), ['food & drink', 'days out'], true)) {
            $query->where('category', $category);
        }

        return $query->get();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function walks(): array
    {
        $raw = Setting::getValue('website_walking_routes', []);

        if (is_array($raw) && $raw !== []) {
            return array_values($raw);
        }

        return [
            [
                'name' => 'The Braunston lock flight and tunnel',
                'description' => 'From the marina follow the Grand Union towpath east past the six locks to the mouth of Braunston Tunnel. The Admiral Nelson at Lock 3 is the natural half-way stop. Return the same way, or cross at the tunnel and come back over the fields.',
                'distance' => 'About 4 miles',
                'time' => '1½–2 hours',
                'terrain' => 'Flat towpath, can be muddy',
                'stop' => 'Admiral Nelson',
            ],
            [
                'name' => 'Willoughby circular',
                'description' => 'Out along the Oxford Canal towpath towards Willoughby, then back across the fields and the old Roman road through the village. Quiet lanes and open country.',
                'distance' => 'About 5 miles',
                'time' => '2–2½ hours',
                'terrain' => 'Towpath and field paths',
                'stop' => 'Willoughby village',
            ],
            [
                'name' => 'Ashby St Ledgers and the Gunpowder Plot',
                'description' => 'Across the fields to Ashby St Ledgers, where the conspirators met in the manor gatehouse in 1605. The village has a thatched street and the Manor House.',
                'distance' => 'About 6 miles',
                'time' => '2½–3 hours',
                'terrain' => 'Field paths, some stiles',
                'stop' => 'Ashby St Ledgers',
            ],
        ];
    }

    /**
     * Approved reviews for the marquee. Each entry: ['stars', 'quote', 'cite'].
     *
     * @return array<int, array{stars: int, quote: string, cite: ?string}>
     */
    private function reviews(): array
    {
        return Review::approved()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(static fn (Review $review): array => [
                'stars' => $review->stars,
                'quote' => $review->quote,
                'cite' => $review->cite,
            ])
            ->all();
    }

    /**
     * @return array{count: int, score: ?float}
     */
    private function reviewStats(): array
    {
        $reviews = Review::approved()->get(['stars']);

        if ($reviews->isEmpty()) {
            return ['count' => (int) Setting::getValue('review_count', 0), 'score' => (float) Setting::getValue('review_score', 4.95)];
        }

        return [
            'count' => $reviews->count(),
            'score' => round($reviews->avg('stars'), 2),
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $defaults
     * @return array<int, array<string, string>>
     */
    private function spaces(string $key, array $defaults): array
    {
        $raw = Setting::getValue($key, []);

        return is_array($raw) && $raw !== [] ? array_values($raw) : $defaults;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function defaultInside(): array
    {
        return [
            ['name' => 'The kitchen', 'where' => 'Ground floor', 'description' => 'The 25-foot centrepiece of the house, and the reason it works so well for a full party. Everyone ends up here, so there is room for everyone to be here.', 'label' => 'The kitchen photo', 'feature' => '1'],
            ['name' => 'Garden dining room', 'where' => 'Ground floor, the old orangery', 'description' => 'The orangery, converted into a dining room that seats ten at a handmade farmhouse table and chairs, with the garden on three sides.', 'label' => 'Garden dining room photo'],
            ['name' => 'Lounge', 'where' => 'Ground floor', 'description' => 'The lounge is a calm space on the ground floor with seating, a fireplace and a television, flowing into the kitchen and the garden dining room.', 'label' => 'Lounge photo'],
            ['name' => 'Games room', 'where' => 'Ground floor', 'description' => 'A dedicated room for the games, with a pool table, darts, board games and a console.', 'label' => 'Games room photo'],
            ['name' => 'Cinema room', 'where' => 'The converted cellar', 'description' => 'The cellar has been converted into a cinema room with a projector screen and comfortable seating for the whole party.', 'label' => 'Cinema room photo'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function defaultOutside(): array
    {
        return [
            ['name' => 'Entertaining patio and garden bar', 'where' => 'The garden', 'description' => 'The patio is where the house spills out on a warm evening: the garden bar, the Kadai fire-pit barbecue and the hot tub, with the landscaped garden beyond.', 'label' => 'Entertaining patio and garden bar photo', 'feature' => '1'],
            ['name' => 'Hot tub', 'where' => 'On the patio', 'description' => 'Sits on the patio, a few steps from the garden bar, and is available between 8:00am and 11:00pm.', 'label' => 'Hot tub photo'],
            ['name' => 'Garden room', 'where' => 'The garden', 'description' => 'A quiet spot in the garden to sit out of the weather, whatever the season.', 'label' => 'Garden room photo'],
            ['name' => 'Balcony', 'where' => 'First floor, off the Lion suite', 'description' => 'A large balcony over the garden and the entertaining patio, reached through the double doors in the Lion suite. Good for a first coffee of the day.', 'label' => 'Balcony photo'],
            ['name' => 'Gym', 'where' => 'The grounds', 'description' => 'Fully equipped in its own building in the grounds, with cardio, weights and racks. Over-16s only.', 'label' => 'Gym photo'],
            ['name' => 'Office', 'where' => 'The grounds', 'description' => 'A purpose-built, hard-wired office in the grounds, with a desk and fast broadband for remote working.', 'label' => 'Office photo'],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function platforms(): array
    {
        return [
            'airbnb' => Setting::getValue('platform_airbnb_url'),
            'booking' => Setting::getValue('platform_booking_url'),
            'vrbo' => Setting::getValue('platform_vrbo_url'),
        ];
    }

    /**
     * @return array<int, array{title: string, items: array<int, string>}>
     */
    /**
     * Coerce a stored rules blob into the array shape the templates expect.
     *
     * A setting whose JSON was accidentally double-encoded (or otherwise stored
     * as a plain string) json_decodes to a string here; returning a rule list
     * built from a string or a malformed array would break the @foreach in the
     * booking partial, so fall back to the defaults for that section.
     *
     * @param  array<int, mixed>  $defaults
     * @return array<int, mixed>
     */
    private function rules(mixed $value, array $defaults): array
    {
        if (! is_array($value) || $value === []) {
            return $defaults;
        }

        return $value;
    }

    private function defaultBookingRules(): array
    {
        return [
            [
                'title' => 'Who can book',
                'items' => [
                    'The lead guest must be 21 or over and must stay at the property for the whole booking.',
                    'The booking is for the whole house only. We do not let individual rooms.',
                    'Bookings made directly with us are 10% cheaper than the same dates on Airbnb, Booking.com or Vrbo.',
                    'Overnight occupancy is capped at 12 adults and 2 children. This is a fire and insurance limit and cannot be exceeded.',
                    'Additional guests are welcome during the day for an event or gathering. Please tell us the expected numbers when you book.',
                    'We ask for a full guest list, with names and ages of any children, before arrival.',
                ],
            ],
            [
                'title' => 'Length of stay',
                'items' => [
                    'Minimum stay of 2 nights.',
                    'Minimum of 3 nights over bank holiday weekends, and 3 nights over Christmas and New Year.',
                    'Check-in from 3:00pm. Check-out by 12:00 noon.',
                    'Earlier check-in or later check-out may be possible if the house is free either side. Please ask; it is never guaranteed.',
                ],
            ],
            [
                'title' => 'Paying',
                'items' => [
                    'A non-refundable booking fee of 30% of the accommodation cost is payable when you book. This confirms the dates.',
                    'The balance is due 28 days before arrival. For bookings made within 28 days of arrival, the full amount is payable at the time of booking.',
                    'Payment by bank transfer or card. We do not accept cash.',
                    'Prices include the cleaning fee, linen, towels and utilities.',
                ],
            ],
            [
                'title' => 'Security deposit',
                'items' => [
                    'A refundable security deposit of &pound;950 is payable 7 days before arrival, by bank transfer or a card pre-authorisation.',
                    'It is returned in full within 7 days of departure, provided the house is left as found, there is no damage beyond fair wear and tear, and no rules have been broken.',
                    'We will always send you photographs and a written explanation of any deduction before it is made.',
                    'The deposit is a contribution, not a cap. If damage exceeds &pound;950, the lead guest remains liable for the balance.',
                ],
            ],
            [
                'title' => 'Agreement and identification',
                'items' => [
                    'Direct bookings require a short written rental agreement, signed by the lead guest before arrival.',
                    'We ask the lead guest for one form of photo identification (passport or driving licence) and proof of home address dated within the last three months.',
                    'We use this only to confirm you are who you say you are. It is stored securely, is not shared with anyone, and is deleted within 30 days of your departure unless there is an open damage claim.',
                    'We may decline a booking, without giving a reason, if identification is not provided.',
                ],
            ],
            [
                'title' => 'Changes and cancellations',
                'items' => [
                    'Cancellations follow our refund policy, which mirrors Airbnb&rsquo;s Moderate policy.',
                    'Date changes are treated as a cancellation and a new booking, though we will always try to move you if we can re-let the dates.',
                    'We strongly recommend travel insurance that covers cancellation.',
                    '<a href="#refunds">Read the full refund policy</a>',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, items: array<int, string>, flag?: bool}>
     */
    private function defaultHouseRules(): array
    {
        return [
            [
                'title' => 'Events and gatherings',
                'items' => [
                    'The house is built for entertaining and we are happy for you to hold an event or function here. Please tell us what you are planning when you book.',
                    'Additional guests may join you during the day. Overnight numbers are strictly capped at 12 adults and 2 children.',
                    'Amplified music, sound systems and DJs are fine within the quiet hours below.',
                    'For a larger event, please talk to us first about parking, numbers and anything you plan to bring in.',
                ],
            ],
            [
                'title' => 'No smoking or vaping',
                'flag' => true,
                'items' => [
                    'No smoking or vaping anywhere inside the house or the garden buildings.',
                    'Smoking is permitted outdoors on the patio only. Please use the ashtray provided.',
                    'Evidence of smoking indoors results in a deduction from the security deposit to cover specialist cleaning.',
                    'No candles, incense, fireworks, Chinese lanterns or open flames anywhere on the property.',
                ],
            ],
            [
                'title' => 'Gym',
                'items' => [
                    'A gym waiver is displayed on the gym door and left on the table with the keys. Please read it before anyone uses the equipment.',
                    'The lead guest accepts the waiver on behalf of the party as part of the booking, and is responsible for making sure everyone has read it.',
                    'Over-16s only. Children must not enter the gym at any time.',
                    'Use the equipment at your own risk, and never alone: always have someone else present.',
                    'Do not use the gym after drinking alcohol.',
                ],
            ],
            [
                'title' => 'Hot tub',
                'items' => [
                    'Available from 8:00am to 11:00pm, in line with the quiet hours. It is close to neighbouring homes, so please keep noise down.',
                    'No glass on or near the patio. Plastic drinkware is provided.',
                    'Please shower before use, and do not use it after drinking heavily.',
                    'Children must be supervised by an adult at all times. Not suitable for anyone who is pregnant or has a heart condition without medical advice.',
                    'Do not adjust the chemical dosing or the controls.',
                ],
            ],
            [
                'title' => 'Noise and neighbours',
                'flag' => true,
                'items' => [
                    'Music and noise are fine between 8:00am and 11:00pm.',
                    'From 11:00pm to 8:00am, please move indoors, close doors and windows, turn music off and keep the garden and patio clear.',
                    'This is a residential village and our neighbours are close by. The 11:00pm limit is the one rule we cannot be flexible about.',
                    'Please park in the gated parking only, which takes up to six cars, rather than on Old Road.',
                ],
            ],
            [
                'title' => 'The house and grounds',
                'items' => [
                    'The garage and its outbuildings are private working areas and are not part of the let. No access at any time.',
                    'Please use the Kadai barbecue only in the position provided, and never under cover or close to the building.',
                    'No pets.',
                    'Please report any damage or breakage to us during your stay rather than after it. Accidents happen and we would much rather know.',
                    'Please leave the house tidy, with rubbish and recycling in the bins outside and furniture back where you found it. You do not need to clean &mdash; that is what the cleaning fee is for.',
                ],
            ],
        ];
    }
}
