<?php

namespace App\Models;

use App\Services\EventClassifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Event extends Model
{
    use HasFactory;

    // The task columns readiness needs, for eager loading: with([Event::READINESS_TASKS]).
    public const READINESS_TASKS = 'tasks:id,event_id,status,assigned_to,due_date';

    protected $fillable = [
        'name',
        'description',
        'department',
        'event_type',
        'venue_id',
        'venue_details',
        'event_date',
        'end_date',
        'event_time',
        'end_time',
        'original_date',
        'original_time',
        'control_number',
        'remarks',
        'needs_preparation',
        'status',
        'created_by',
    ];

    protected $appends = ['readiness', 'location', 'ongoing'];

    protected function casts(): array
    {
        return [
            'event_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'original_date' => 'date:Y-m-d',
            'needs_preparation' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getReadinessAttribute(): string
    {
        return EventClassifier::classify($this);
    }

    // Why the event has its readiness, e.g. "1 task is overdue". Not appended
    // by default; call append('readiness_reason') where it is shown.
    public function getReadinessReasonAttribute(): ?string
    {
        return EventClassifier::reason($this);
    }

    /**
     * Where the event is held, e.g. "SOM 504–507": the venue from the managed
     * list plus the free-text room/details, or just the details for "Other".
     */
    public function getLocationAttribute(): ?string
    {
        $parts = array_filter([$this->venue?->name, $this->venue_details]);

        return $parts ? implode(' ', $parts) : null;
    }

    /**
     * Events the user is working on: the ones where they have a task. (In a
     * small office everyone can see every event; this is just "my events".)
     */
    public function scopeInvolving(Builder $query, User $user): void
    {
        $query->whereHas('tasks', fn ($tasks) => $tasks->where('assigned_to', $user->id));
    }

    /*
     * Lifecycle: an event is upcoming until it starts, ongoing while it runs,
     * and completed once it ends. Cancelled is set by hand and overrides all
     * three. Only upcoming, completed and cancelled are stored: "ongoing" is an
     * upcoming event that has started, so everything that lists events that
     * aren't over yet (Home, readiness, task lists) keeps including it.
     */

    // The start time on the first day, or the start of that day.
    public static function startsAt($date, $time = null): Carbon
    {
        $day = Carbon::parse($date)->startOfDay();

        return $time ? $day->setTimeFromTimeString($time) : $day;
    }

    // The end time on the last day, or the end of that day.
    public static function endsAt($date, $endDate = null, $endTime = null): Carbon
    {
        $day = Carbon::parse($endDate ?? $date);

        return $endTime ? $day->startOfDay()->setTimeFromTimeString($endTime) : $day->endOfDay();
    }

    public static function statusForDate($date, $endDate = null, $endTime = null): string
    {
        return self::endsAt($date, $endDate, $endTime)->isFuture() ? 'upcoming' : 'completed';
    }

    public function getOngoingAttribute(): bool
    {
        return $this->status === 'upcoming'
            && $this->event_date !== null
            && ! self::startsAt($this->event_date, $this->event_time)->isFuture()
            && self::endsAt($this->event_date, $this->end_date, $this->end_time)->isFuture();
    }

    // Upcoming, ongoing, completed or cancelled: the status people see.
    public function getLifecycleAttribute(): string
    {
        return $this->ongoing ? 'ongoing' : $this->status;
    }

    public static function completePastEvents(): void
    {
        $today = today()->toDateString();
        $lastDay = fn ($q) => $q
            ->where(fn ($single) => $single->whereNull('end_date')->whereDate('event_date', $today))
            ->orWhereDate('end_date', $today);

        static::where('status', 'upcoming')
            ->where(fn ($q) => $q
                ->where(fn ($single) => $single->whereNull('end_date')->whereDate('event_date', '<', $today))
                ->orWhereDate('end_date', '<', $today)
                // Ended earlier today.
                ->orWhere(fn ($endedToday) => $endedToday
                    ->whereNotNull('end_time')
                    ->whereTime('end_time', '<=', now()->format('H:i:s'))
                    ->where($lastDay)))
            ->update(['status' => 'completed']);
    }
}
