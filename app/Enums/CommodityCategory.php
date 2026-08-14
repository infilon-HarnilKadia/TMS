<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CommodityCategory: string implements HasLabel
{
    case Agri = 'agri';

    case Industrial = 'industrial';

    case Consumer = 'consumer';

    case Construction = 'construction';

    case Chemical = 'chemical';

    case Petroleum = 'petroleum';

    case Minerals = 'minerals';

    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Agri => 'Agri',
            self::Industrial => 'Industrial',
            self::Consumer => 'Consumer',
            self::Construction => 'Construction',
            self::Chemical => 'Chemical',
            self::Petroleum => 'Petroleum',
            self::Minerals => 'Minerals',
            self::Other => 'Other',
        };
    }
}
