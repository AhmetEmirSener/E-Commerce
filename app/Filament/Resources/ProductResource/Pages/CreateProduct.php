<?php

namespace App\Filament\Resources\ProductResource\Pages;
use App\Services\SlugCreateService;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    public array $galleryImages = [];


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = app(SlugCreateService::class)->createSlug($data, \App\Models\Product::class);

        if (isset($data['temporary_gallery'])) {
            // Resimleri class'ın içindeki değişkene atıyoruz
            $this->galleryImages = $data['temporary_gallery']; 
            
            // SQL "temporary_gallery diye bir sütun yok" demesin diye diziden uçuruyoruz
            unset($data['temporary_gallery']); 
        }

        return $data;
    }


    protected function afterCreate(): void
    {
        $product = $this->record; // Yeni oluşan ürünümüz

        // Eğer galeriye resim yüklendiyse
        if (!empty($this->galleryImages)) {
            foreach ($this->galleryImages as $index => $path) {
                
                // İlk sıradaki resmi otomatik ana görsel yapıyoruz
                $isMain = ($index === 0);

                // product_images tablosuna resmi yazıyoruz
                $product->images()->create([
                    'path' => $path,
                    'title' => $product->name . ' - Görsel ' . ($index + 1),
                    'sort' => $index + 1,
                    'is_main' => $isMain,
                ]);

                // Eğer ana görselse, products tablosundaki 'image' alanını da güncelliyoruz
                if ($isMain) {
                    $product->update(['image' => $path]);
                }
            }
        }
    }
}
