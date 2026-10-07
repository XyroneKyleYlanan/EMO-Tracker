<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOfficer(): bool
    {
        return $this->role === 'officer';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Signs the account out on every device, optionally keeping the one in use.
     */
    public function signOutEverywhere(mixed $except = null): void
    {
        $this->tokens()
            ->when($except instanceof PersonalAccessToken, fn ($tokens) => $tokens->whereKeyNot($except->getKey()))
            ->delete();
    }

    public function eventsCreated(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }
}
