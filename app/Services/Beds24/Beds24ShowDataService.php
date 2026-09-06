<?php

namespace App\Services\Beds24;

use App\Models\CalendarBlock;
use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Beds24ShowDataService
{
    private const DEFAULT_BASE_URL = 'https://beds24.com/api/booking.com/showdata.php';

    /**
     * Import closed (unavailable) nights from the public Booking.com showdata
     * feed into CalendarBlock (type "channel") rows so the website availability
     * calendar reflects live Beds24 availability.
     *
     * @return array{ranges: int, nights: int, rooms: int}
     */
    public function import(ChannelAccount $account, ?Room $directRoom = null, ?string $directBeds24RoomId = null, ?string $baseUrl = null): array
    {
        if ($directRoom instanceof Room && $directBeds24RoomId !== null) {
            $result = $this->importRoom($directRoom, $directBeds24RoomId, $baseUrl);

            return [
                'ranges' => $result['ranges'],
                'nights' => $result['nights'],
                'rooms' => 1,
            ];
        }

        $mappings = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('provider', 'beds24')
            ->whereNotNull('room_id')
            ->whereNotNull('external_room_id')
            ->with('room')
            ->get();

        $totalRanges = 0;
        $totalNights = 0;
        $rooms = 0;

        foreach ($mappings as $mapping) {
            $room = $mapping->room;
            if (! $room instanceof Room) {
                continue;
            }

            try {
                $result = $this->importRoom($room, (string) $mapping->external_room_id);
            } catch (\Throwable $e) {
                Log::warning('Beds24 showdata import failed for room', [
                    'room_id' => $room->id,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            $totalRanges += $result['ranges'];
            $totalNights += $result['nights'];
            $rooms++;
        }

        return ['ranges' => $totalRanges, 'nights' => $totalNights, 'rooms' => $rooms];
    }

    /**
     * Import closed nights for a single room from its showdata feed.
     *
     * @return array{ranges: int, nights: int}
     */
    public function importRoom(Room $room, string $beds24RoomId, ?string $baseUrl = null): array
    {
        $payload = $this->fetchShowData($beds24RoomId, $baseUrl);
        $closed = $this->closedNights($payload);

        CalendarBlock::query()
            ->where('room_id', $room->id)
            ->where('type', 'channel')
            ->where('notes', 'beds24-showdata')
            ->delete();

        $ranges = 0;
        foreach ($this->collapseDates($closed) as $range) {
            CalendarBlock::create([
                'property_id' => $room->property_id,
                'room_id' => $room->id,
                'start_date' => $range['start'],
                'end_date' => $range['end'],
                'type' => 'channel',
                'title' => 'Beds24 closed',
                'notes' => 'beds24-showdata',
            ]);
            $ranges++;
        }

        return ['ranges' => $ranges, 'nights' => count($closed)];
    }

    /**
     * Fetch the raw showdata text for a Beds24 room.
     */
    private function fetchShowData(string $beds24RoomId, ?string $baseUrl = null): string
    {
        $url = ($baseUrl ?? self::DEFAULT_BASE_URL).'?roomid='.urlencode($beds24RoomId);

        $response = Http::timeout(30)
            ->withHeaders(['Accept' => 'text/plain, */*'])
            ->get($url);

        $response->throw();

        return $response->body();
    }

    /**
     * Parse the tab-separated showdata table and return the dates that are
     * unavailable to the website. A night counts as closed when its inventory
     * is zero or its "Closed" column is "closed".
     *
     * @return array<int, string> Y-m-d date strings
     */
    private function closedNights(string $body): array
    {
        $closed = [];

        foreach (preg_split('/\R/', $body) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $date = $this->parseDate(trim($line));
            if ($date === null) {
                continue;
            }

            if ($this->isClosedRow($line)) {
                $closed[] = $date;
            }
        }

        $closed = array_values(array_unique($closed));
        sort($closed);

        return $closed;
    }

    private function parseDate(string $line): ?string
    {
        if (! preg_match('/^([A-Za-z]{3}\s+\d{1,2}\s+[A-Za-z]{3}\s+\d{4})/', $line, $matches)) {
            return null;
        }

        try {
            return Carbon::parse($matches[1])->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function isClosedRow(string $line): bool
    {
        $fields = preg_split('/\t/', $line) ?: [];

        $inventory = $fields[1] ?? '';
        $closedFlag = $fields[3] ?? '';

        if (strtolower(trim((string) $closedFlag)) === 'closed') {
            return true;
        }

        if (is_numeric(trim((string) $inventory))) {
            return (int) trim((string) $inventory) <= 0;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $dates
     * @return array<int, array{start: string, end: string}>
     */
    private function collapseDates(array $dates): array
    {
        $ranges = [];
        $start = null;
        $prev = null;

        foreach ($dates as $date) {
            if ($start === null) {
                $start = $date;
                $prev = $date;

                continue;
            }

            if (Carbon::parse($prev)->addDay()->toDateString() === $date) {
                $prev = $date;

                continue;
            }

            $ranges[] = ['start' => $start, 'end' => $prev];
            $start = $date;
            $prev = $date;
        }

        if ($start !== null) {
            $ranges[] = ['start' => $start, 'end' => $prev];
        }

        return $ranges;
    }
}
