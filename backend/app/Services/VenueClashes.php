<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Collection;

/**
 * Double-booking check. Two bookings clash when they're at the same venue and
 * room (or one of them takes the whole venue), on overlapping days, at
 * overlapping times; an event with no time counts as all day. Cancelled events
 * don't hold the venue, and free-text places can't be compared reliably.
 * It's a warning, not a rule: some overlaps are on purpose, like a rehearsal
 * right before its own event.
 */
class VenueClashes
{
    private const ALL_DAY = [0, 24 * 60];

    public static function between(Event $a, Event $b): bool
    {
        // Venue ids from the form arrive as text, so compare them as numbers.
        if ($a->is($b) || ! $a->venue_id || (int) $a->venue_id !== (int) $b->venue_id) {
            return false;
        }
        if ($a->status === 'cancelled' || $b->status === 'cancelled' || ! self::sameRoom($a->venue_details, $b->venue_details)) {
            return false;
        }
        if ($a->event_date->gt($b->end_date ?? $b->event_date) || $b->event_date->gt($a->end_date ?? $a->event_date)) {
            return false;
        }
        [$aFrom, $aTo] = self::hours($a);
        [$bFrom, $bTo] = self::hours($b);

        return $aFrom < $bTo && $bFrom < $aTo;
    }

    /**
     * Saved bookings that clash with this one, whether or not it's saved yet
     * (the event form checks before saving). $ignore leaves out the event
     * being edited.
     */
    public static function for(Event $event, ?int $ignore = null): Collection
    {
        if (! $event->venue_id || ! $event->event_date || $event->status === 'cancelled') {
            return collect();
        }
        $ignore ??= $event->exists ? $event->id : null;
        $start = $event->event_date->toDateString();
        $end = ($event->end_date ?? $event->event_date)->toDateString();

        return Event::with('venue:id,name')
            ->where('venue_id', $event->venue_id)
            ->where('status', '!=', 'cancelled')
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore))
            ->whereDate('event_date', '<=', $end)
            ->where(fn ($q) => $q
                ->whereDate('end_date', '>=', $start)
                ->orWhere(fn ($single) => $single->whereNull('end_date')->whereDate('event_date', '>=', $start)))
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get()
            ->filter(fn (Event $other) => self::between($event, $other))
            ->values();
    }

    /**
     * Which events in a list clash with another one in it.
     *
     * @return array<int, Event[]> event id => the events it clashes with
     */
    public static function within(Collection $events): array
    {
        $clashes = [];
        $booked = $events->whereNotNull('venue_id')->where('status', '!=', 'cancelled');
        foreach ($booked->groupBy('venue_id') as $atVenue) {
            $list = $atVenue->sortBy(fn (Event $e) => $e->event_date->toDateString())->values();
            foreach ($list as $i => $a) {
                for ($j = $i + 1; $j < $list->count(); $j++) {
                    $b = $list[$j];
                    // Sorted by start date: nothing later can overlap $a once one starts after it ends.
                    if ($b->event_date->gt($a->end_date ?? $a->event_date)) {
                        break;
                    }
                    if (self::between($a, $b)) {
                        $clashes[$a->id][] = $b;
                        $clashes[$b->id][] = $a;
                    }
                }
            }
        }

        return $clashes;
    }

    // What the form, panel and Schedule show about a clashing booking.
    public static function summary(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'event_date' => $event->event_date->toDateString(),
            'end_date' => $event->end_date?->toDateString(),
            'event_time' => $event->event_time,
            'end_time' => $event->end_time,
            'location' => $event->location,
        ];
    }

    // An empty room means the whole venue; otherwise the same room text (ignoring case and spaces).
    private static function sameRoom(?string $a, ?string $b): bool
    {
        $normal = fn (?string $room) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $room)));

        return $normal($a) === '' || $normal($b) === '' || $normal($a) === $normal($b);
    }

    // Daily hours in minutes. No time, or hours that run past midnight, count as
    // all day; no end time runs to the end of the day.
    private static function hours(Event $event): array
    {
        if (! $event->event_time) {
            return self::ALL_DAY;
        }
        $from = self::minutes($event->event_time);
        $to = $event->end_time ? self::minutes($event->end_time) : 24 * 60;

        return $to > $from ? [$from, $to] : self::ALL_DAY;
    }

    private static function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }
}
