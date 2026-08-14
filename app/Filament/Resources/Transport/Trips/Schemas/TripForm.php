<?php

namespace App\Filament\Resources\Transport\Trips\Schemas;

use App\Enums\TripStatus;
use App\Models\Trip;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TripForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Trip Information')
                            ->schema(static::getTripInfoComponents())
                            ->columns(2),

                        Section::make('Route Details')
                            ->schema(static::getRouteComponents())
                            ->columns(2),

                        Section::make('Additional Notes')
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(3)
                                    ->placeholder('Additional trip notes...'),
                            ]),
                    ])
                    ->columnSpan(2),

                Section::make('Summary')
                    ->schema([
                        TextInput::make('trip_number')
                            ->label('Trip Number')
                            ->default('TR-' . random_int(100000, 999999))
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(Trip::class, 'trip_number', ignoreRecord: true),

                        Select::make('status')
                            ->options(TripStatus::class)
                            ->default(TripStatus::Draft)
                            ->required(),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    /**
     * @return array<Component>
     */
    public static function getTripInfoComponents(): array
    {
        return [
            Select::make('client_id')
                ->label('Client')
                ->relationship('client', 'name')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('email')->email()->maxLength(255),
                    TextInput::make('phone')->maxLength(255),
                    TextInput::make('company')->maxLength(255),
                ])
                ->required(),

            Select::make('vehicle_id')
                ->label('Vehicle')
                ->relationship('vehicle', 'plate_number')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    TextInput::make('plate_number')->required()->maxLength(255),
                    TextInput::make('make')->maxLength(255),
                    TextInput::make('model')->maxLength(255),
                    TextInput::make('year')->integer(),
                    TextInput::make('capacity_tons')->numeric(),
                    Select::make('fuel_type')->options([
                        'diesel' => 'Diesel',
                        'petrol' => 'Petrol',
                        'electric' => 'Electric',
                    ]),
                ])
                ->required(),

            Select::make('driver_id')
                ->label('Driver')
                ->relationship('driver', 'name')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('email')->email()->maxLength(255),
                    TextInput::make('phone')->maxLength(255),
                    TextInput::make('license_number')->maxLength(255),
                ])
                ->required(),

            TextInput::make('allocated_payload_tons')
                ->label('Allocated Payload (Tons)')
                ->numeric()
                ->minValue(0)
                ->suffix('Tons'),

            Select::make('status')
                ->label('Trip Status')
                ->options([
                    'draft' => 'Draft',
                    'planned' => 'Planned',
                    'in_transit' => 'In Transit',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ])
                ->default('draft')
                ->required(),
        ];
    }

    /**
     * @return array<Component>
     */
    public static function getRouteComponents(): array
    {
        return [
            TextInput::make('origin')
                ->label('Origin Location')
                ->required()
                ->maxLength(255)
                ->placeholder('e.g. DAR ES SALAAM'),

            TextInput::make('destination')
                ->label('Destination Location')
                ->required()
                ->maxLength(255)
                ->placeholder('e.g. MWANZA'),

            TextInput::make('total_distance_km')
                ->label('Planned Distance (KM)')
                ->numeric()
                ->minValue(0)
                ->suffix('KM'),

            TextInput::make('estimated_fuel_cost')
                ->label('Estimated Fuel Cost')
                ->numeric()
                ->minValue(0)
                ->prefix('$'),

            TextInput::make('estimated_expenses')
                ->label('Estimated Total Expenses')
                ->numeric()
                ->minValue(0)
                ->prefix('$'),
        ];
    }
}
