<?php

namespace App\Filament\Resources\CargoFeeResource\Pages;

use App\Filament\Resources\CargoFeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCargoFees extends ListRecords
{
    protected static string $resource = CargoFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
