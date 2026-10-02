<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * Buildings (with the colors from the EMO's schedule sheet) and the venues used
 * most often. The EMO adds new venues from the event form as they come up.
 */
class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $buildings = [
            'UHALL' => '#A4C2F4',
            'SOM' => '#B6D7A8',
            'Main' => '#FFE599',
            'IS BLDG' => '#EA9999',
            'NEU Library' => '#DD7E6B',
            'PSB' => '#D5A6BD',
            'Outdoor areas' => '#FFD966',
        ];

        foreach ($buildings as $name => $color) {
            Building::create(['name' => $name, 'color' => $color]);
        }

        $venues = [
            'University Hall' => 'UHALL',
            'PSB MPH' => 'PSB',
            'PSB Building' => 'PSB',
            'IS MPH' => 'IS BLDG',
            'IS Building' => 'IS BLDG',
            'IS Covered Court' => 'IS BLDG',
            'IS Library' => 'IS BLDG',
            'SOM MPH' => 'SOM',
            'SOM Building' => 'SOM',
            'Main Library' => 'NEU Library',
            'Main Building' => 'Main',
            '2nd Floor Lobby' => 'Main',
            'NEU Open Field' => 'Outdoor areas',
            'NEU Covered Court' => 'Outdoor areas',
        ];

        foreach ($venues as $name => $building) {
            Venue::create(['name' => $name, 'building_id' => Building::where('name', $building)->value('id')]);
        }
    }
}
