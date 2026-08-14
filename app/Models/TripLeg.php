<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripLeg extends Model
{
    /** @use HasFactory<\Database\Factories\TripLegFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload_weight_tons' => 'decimal:2',
            'distance_km' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Trip, $this> */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
