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
     * Staff only see events they are assigned to, either at event level
     * or through a task. Admins and officers see everything.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! $user->isStaff()) {
            return;
        }

        $query->where(fn ($q) => $q
            ->whereHas('staff', fn ($staff) => $staff->where('users.id', $user->id))
            ->orWhereHas('tasks', fn ($tasks) => $tasks->where('assigned_to', $user->id)));
    }

    public function isVisibleTo(User $user): bool
    {
        return ! $user->isStaff() || static::visibleTo($user)->whereKey($this->id)->exists();
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
