<?php

namespace App\Models;

use App\Services\EventClassifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'venue_id',
        'venue_details',
        'event_date',
        'end_date',
        'event_time',
        'end_time',
        'budget',
        'control_number',
        'remarks',
        'needs_preparation',
        'status',
        'created_by',
    ];

    protected $appends = ['readiness', 'location'];

    protected function casts(): array
    {
        return [
            'event_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'budget' => 'decimal:2',
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

    /**
     * No longer used: the people on an event are now whoever has a task on it.
     * The event_staff table is dropped once the demo seeder stops filling it.
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_staff')->withTimestamps();
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

    /**
     * An event is over once its last day has passed (its end date for
     * multi-day events, otherwise its date).
     */
    public static function statusForDate($date, $endDate = null): string
    {
        return Carbon::parse($endDate ?? $date)->lt(today()) ? 'completed' : 'upcoming';
    }

    public static function completePastEvents(): void
    {
        static::where('status', 'upcoming')
            ->where(fn ($q) => $q
                ->where(fn ($single) => $single->whereNull('end_date')->whereDate('event_date', '<', today()))
                ->orWhereDate('end_date', '<', today()))
            ->update(['status' => 'completed']);
    }
}
