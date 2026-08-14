<?php

namespace App\Filament\Resources\Transport\Trips\Tables;

use App\Enums\TripStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TripsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trip_number')
                    ->label('Trip #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('origin')
                    ->label('Origin')
                    ->searchable(),

                TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable(),

                TextColumn::make('driver.name')
                    ->label('Driver')
                    ->searchable(),

                TextColumn::make('vehicle.plate_number')
                    ->label('Vehicle')
                    ->searchable(),

                TextColumn::make('allocated_payload_tons')
                    ->label('Payload (T)')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('total_distance_km')
                    ->label('Distance (KM)')
                    ->numeric(0)
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TripStatus $state): string => $state->getLabel())
                    ->color(fn (TripStatus $state): string => $state->getColor()),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
