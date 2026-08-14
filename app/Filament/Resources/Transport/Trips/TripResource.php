<?php

namespace App\Filament\Resources\Transport\Trips;

use App\Filament\Resources\Transport\Trips\Pages\CreateTrip;
use App\Filament\Resources\Transport\Trips\Pages\EditTrip;
use App\Filament\Resources\Transport\Trips\Pages\ListTrips;
use App\Filament\Resources\Transport\Trips\Schemas\TripForm;
use App\Filament\Resources\Transport\Trips\Tables\TripsTable;
use App\Models\Trip;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * @extends \Filament\Resources\Resource<Trip>
 */
class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $slug = 'transport/trips';

    protected static ?string $recordTitleAttribute = 'trip_number';

    protected static string | UnitEnum | null $navigationGroup = 'Transport';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return TripForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TripsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrips::route('/'),
            'create' => CreateTrip::route('/create'),
            'edit' => EditTrip::route('/{record}/edit'),
        ];
    }

    /** @return Builder<Trip> */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['trip_number', 'client.name', 'driver.name', 'vehicle.plate_number'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Trip $record */

        return [
            'Client' => optional($record->client)->name,
            'Driver' => optional($record->driver)->name,
        ];
    }

    /** @return Builder<Trip> */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['client', 'driver', 'vehicle']);
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::$model;

        return (string) $modelClass::where('status', 'in_transit')->count();
    }
}
