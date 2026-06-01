<?php

namespace App\Filament\Resources\CampaignResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActiveProductDiscountsRelationManager extends RelationManager
{
    // Senin yazdığın o özel ilişki metodunun adı kanka
    protected static string $relationship = 'activeProductDiscounts';

    // Tablonun üstünde yazacak başlık
    protected static ?string $title = 'Kampanyaya Dahil Aktif Ürünler';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // İhtiyacına göre buraya hızlı düzenleme alanları koyabilirsin kanka
                Forms\Components\TextInput::make('discount_value')
                    ->label('İndirim Değeri')
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Kanka senin yazdığın ->with('product.advert') optimizasyonunu buraya gömüyoruz:
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product.advert']))
            ->recordTitleAttribute('id')
            ->columns([
                // Ürün görselini ilişkiden çekip basıyoruz
                Tables\Columns\ImageColumn::make('product.image')
                    ->label('Ürün Fotoğrafı'),

                // Ürün adını çekiyoruz
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Ürün Adı')
                    ->searchable()
                    ->sortable(),

                // Ürünün ham fiyatı
                Tables\Columns\TextColumn::make('product.price')
                    ->label('Normal Fiyatı')
                    ->money('TRY'),

                // Bu kampanya altındaki indirim türü
                Tables\Columns\TextColumn::make('discount_type')
                    ->label('İndirim Türü')
                    ->formatStateUsing(fn ($state) => $state === 'percent' ? 'Yüzdelik (%)' : 'Sabit Tutar (TL)'),

                // İndirim değeri
                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Uygulanan İndirim')
                    ->formatStateUsing(fn ($state, $record) => $record->discount_type === 'percent' ? "%{$state}" : "₺{$state}"),
                
                // İlan (Advert) bilgisini de buraya şık bir badge olarak çakalım kanka admin görsün
                Tables\Columns\TextColumn::make('product.advert.title')
                    ->label('Reklam / İlan Başlığı')
                    ->placeholder('Reklam Yok')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                // Burada ürün bazlı filtre istersen koyarsın kanka
            ])
            ->headerActions([
                // Kampanyaya yeni ürün ekleme aksiyonları
            ])
           ->actions([
                // 1. DÜZENLEME BİTTİĞİ AN DB'Yİ DE TETİKLE KANKA
                Tables\Actions\EditAction::make()
                    ->label('Düzenle')
                    ->after(function ($record) {
                        // $record burada ProductDiscount modelidir.
                        // Satır güncellenince ilişkideki ürünü çekip senin o meşhur Job'a fırlatıyoruz!
                        $product = $record->product;
                        if ($product) {
                            \App\Jobs\UpdateCampaignDiscountJob::dispatch($product);
                        }
                    }),

                // 2. KAMPANYADAN SİLİNDİĞİ AN DB'DE KAMPANYA BAYRAĞINI İNDİR VE FİYATI ESKİYE ÇEK
                Tables\Actions\DeleteAction::make()
                    ->label('Kampanyadan Çıkar')
                    ->after(function ($record) {
                        $product = $record->product;
                        if ($product) {
                            $product->update(['is_campaign_on' => false]);
                            
                            // Ürün boşa düştüğü için Job'ı tetikleyip fiyatı orijinal haline çektiriyoruz kanka
                            \App\Jobs\UpdateCampaignDiscountJob::dispatch($product);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // 3. TOPLU SİLMEDE DE TÜM ÜRÜNLERİ DÖNGÜYLE AVLA KANKA
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($records) {
                            foreach ($records as $record) {
                                $product = $record->product;
                                if ($product) {
                                    $product->update(['is_campaign_on' => false]);
                                    \App\Jobs\UpdateCampaignDiscountJob::dispatch($product);
                                }
                            }
                        }),
                ]),
            ]);
    }
}