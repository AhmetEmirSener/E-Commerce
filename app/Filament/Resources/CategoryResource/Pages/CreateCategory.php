<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Services\SlugCreateService;
use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = app(SlugCreateService::class)->createSlug($data, \App\Models\Product::class);

        return $data;
    }
}
