<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $fillable = ['name', 'color'];

    /**
     * Light, sheet-style colors (Google Sheets' two lightest rows, which the
     * EMO's schedule already uses), in hue order. Every one keeps the
     * Schedule's text and small notes readable (contrast of 4.5:1 or more).
     */
    public const PALETTE = [
        '#F4CCCC', '#EA9999', '#E6B8AF', '#DD7E6B', '#FCE5CD', '#F9CB9C',
        '#FFF2CC', '#FFE599', '#FFD966', '#D9EAD3', '#B6D7A8', '#D0E0E3',
        '#A2C4C9', '#CFE2F3', '#9FC5E8', '#C9DAF8', '#A4C2F4', '#D9D2E9',
        '#B4A7D6', '#EAD1DC', '#D5A6BD', '#EFEFEF', '#D9D9D9', '#CCCCCC',
    ];

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }
}
