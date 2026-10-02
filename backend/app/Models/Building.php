<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $fillable = ['name', 'color'];

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }
}
