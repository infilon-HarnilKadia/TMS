<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TripStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';

    case Planned = 'planned';

    case InTransit = 'in_transit';

    case Completed = 'completed';

    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Planned => 'Planned',
            self::InTransit => 'In Transit',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Planned => 'info',
            self::InTransit => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
