<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OfferingType: string implements HasLabel
{
    case Product = 'product';
    case Service = 'service';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Product => 'Product',
            self::Service => 'Service',
        };
    }
}