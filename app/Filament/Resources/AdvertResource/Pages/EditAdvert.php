<?php

namespace App\Filament\Resources\AdvertResource\Pages;

use App\Filament\Resources\AdvertResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Services\SlugCreateService;

class EditAdvert extends EditRecord
{
    protected static string $resource = AdvertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['slug'] = app(SlugCreateService::class)->createSlug($data, \App\Models\Advert::class,$this->record->id);
        
        return $data;
    }
}
