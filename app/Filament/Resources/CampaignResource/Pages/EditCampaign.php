<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Services\SlugCreateService;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }


    protected function mutateFormDataBeforeSave(array $data): array
{   
    $data['slug'] = app(SlugCreateService::class)->createSlug(
        $data,
        \App\Models\Campaign::class,
        $this->record->id
    );
    
    return $data;
}
}
