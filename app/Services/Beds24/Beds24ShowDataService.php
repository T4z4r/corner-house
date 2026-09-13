<?php

namespace App\Services\Beds24;

use App\Models\CalendarBlock;
use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\ChannelPricingSnapshot;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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
     * Store a pasted showdata.php pricing feed verbatim as a snapshot.
     *
     * The feed is session-gated, so the admin copies it from a browser signed
     * into Beds24 and pastes it here. The raw text is kept as-is; the daily
     * rows are parsed alongside only so the snapshot can be browsed.
     */
    public function importPasted(ChannelAccount $account, string $beds24RoomId, string $rawData, ?Room $room = null): ChannelPricingSnapshot
    {
        $rows = $this->parseRows($rawData);

        if ($rows === []) {
            throw new RuntimeException('No pricing rows were found in the pasted showdata output.');
        }

        $dates = array_column($rows, 'date');

        return ChannelPricingSnapshot::create([
            'channel_account_id' => $account->id,
            'room_id' => $room?->id,
            'external_room_id' => $beds24RoomId,
            'rate_code' => $this->firstRateCode($rows),
            'date_from' => min($dates),
            'date_to' => max($dates),
            'raw_data' => $rawData,
            'rows' => $rows,
            'open_days' => count(array_filter($rows, fn (array $row): bool => $row['closed'] === false)),
            'closed_days' => count(array_filter($rows, fn (array $row): bool => $row['closed'] === true)),
            'synced_at' => now(),
        ]);
    }

    /**
     * Parse the tab-separated showdata table (including the surrounding
     * browser dump heading lines and the repeated "Date ... Min Stay" header
     * rows) into per-day rows.
     *
     * @return array<int, array{date: string, inventory: ?int, rate_code: ?string, closed: bool, price: ?float, min_stay: ?int}>
     */
    private function parseRows(string $body): array
    {
        $rows = [];

        foreach (preg_split('/\R/', $body) as $line) {
            $line = trim($line);

            if ($line === '' || ! preg_match('/^([A-Za-z]{3}\s+\d{1,2}\s+[A-Za-z]{3}\s+\d{4})\t(.*)$/', $line, $matches)) {
                continue;
            }

            $date = $this->parseDate($matches[1]);
            if ($date === null) {
                continue;
            }

            $fields = preg_split('/\t/', $matches[2]) ?: [];
            $inventory = trim((string) ($fields[0] ?? ''));
            $rateCode = trim((string) ($fields[1] ?? ''));
            $closedFlag = strtolower(trim((string) ($fields[2] ?? '')));
            $price = trim((string) ($fields[3] ?? ''));
            $minStay = trim((string) ($fields[4] ?? ''));

            $isClosed = $closedFlag === 'closed'
                || (is_numeric($inventory) && (int) $inventory <= 0);

            $rows[$date] = [
                'date' => $date,
                'inventory' => is_numeric($inventory) ? (int) $inventory : null,
                'rate_code' => $rateCode !== '' ? $rateCode : null,
                'closed' => $isClosed,
                'price' => is_numeric($price) ? (float) $price : null,
                'min_stay' => is_numeric($minStay) ? (int) $minStay : null,
            ];
        }

        return array_values($rows);
    }

    private function firstRateCode(array $rows): ?string
    {
        foreach ($rows as $row) {
            if ($row['rate_code'] !== null) {
                return $row['rate_code'];
            }
        }

        return null;
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
