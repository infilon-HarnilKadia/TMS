<?php

use App\Filament\Resources\Transport\Trips\Pages\CreateTrip;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripLeg;
use App\Models\Vehicle;
use Livewire\Livewire;

function tripWizardFixtures(): array
{
    return [
        'vehicle' => Vehicle::factory()->create([
            'plate_number' => 'T144EAM',
            'make' => 'Scania',
            'model' => 'R500',
            'capacity_tons' => 30,
            'fuel_type' => 'Diesel',
            'tank_capacity_litres' => 600,
            'current_odometer_km' => 142500,
            'starting_fuel_litres' => 120,
            'status' => 'active',
        ]),
        'driver' => Driver::factory()->create(['name' => 'Hamisi Omari']),
        'client' => Client::factory()->create(['name' => 'Tanzania Portland Cement']),
    ];
}

it('can render the create trip wizard', function () {
    Livewire::test(CreateTrip::class)->assertOk();
});

it('creates a trip with its dispatch details and a backhaul cargo leg', function () {
    ['vehicle' => $vehicle, 'driver' => $driver, 'client' => $client] = tripWizardFixtures();

    $backhaulClient = Client::factory()->create(['name' => 'Bakhresa Group']);

    Livewire::test(CreateTrip::class)
        ->fillForm([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'client_id' => $client->id,
            'allocated_payload_tons' => 30,
            'commodity_group' => 'Cement (Premium Grade)',
            'origin' => 'DAR ES SALAAM',
            'destination' => 'MWANZA',
            'estimated_departure' => '2025-05-18 08:00:00',
            'estimated_arrival' => '2025-05-20 18:00:00',
            'invoice_reference' => 'INV/2025/00432',
            'waybill_number' => 'WB-998821',
            'total_distance_km' => 1120,
            'legs' => [
                [
                    'leg_type' => 'return',
                    'client_id' => $backhaulClient->id,
                    'origin' => 'MWANZA',
                    'destination' => 'DODOMA',
                    'payload_weight_tons' => 25,
                    'commodity_category' => 'agri',
                    'distance_km' => 450,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $trip = Trip::latest('id')->first();

    expect($trip)->not->toBeNull()
        ->and($trip->trip_number)->toStartWith('TRIP-')
        ->and($trip->origin)->toBe('DAR ES SALAAM')
        ->and($trip->destination)->toBe('MWANZA')
        ->and($trip->commodity_group)->toBe('Cement (Premium Grade)')
        ->and($trip->invoice_reference)->toBe('INV/2025/00432')
        ->and($trip->waybill_number)->toBe('WB-998821')
        ->and($trip->estimated_departure->format('Y-m-d H:i'))->toBe('2025-05-18 08:00');

    expect($trip->legs)->toHaveCount(1);

    $leg = $trip->legs->first();

    expect($leg)->toBeInstanceOf(TripLeg::class)
        ->and($leg->origin)->toBe('MWANZA')
        ->and($leg->destination)->toBe('DODOMA')
        ->and((float) $leg->distance_km)->toBe(450.0);
});

it('shows the assigned vehicle specs once a vehicle is selected', function () {
    ['vehicle' => $vehicle, 'driver' => $driver] = tripWizardFixtures();

    Livewire::test(CreateTrip::class)
        ->fillForm([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
        ])
        ->assertSee('Assigned Scania R500 Specs')
        ->assertSee('T144EAM')
        ->assertSee('30,000 kg (30T)')
        ->assertSee('600 Litres')
        ->assertSee('142,500 KM')
        ->assertSee('Hamisi Omari')
        // Callout is only rendered for roadworthy vehicles
        ->assertSee('cleared for long haul');
});

it('builds a route timeline that totals the primary leg and every backhaul', function () {
    ['vehicle' => $vehicle] = tripWizardFixtures();

    Livewire::test(CreateTrip::class)
        ->fillForm([
            'vehicle_id' => $vehicle->id,
            'origin' => 'Dar Es Salaam',
            'destination' => 'Mwanza',
            'total_distance_km' => 1120,
            'legs' => [
                ['origin' => 'MWANZA', 'destination' => 'Dodoma', 'distance_km' => 450],
                ['origin' => 'DODOMA', 'destination' => 'Dar Es Salaam', 'distance_km' => 450],
            ],
        ])
        ->assertSee('Primary Dispatch Hub')
        ->assertSee('1,120 KM')
        // 1120 + 450 + 450
        ->assertSee('End Journey (Total Trip: 2,020 KM)');
});

it('requires the fields the dispatch board depends on', function () {
    Livewire::test(CreateTrip::class)
        ->fillForm([
            'vehicle_id' => null,
            'driver_id' => null,
            'client_id' => null,
            'origin' => null,
            'destination' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'vehicle_id' => 'required',
            'driver_id' => 'required',
            'client_id' => 'required',
            'origin' => 'required',
            'destination' => 'required',
        ]);
});
