<?php

namespace App\Filament\Resources\AdvertResource\Pages;

use App\Filament\Resources\AdvertResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Services\SlugCreateService;

class CreateAdvert extends CreateRecord
{
    protected static string $resource = AdvertResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Servisine veriyi ve Advert modelini gönderiyoruz
        $data['slug'] = app(SlugCreateService::class)->createSlug($data, \App\Models\Advert::class);
        
        return $data;
    }
    
}
