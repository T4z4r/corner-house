<?php

namespace App\Services\Website;

use App\Models\Amenity;
use App\Models\PlacesOfInterest;
use App\Models\Property;
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
            'bookingRules' => Setting::getValue('website_booking_rules', $this->defaultBookingRules()),
            'houseRules' => Setting::getValue('website_house_rules', $this->defaultHouseRules()),
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
        $reviewCount = (int) Setting::getValue('review_count', 40);

        return [
            ['value' => (string) Setting::getValue('hero_bedrooms', $roomCount ?: ($property?->bedrooms ?? 5)), 'label' => 'ensuite bedrooms'],
            ['value' => (string) Setting::getValue('hero_guests', $property?->capacity ?? '12'), 'label' => 'adults + 2 children'],
            ['value' => (string) Setting::getValue('hero_square_feet', '4,000'), 'label' => 'square feet'],
            ['value' => (string) Setting::getValue('hero_kitchen', '25 ft'), 'label' => 'centrepiece kitchen'],
            ['value' => (string) Setting::getValue('hero_built', '1850'), 'label' => 'the year it was built'],
            ['value' => (string) Setting::getValue('review_score', '4.95'), 'label' => sprintf('average from %d %s', $reviewCount, $reviewCount === 1 ? 'Airbnb review' : 'Airbnb reviews')],
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
     * Review quotes for the marquee. Each entry: ['stars', 'quote', 'cite'].
     *
     * @return array<int, array{stars: int, quote: string, cite: string}>
     */
    private function reviews(): array
    {
        $raw = Setting::getValue('website_reviews', []);

        if (is_array($raw) && $raw !== []) {
            return array_values($raw);
        }

        return array_map(function (): array {
            return ['stars' => 5, 'quote' => 'Paste a real guest review here.', 'cite' => 'Guest name, month year'];
        }, range(1, 11));
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
    private function defaultBookingRules(): array
    {
        return [
            [
                'title' => 'Pricing and payment',
                'items' => [
                    'The whole house is let to one party at a time, for a minimum of 2 nights.',
                    'The nightly rate is &pound;950 low season, &pound;1,100 shoulder and &pound;1,350 high season, plus a &pound;250 cleaning fee and a refundable security deposit.',
                    'A non-refundable deposit of 25% secures the dates, with the balance due 8 weeks before arrival.',
                ],
            ],
            [
                'title' => 'Dates and changes',
                'items' => [
                    'Check-in from 5:00pm, check-out by 10:00am on the day of departure.',
                    'Changes to a confirmed booking are subject to availability and may carry a fee.',
                    'We hold dates for 7 days while an enquiry or deposit is outstanding.',
                ],
            ],
            [
                'title' => 'Security deposit',
                'items' => [
                    'A refundable security deposit is collected with the balance and returned within 7 days of departure, less any deductions for damage or breakages.',
                    'The hot tub, gym and garden bar are included in the rate; extra charges apply for event furniture hire and some add-ons.',
                ],
            ],
            [
                'title' => 'Cancellation',
                'items' => [
                    'See our <a href="#refunds">refund policy</a> for the amounts payable if you cancel.',
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
                'title' => 'To keep you and the house safe',
                'items' => [
                    'No unaccompanied children. The hot tub, gym, canal and Marina are not supervised, and the garden pond is deep.',
                    'The hot tub is used at your own risk. No alcohol or glass on the poolside. Over-12s only for the hot tub; over-16s only for the gym.',
                ],
            ],
            [
                'title' => 'To keep neighbours happy',
                'items' => [
                    'Be courteous to our neighbours, especially during garden social evenings and late arrivals. Noise carries in the village.',
                ],
            ],
            [
                'title' => 'To keep the house in good order',
                'items' => [
                    'No smoking or vaping anywhere in the house, outbuilding or on the grounds. A &pound;200 cleaning charge applies for smoking indoors or the use of a vape.',
                    'Up to 12 adults plus 2 children, in five bedrooms. Different occupancy requires prior written agreement.',
                    'Only use a barbecue in the designated Kadai and garden bar, well clear of the house and outbuildings. Never leave a hot barbecue unattended.',
                    'The cinema room and games room are in the cellar; take care on the cellar stairs.',
                ],
            ],
            [
                'title' => 'And finally',
                'flag' => true,
                'items' => [
                    'If we think you are breaking the house rules, we will talk to you, of course. But persistent or serious breaches of the rules are not tolerated and may result in the termination of your booking without refund.',
                ],
            ],
        ];
    }
}
