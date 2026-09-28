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
        'venue',
        'event_date',
        'event_time',
        'budget',
        'status',
        'created_by',
    ];

    protected $appends = ['readiness'];

    protected function casts(): array
    {
        return [
            'event_date' => 'date:Y-m-d',
            'budget' => 'decimal:2',
        ];
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

    public static function statusForDate($date): string
    {
        return Carbon::parse($date)->lt(today()) ? 'completed' : 'upcoming';
    }

    public static function completePastEvents(): void
    {
        static::where('status', 'upcoming')
            ->whereDate('event_date', '<', today())
            ->update(['status' => 'completed']);
    }
}
