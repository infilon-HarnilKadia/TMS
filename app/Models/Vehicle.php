<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $guarded = [];

    /** @return HasMany<Trip, $this> */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
