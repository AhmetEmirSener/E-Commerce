<?php

namespace App\Filament\Resources\RefundRequestResource\Pages;

use App\Filament\Resources\RefundRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRefundRequests extends ListRecords
{
    protected static string $resource = RefundRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
