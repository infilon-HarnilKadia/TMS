<?php

namespace App\Filament\Resources\Transport\Trips\Pages;

use App\Enums\CommodityCategory;
use App\Enums\LegType;
use App\Enums\TripStatus;
use App\Filament\Resources\Transport\Trips\TripResource;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Contracts\View\View;

class CreateTrip extends CreateRecord
{
    use HasWizard;

    protected static string $resource = TripResource::class;

    /**
     * @return array<Step>
     */
    protected function getSteps(): array
    {
        return [
            Step::make('Trip Details')
                ->schema([
                    Grid::make(3)->schema([
                        Section::make('Step 1: Primary Trip Information')
                            ->description('Input basic tracking details, assign a driver and select the commercial carrier client.')
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema(static::getTripDetailsComponents()),

                        static::getVehicleSpecsPanel(),
                    ]),
                ]),

            Step::make('Cargo & Route')
                ->schema([
                    Grid::make(3)->schema([
                        Group::make()
                            ->columnSpan(2)
                            ->schema([
                                Section::make('Primary Outbound Segment')
                                    ->description('Carried over from the trip details step.')
                                    ->columns(2)
                                    ->schema(static::getPrimarySegmentComponents()),

                                Section::make('Additional / Return Cargo Legs')
                                    ->description('Minimize empty backhauls by mapping secondary routes.')
                                    ->schema([static::getCargoLegsRepeater()]),
                            ]),

                        static::getRouteTimelinePanel(),
                    ]),
                ]),

            Step::make('Fuel')
                ->schema([
                    Section::make('Step 3: Fuel Configuration')
                        ->description('Plan the fuel budget for the full round trip.')
                        ->columns(2)
                        ->schema([
                            TextInput::make('estimated_fuel_cost')
                                ->label('Estimated Fuel Cost')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('TZS'),

                            TextInput::make('estimated_fuel_litres')
                                ->label('Estimated Fuel Volume')
                                ->numeric()
                                ->minValue(0)
                                ->suffix('Litres')
                                ->dehydrated(false),

                            Textarea::make('fuel_notes')
                                ->label('Fuel Notes')
                                ->rows(3)
                                ->columnSpanFull()
                                ->dehydrated(false),
                        ]),
                ]),

            Step::make('Expenses')
                ->schema([
                    Section::make('Step 4: Trip Expenses')
                        ->description('Budget tolls, allowances and incidentals before dispatch.')
                        ->columns(2)
                        ->schema([
                            TextInput::make('estimated_expenses')
                                ->label('Estimated Total Expenses')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('TZS'),

                            TextInput::make('driver_allowance')
                                ->label('Driver Allowance')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('TZS')
                                ->dehydrated(false),

                            Textarea::make('notes')
                                ->label('Expense Notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ]),

            Step::make('Review')
                ->schema([
                    Section::make('Step 5: Review & Dispatch')
                        ->description('Confirm the trip plan before it is written to the dispatch board.')
                        ->columns(3)
                        ->schema([
                            TextEntry::make('trip_number_review')
                                ->label('Trip Ref')
                                ->state(fn (Get $get): string => $get('trip_number') ?: '—'),

                            TextEntry::make('route_review')
                                ->label('Route')
                                ->state(fn (Get $get): string => filled($get('origin')) && filled($get('destination'))
                                    ? mb_strtoupper((string) $get('origin')) . ' → ' . mb_strtoupper((string) $get('destination'))
                                    : '—'),

                            TextEntry::make('payload_review')
                                ->label('Payload')
                                ->state(fn (Get $get): string => filled($get('allocated_payload_tons'))
                                    ? $get('allocated_payload_tons') . ' Tons'
                                    : '—'),

                            TextEntry::make('legs_review')
                                ->label('Cargo Legs')
                                ->state(fn (Get $get): string => (string) count($get('legs') ?? [])),

                            TextEntry::make('distance_review')
                                ->label('Total Distance')
                                ->state(fn (Get $get): string => number_format((float) static::totalDistance($get)) . ' KM'),

                            TextEntry::make('status_review')
                                ->label('Status')
                                ->state(fn (Get $get): string => TripStatus::tryFrom((string) $get('status'))?->getLabel() ?? 'Draft'),
                        ]),
                ]),
        ];
    }

    /**
     * @return array<Component>
     */
    protected static function getTripDetailsComponents(): array
    {
        return [
            TextInput::make('trip_number')
                ->label('Trip Ref (Auto)')
                ->default(fn (): string => static::nextTripNumber())
                ->disabled()
                ->dehydrated()
                ->required()
                ->unique(Trip::class, 'trip_number'),

            Select::make('vehicle_id')
                ->label('Vehicle Assignment')
                ->relationship('vehicle', 'plate_number')
                ->getOptionLabelFromRecordUsing(fn (Vehicle $record): string => static::vehicleLabel($record))
                ->searchable()
                ->preload()
                ->live()
                ->required(),

            Select::make('driver_id')
                ->label('Assigned Primary Driver')
                ->relationship('driver', 'name')
                ->searchable()
                ->preload()
                ->live()
                ->required(),

            Select::make('client_id')
                ->label('Client')
                ->relationship('client', 'name')
                ->searchable()
                ->preload()
                ->live()
                ->required(),

            TextInput::make('allocated_payload_tons')
                ->label('Tonnage (Tons)')
                ->numeric()
                ->minValue(0)
                ->live(onBlur: true),

            TextInput::make('commodity_group')
                ->label('Commodity Group')
                ->maxLength(255)
                ->placeholder('e.g. Cement (Premium Grade)'),

            TextInput::make('origin')
                ->label('Origin Hub')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->placeholder('e.g. DAR ES SALAAM'),

            TextInput::make('destination')
                ->label('Primary Destination')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->placeholder('e.g. MWANZA'),

            DateTimePicker::make('estimated_departure')
                ->label('Estimated Departure')
                ->seconds(false)
                ->native(false),

            DateTimePicker::make('estimated_arrival')
                ->label('Estimated Arrival')
                ->seconds(false)
                ->native(false)
                ->after('estimated_departure'),

            TextInput::make('invoice_reference')
                ->label('Invoice Reference #')
                ->maxLength(255)
                ->placeholder('e.g. INV/2025/00432'),

            TextInput::make('waybill_number')
                ->label('Document / Waybill No.')
                ->maxLength(255)
                ->placeholder('e.g. WB-998821'),
        ];
    }

    /**
     * Read-only mirrors of the step 1 values, so the dispatcher can confirm the
     * outbound leg without stepping back.
     *
     * These are entries rather than disabled inputs on purpose: an input bound
     * to the same state path as step 1 registers a second, rule-less component
     * for that path and silently drops step 1's `required` validation.
     *
     * @return array<Component>
     */
    protected static function getPrimarySegmentComponents(): array
    {
        return [
            TextEntry::make('outbound_client')
                ->label('Outbound Client')
                ->placeholder('Not selected')
                ->state(fn (Get $get): ?string => Client::find($get('client_id'))?->name),

            TextEntry::make('outbound_payload')
                ->label('Allocated Payload')
                ->placeholder('Not set')
                ->state(fn (Get $get): ?string => filled($get('allocated_payload_tons'))
                    ? $get('allocated_payload_tons') . ' Tons'
                    : null),

            TextEntry::make('outbound_origin')
                ->label('Origin Location')
                ->placeholder('Not set')
                ->state(fn (Get $get): ?string => filled($get('origin')) ? mb_strtoupper((string) $get('origin')) : null),

            TextEntry::make('outbound_destination')
                ->label('Destination Location')
                ->placeholder('Not set')
                ->state(fn (Get $get): ?string => filled($get('destination')) ? mb_strtoupper((string) $get('destination')) : null),

            TextInput::make('total_distance_km')
                ->label('Planned Distance')
                ->numeric()
                ->minValue(0)
                ->suffix('KM')
                ->live(onBlur: true)
                ->columnSpanFull(),
        ];
    }

    protected static function getCargoLegsRepeater(): Component
    {
        return Repeater::make('legs')
            ->relationship('legs')
            ->hiddenLabel()
            ->columns(2)
            ->defaultItems(0)
            ->addActionLabel('Add Cargo Leg')
            ->itemLabel(fn (array $state): string => filled($state['destination'] ?? null)
                ? 'Leg to ' . mb_strtoupper((string) $state['destination'])
                : 'New cargo leg')
            ->schema([
                Select::make('leg_type')
                    ->label('Leg Type')
                    ->options(LegType::class)
                    ->default(LegType::Return)
                    ->required(),

                Select::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                TextInput::make('origin')
                    ->label('Origin (Load Hub)')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->placeholder('e.g. MWANZA'),

                TextInput::make('destination')
                    ->label('Destination (Discharge)')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->placeholder('e.g. DODOMA'),

                TextInput::make('payload_weight_tons')
                    ->label('Return Payload Weight')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('Tons'),

                Select::make('commodity_category')
                    ->label('Commodity Category')
                    ->options(CommodityCategory::class)
                    ->searchable(),

                TextInput::make('distance_km')
                    ->label('Leg Distance')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('KM')
                    ->live(onBlur: true)
                    ->columnSpanFull(),
            ]);
    }

    protected static function getVehicleSpecsPanel(): Component
    {
        return Section::make(fn (Get $get): string => static::vehicleSpecsHeading($get))
            ->icon('heroicon-o-truck')
            ->columnSpan(1)
            ->schema([
                TextEntry::make('spec_plate')
                    ->label('Registration Plate')
                    ->inlineLabel()
                    ->state(fn (Get $get): string => static::vehicle($get)?->plate_number ?? '—'),

                TextEntry::make('spec_capacity')
                    ->label('Max Cargo Capacity')
                    ->inlineLabel()
                    ->state(function (Get $get): string {
                        $tons = static::vehicle($get)?->capacity_tons;

                        return $tons
                            ? number_format((float) $tons * 1000) . ' kg (' . rtrim(rtrim(number_format((float) $tons, 2, '.', ''), '0'), '.') . 'T)'
                            : '—';
                    }),

                TextEntry::make('spec_fuel_type')
                    ->label('Standard Fuel Type')
                    ->inlineLabel()
                    ->state(fn (Get $get): string => static::vehicle($get)?->fuel_type ?? '—'),

                TextEntry::make('spec_tank')
                    ->label('Tank Capacity')
                    ->inlineLabel()
                    ->state(fn (Get $get): string => ($litres = static::vehicle($get)?->tank_capacity_litres)
                        ? number_format($litres) . ' Litres'
                        : '—'),

                TextEntry::make('spec_driver')
                    ->label('Assigned Driver')
                    ->inlineLabel()
                    ->state(fn (Get $get): string => Driver::find($get('driver_id'))?->name ?? '—'),

                TextEntry::make('spec_odometer')
                    ->label('Current Odometer')
                    ->inlineLabel()
                    ->state(fn (Get $get): string => ($km = static::vehicle($get)?->current_odometer_km)
                        ? number_format($km) . ' KM'
                        : '—'),

                TextEntry::make('spec_starting_fuel')
                    ->label('Est. Starting Fuel')
                    ->inlineLabel()
                    ->state(fn (Get $get): string => ($litres = static::vehicle($get)?->starting_fuel_litres)
                        ? number_format($litres) . ' Litres'
                        : '—'),

                Callout::make('This truck is cleared for long haul. Active maintenance checks are valid.')
                    ->success()
                    ->visible(fn (Get $get): bool => static::vehicle($get)?->status === 'active'),
            ]);
    }

    protected static function getRouteTimelinePanel(): Component
    {
        return Section::make('Trip Route Timeline')
            ->icon('heroicon-o-map-pin')
            ->columnSpan(1)
            ->schema([
                Text::make(fn (Get $get): View => view('filament.trip.route-timeline', [
                    'stops' => static::routeStops($get),
                ])),
            ]);
    }

    /**
     * Origin → primary destination → each cargo leg destination, with the last
     * stop flagged as the end of the journey.
     *
     * @return array<int, array{label: string, caption: string, distance: ?string, tone: string}>
     */
    protected static function routeStops(Get $get): array
    {
        $origin = $get('origin');
        $destination = $get('destination');

        if (blank($origin) || blank($destination)) {
            return [];
        }

        $stops = [[
            'label' => mb_strtoupper((string) $origin),
            'caption' => 'Primary Dispatch Hub',
            'distance' => null,
            'tone' => 'start',
        ]];

        $legs = collect($get('legs') ?? [])->filter(fn ($leg): bool => filled($leg['destination'] ?? null));

        $stops[] = [
            'label' => mb_strtoupper((string) $destination),
            'caption' => $legs->isNotEmpty() ? 'Primary Discharge & Backhaul Reload' : 'Primary Discharge',
            'distance' => static::formatKm($get('total_distance_km')),
            'tone' => 'via',
        ];

        foreach ($legs as $leg) {
            $stops[] = [
                'label' => mb_strtoupper((string) $leg['destination']),
                'caption' => 'Secondary Backhaul Discharge',
                'distance' => static::formatKm($leg['distance_km'] ?? null),
                'tone' => 'via',
            ];
        }

        $lastIndex = array_key_last($stops);
        $stops[$lastIndex]['tone'] = 'end';
        $stops[$lastIndex]['caption'] = 'End Journey (Total Trip: ' . number_format(static::totalDistance($get)) . ' KM)';

        return $stops;
    }

    protected static function totalDistance(Get $get): float
    {
        return (float) ($get('total_distance_km') ?? 0)
            + collect($get('legs') ?? [])->sum(fn ($leg): float => (float) ($leg['distance_km'] ?? 0));
    }

    protected static function formatKm(mixed $km): ?string
    {
        return filled($km) && (float) $km > 0
            ? number_format((float) $km) . ' KM'
            : null;
    }

    protected static function vehicle(Get $get): ?Vehicle
    {
        $id = $get('vehicle_id');

        return filled($id) ? Vehicle::find($id) : null;
    }

    protected static function vehicleLabel(Vehicle $vehicle): string
    {
        $model = trim(implode(' ', array_filter([$vehicle->make, $vehicle->model])));

        return filled($model)
            ? "{$vehicle->plate_number} — {$model}"
            : (string) $vehicle->plate_number;
    }

    protected static function vehicleSpecsHeading(Get $get): string
    {
        $vehicle = static::vehicle($get);

        if (! $vehicle) {
            return 'Vehicle Specs';
        }

        $model = trim(implode(' ', array_filter([$vehicle->make, $vehicle->model])));

        return filled($model) ? "Assigned {$model} Specs" : 'Assigned Vehicle Specs';
    }

    protected static function nextTripNumber(): string
    {
        return 'TRIP-' . str_pad((string) (((int) Trip::withTrashed()->max('id')) + 1), 6, '0', STR_PAD_LEFT);
    }

    protected function afterCreate(): void
    {
        /** @var Trip $trip */
        $trip = $this->record;

        Notification::make()
            ->title('Trip created')
            ->body("Trip {$trip->trip_number} has been added to the dispatch board.")
            ->success()
            ->send();
    }
}
