<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Calendar\IcalService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class IcalController extends Controller
{
    public function __construct(private readonly IcalService $ical) {}

    public function __invoke(Room $room): Response
    {
        $ics = $this->ical->exportForRoom($room);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="corner-house-'.e(Str::slug($room->name)).'.ics"',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
