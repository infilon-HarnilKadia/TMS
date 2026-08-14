<?php

namespace App\Models;

use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    /** @use HasFactory<\Database\Factories\TripFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => TripStatus::class,
            'estimated_departure' => 'datetime',
            'estimated_arrival' => 'datetime',
            'total_distance_km' => 'decimal:2',
            'allocated_payload_tons' => 'decimal:2',
            'estimated_fuel_cost' => 'decimal:2',
            'actual_fuel_cost' => 'decimal:2',
            'estimated_expenses' => 'decimal:2',
            'actual_expenses' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Driver, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /** @return HasMany<TripLeg, $this> */
    public function legs(): HasMany
    {
        return $this->hasMany(TripLeg::class)->orderBy('leg_number');
    }
}
