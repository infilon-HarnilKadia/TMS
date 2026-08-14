<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $guarded = [];

    /** @return HasMany<Trip, $this> */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /** @return HasMany<TripLeg, $this> */
    public function tripLegs(): HasMany
    {
        return $this->hasMany(TripLeg::class);
    }
}
