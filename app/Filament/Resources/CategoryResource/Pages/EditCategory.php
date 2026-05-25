<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Services\SlugCreateService;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }



    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['slug'] = app(SlugCreateService::class)->createSlug($data, \App\Models\Product::class, $this->record->id);
        return $data;
    }
}
