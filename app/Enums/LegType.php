<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LegType: string implements HasColor, HasLabel
{
    case Primary = 'primary';

    case Return = 'return';

    case Backhaul = 'backhaul';

    public function getLabel(): string
    {
        return match ($this) {
            self::Primary => 'Primary',
            self::Return => 'Return Cargo',
            self::Backhaul => 'Backhaul',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Primary => 'success',
            self::Return => 'info',
            self::Backhaul => 'warning',
        };
    }
}
