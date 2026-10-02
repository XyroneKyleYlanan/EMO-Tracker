<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Venue;

/**
 * Applies TextTidy to what's already in the database: event names,
 * departments, rooms and remarks, plus venue names.
 */
class ScheduleTidy
{
    /**
     * @return array<int, array{0: string, 1: int|string, 2: string, 3: string, 4: ?string, 5: ?string}>
     *                                                                                                   [what, id, label, field, before, after]
     */
    public static function run(bool $dryRun = false): array
    {
        $changes = [];

        foreach (Event::orderBy('event_date')->get() as $event) {
            [$name, $cancelNote] = TextTidy::cancellation($event->name);
            $new = [
                'name' => TextTidy::title($name) ?? $event->name,
                'department' => TextTidy::title($event->department),
                'venue_details' => TextTidy::room($event->venue_details),
                'remarks' => TextTidy::remark($event->remarks),
            ];
            if ($cancelNote) {
                $new['remarks'] = trim(($new['remarks'] ? "{$new['remarks']} · " : '').$cancelNote);
                $new['status'] = 'cancelled';
            }

            foreach ($new as $field => $value) {
                if ($value !== $event->{$field}) {
                    $changes[] = ['Event', $event->id, $event->event_date->format('M j, Y'), $field, $event->{$field}, $value];
                }
            }
            if (! $dryRun) {
                $event->update($new);
            }
        }

        // Names in use by each venue, compared case-insensitively like MySQL does.
        $taken = Venue::pluck('name', 'id')->map(fn ($n) => mb_strtolower($n))->all();
        foreach (Venue::orderBy('name')->get() as $venue) {
            $name = TextTidy::title($venue->name);
            if ($name === $venue->name) {
                continue;
            }
            $others = array_diff_key($taken, [$venue->id => true]);
            if (in_array(mb_strtolower($name), $others, true)) {
                $changes[] = ['Venue', $venue->id, $venue->name, 'name', $venue->name, "(not renamed: \"{$name}\" already exists, merge them on the Venues page)"];

                continue;
            }
            $changes[] = ['Venue', $venue->id, $venue->name, 'name', $venue->name, $name];
            $taken[$venue->id] = mb_strtolower($name);
            if (! $dryRun) {
                $venue->update(['name' => $name]);
            }
        }

        return $changes;
    }
}
