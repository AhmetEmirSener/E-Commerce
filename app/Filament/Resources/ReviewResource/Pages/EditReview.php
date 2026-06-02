<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Services\ReviewService;

class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Yorumu Onayla')
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->visible(fn ($record) => $record->approved_at === null)
                ->requiresConfirmation()
    
                ->action(function ($record) {
                    app(ReviewService::class)->approve($record);
                    
                    $this->refreshFormData(['approved_at']); 

                    \Filament\Notifications\Notification::make()
                        ->title('Yorum Yayına Alındı!')
                        ->body('İlanın ortalama puanı ve toplam yorum sayısı başarıyla güncellendi.')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $review = $this->getRecord();

        if ($review->approved_at && $review->wasChanged('approved_at')) {
            app(\App\Services\ReviewService::class)->approve($review);
        }
    }
}
