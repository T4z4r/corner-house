<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            ['stars' => 5, 'quote' => 'Corner House was the perfect base for our family gathering. The kitchen really is the heart of it — we all ended up around that table, and the cinema room was a huge hit with the kids.', 'cite' => 'The Hamiltons, July 2026', 'status' => Review::STATUS_APPROVED, 'sort_order' => 1],
            ['stars' => 5, 'quote' => 'Beautiful house, beautifully kept. The hot tub after a long walk to Ashby St Ledgers was exactly what we needed.', 'cite' => 'Sophie and Tom, June 2026', 'status' => Review::STATUS_APPROVED, 'sort_order' => 2],
            ['stars' => 5, 'quote' => 'The garden and entertaining patio are even better than the photos. We cooked on the fire-pit both nights and barely wanted to leave.', 'cite' => 'Mark, May 2026', 'status' => Review::STATUS_APPROVED, 'sort_order' => 3],
            ['stars' => 4, 'quote' => 'Great location for the Marina and a proper kitchen for cooking for twelve. The office in the grounds was a lifesaver for a mid-week call.', 'cite' => 'Priya, April 2026', 'status' => Review::STATUS_APPROVED, 'sort_order' => 4],
            ['stars' => 5, 'quote' => 'We organised a birthday weekend here and the whole party was looked after brilliantly. The rooms are huge and each one having its own bathroom is a treat.', 'cite' => 'James, March 2026', 'status' => Review::STATUS_APPROVED, 'sort_order' => 5],
            ['stars' => 5, 'quote' => 'Faultless. The gym is better equipped than most hotels, and the welcome on arrival was warm and easy. We will be back.', 'cite' => 'Rachel, February 2026', 'status' => Review::STATUS_APPROVED, 'sort_order' => 6],
            ['stars' => 5, 'quote' => 'A proper country house for a full house. Everyone who stayed wants to come again — the balcony off the Lion suite at sunrise is magic.', 'cite' => 'The O\'Briens, January 2026', 'status' => Review::STATUS_APPROVED, 'sort_order' => 7],
            ['stars' => 4, 'quote' => 'Lovely stay at the Heart of the Waterways. The Gongoozlers Rest for breakfast and a walk along the towpath were highlights.', 'cite' => 'Daniel, December 2025', 'status' => Review::STATUS_APPROVED, 'sort_order' => 8],
            ['stars' => 5, 'quote' => 'We stayed over New Year and the games room, cinema room and garden bar kept a big mixed group entertained for days.', 'cite' => 'Anna, January 2026', 'status' => Review::STATUS_APPROVED, 'sort_order' => 9],
            ['stars' => 5, 'quote' => 'Booked the Serengeti Spirits tasting on site and it made the weekend. Great house, great hosts, great gin.', 'cite' => 'Ben, November 2025', 'status' => Review::STATUS_APPROVED, 'sort_order' => 10],
            ['stars' => 5, 'quote' => 'Everything was exactly as described and the beds were wonderfully comfortable. A perfect retreat from the city.', 'cite' => 'Charlotte, October 2025', 'status' => Review::STATUS_APPROVED, 'sort_order' => 11],
        ];

        foreach ($reviews as $review) {
            Review::updateOrCreate(
                ['quote' => $review['quote']],
                $review + ['source' => 'manual', 'source_id' => null],
            );
        }
    }
}
