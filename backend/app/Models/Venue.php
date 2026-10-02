<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    protected $fillable = ['name', 'building_id'];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
