<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfferingResource\Pages;

use App\Filament\Resources\OfferingResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateOffering extends CreateRecord
{
    protected static string $resource = OfferingResource::class;
}
