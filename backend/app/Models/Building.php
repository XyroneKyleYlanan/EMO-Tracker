<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $fillable = ['name', 'color'];

    /**
     * Light, sheet-style colors (Google Sheets' light palette, which the EMO's
     * schedule already uses). Dark text stays readable on all of them.
     */
    public const PALETTE = [
        '#EA9999', '#DD7E6B', '#F9CB9C', '#FFE599', '#FFD966', '#B6D7A8',
        '#A2C4C9', '#9FC5E8', '#A4C2F4', '#B4A7D6', '#D5A6BD', '#D9D9D9',
    ];

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }
}
