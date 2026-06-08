<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use Filament\Actions;
use App\Models\Advert;

use Filament\Resources\Pages\EditRecord;
use App\Services\SlugCreateService;
use App\Jobs\CreateCampaignProductsJob;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
      Actions\Action::make('runCampaignJob')
                ->label('Kampanyayı Başlat ve Ürünleri Hesapla')
                ->icon('heroicon-m-play')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Kampanya Başlatılsın Mı?')
                ->modalDescription('Bu kampanyaya ait kurallar taranacak, sadece şartları sağlayan ürünler filtrelenerek kuyruğa fırlatılacaktır.')
                ->action(function ($record) {
                    // $record zaten o an düzenlediğin Campaign modelidir kanka.
                    // İlişkili kuralları (rules) zaten Filament üzerinden kaydettiğimiz için doğrudan çekiyoruz:
                    $campaignRules = $record->rules;

                    // 1. Senin o meşhur dinamik query yapısını başlatıyoruz kanka
                    $query = Advert::query();
                    $sameFieldRules = $campaignRules->groupBy('field');

                    foreach ($sameFieldRules as $field => $rules) {
                        if ($field === 'price') {
                            foreach ($rules as $rule) {
                                $query->whereHas('product', function ($q) use ($rule) {
                                    $q->where('price', $rule->operator, $rule->value);
                                });
                            }
                        } else {
                            // Kategori veya diğer alanlar için orWhere koridoru mq
                            $query->where(function ($q) use ($rules, $field) {
                                foreach ($rules as $rule) {
                                    $q->orWhere($field, $rule->value);
                                }
                            });
                        }
                    }

                    // 2. Sadece kurallara uyan nokta atışı ilanları ilişkisiyle çekiyoruz kanka
                    $adverts = $query->with('product')->get();

                    // 3. Ve senin o canavar Job'ı bu filtrelenmiş listeyle ateşliyoruz!
                    CreateCampaignProductsJob::dispatch($record, $adverts);

                    // Adminin içine su serpecek o havalı bildirim:
                    \Filament\Notifications\Notification::make()
                        ->title('Kampanya Kuyruğu Başlatıldı 🚀')
                        ->body(count($adverts) . ' adet uygun ilan bulundu. Arka planda öncelikler ve indirimli fiyatlar hesaplanıyor kanka.')
                        ->success()
                        ->send();
                }),

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
