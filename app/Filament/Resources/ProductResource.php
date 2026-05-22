<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;

use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $featureOptions = [
        'tahilsiz' => 'Tahılsız',
        'kisir-destegi' => 'Kısır Desteği',
        'premium' => 'Premium',
        'hassas-sindirim' => 'Hassas Sindirim',
        'kisirlestirilmis' => 'Kısırlaştırılmış Kediler İçin',
        'ideal-kilo' => 'İdeal Kilo Kontrolü',
        'dengeli' => 'Dengeli Beslenme',
        'dusuk-yagli' => 'Düşük Yağ Oranı',
        'idrar-destegi' => 'İdrar Yolu Sağlığı',
        'yuksek-protein' => 'Yüksek Protein',
        'parlak-tuy' => 'Parlak Tüy Desteği',
        'bagisiklik' => 'Bağışıklık Güçlendirici',
        'indoor' => 'Ev Kedileri İçin',
        'tuy-topagi' => 'Tüy Yumağı Kontrolü',
        'koku-kontrol' => 'Dışkı Koku Kontrolü',
        'pirincli' => 'Pirinçli',
        'ekonomik' => 'Ekonomik Paket',
        'tavuklu' => 'Tavuklu',
        'serbest-gezen-tavuk' => 'Serbest Gezen Tavuk',
        'coklu-balik' => '6 Çeşit Balık',
        'omega-3' => 'Omega 3 & 6',
        'kas' => 'Kas Gelişimi',
        'veteriner' => 'Veteriner Formülü',
        'kalp' => 'Kalp Sağlığı',
        'tartar' => 'Diş ve Ağız Sağlığı',
        ];

        return $form
           ->schema([
   Wizard::make([
                // 1. ADIM: TEMEL BİLGİLER
                Step::make('Temel Bilgiler')
                    ->description('Ürünün adı, fiyatı ve kategorisi')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Ürün Adı')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('price')
                            ->label('Fiyat')
                            ->required()
                            ->numeric()
                            ->prefix('₺'),

                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options([
                                'aktif' => 'Aktif',
                                'pasif' => 'Pasif',
                                'beklemede' => 'Beklemede',
                            ])
                            ->required()
                            ->default('aktif'),
                    ])->columns(2), // Bu adımın içindeki inputları yan yana dizer

                // 2. ADIM: DETAYLAR VE STOK
                Step::make('Detaylar')
                    ->description('Stok, ağırlık ve ürün özellikleri')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\TextInput::make('stock')
                            ->label('Stok')
                            ->required()
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('weight')
                            ->label('Ağırlık (kg)')
                            ->numeric(),

                        Forms\Components\Select::make('features')
                            ->label('Özellikler')
                            ->multiple()
                            ->options($featureOptions)
                            ->searchable()
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                if (!$state) return [];
                                return collect($state)->pluck('key')->toArray();
                            })
                            ->dehydrateStateUsing(function ($state) use ($featureOptions) {
                                if (!$state) return [];
                                return collect($state)->map(function ($key) use ($featureOptions) {
                                    return ['key' => $key, 'label' => $featureOptions[$key] ?? $key];
                                })->values()->toArray();
                            }),
                    ])->columns(2),

                // 3. ADIM: GÖRSELLER (Geçici Galeri)
                Step::make('Görseller')
                    ->description('Ürün fotoğraflarını yükleyin')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        // Sadece "Create" (Oluşturma) sayfasında gösterilecek galeri alanı
                        Forms\Components\FileUpload::make('temporary_gallery')
                            ->label('Ürün Galerisi Görselleri')
                            ->image()
                            ->multiple()
                            ->directory('product-images')
                            ->panelLayout('grid')
                            ->reorderable()
                            // Sadece oluştururken göster kuralı:
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ProductResource\Pages\CreateProduct)
                            ->columnSpanFull(),
                            
                        // Edit sayfasında görünecek ana görsel alanı
                        Forms\Components\FileUpload::make('image')
                            ->label('Ana Görsel (Otomatik Seçilir)')
                            ->image()
                            ->disabled() // Adminin buradan elle değiştirmesine gerek yok, RelationManager'dan yönetecek
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ProductResource\Pages\EditProduct),
                    ]),
            ])
            ->columnSpanFull() // Wizard'ın ekranı tam kaplaması için şart
            ->skippable(), // Adminin adımlar arasında serbestçe geçmesini sağlar (opsiyonel)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
             
                /*
                Tables\Columns\TextColumn::make('brand_id')
                    ->numeric()
                    ->sortable(),
                */
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('campaign_id')
                    ->numeric()
                    ->sortable(),
              
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('weight')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductImagesRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }


}
