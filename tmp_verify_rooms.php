<?php

use App\Models\Room;

$rooms = Room::orderBy('sort_order')->get();
foreach ($rooms as $room) {
    echo "[{$room->sort_order}] {$room->name} ({$room->slug})\n";
    echo '  type: '.$room->type."\n";
    echo '  description: '.mb_substr($room->description, 0, 80)."...\n";
    echo '  features: '.implode(' | ', $room->features ?? [])."\n";
    echo '  property_id: '.$room->property_id."\n";
}
echo "\nTotal rooms: ".$rooms->count()."\n";
$orphaned = Room::whereNull('property_id')->count();
echo "Rooms without property: {$orphaned}\n";
