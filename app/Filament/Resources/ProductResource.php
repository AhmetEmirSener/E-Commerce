<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Resources\ProductResource\RelationManagers\ProductImagesRelationManager;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Support\Enums\Alignment;

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
                        ->description('Ürünün bilgileri')
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

                            Forms\Components\Select::make('status')
                                ->label('Durum')
                                ->options([
                                    'aktif' => 'Aktif',
                                    'pasif' => 'Pasif',
                                    'beklemede' => 'Beklemede',
                                ])
                                ->required()
                                ->default('aktif'),
                        ])->columns(2),

                    // 2. ADIM: FİYAT VE KAMPANYA
                    Step::make('Fiyat ve İndirim')
                        ->description('Fiyat bilgileri')
                        ->icon('heroicon-o-ticket')
                        ->schema([
                            // SADECE OLUŞTURMA (CREATE) SAYFASINDA GÖRÜNECEK ALANLAR
                            Forms\Components\Grid::make(4)
                                ->schema([
                                    Forms\Components\Select::make('activeDiscount.discount_type')
                                        ->label('İndirim Türü')
                                        ->options([
                                            'percent' => 'Yüzdelik (%)',
                                            'fixed' => 'Sabit Tutar (TL)',
                                        ])
                                        ->required(fn ($get) => !blank($get('activeDiscount.discount_value')))
                                        ->native(false),

                                    Forms\Components\TextInput::make('activeDiscount.discount_value')
                                        ->label('İndirim Değeri')
                                        ->numeric()
                                        ->minValue(1)
                                        ->suffix(fn ($get) => $get('activeDiscount.discount_type') === 'percent' ? '%' : 'TL')
                                        ->placeholder('Örn: 20'),

                                    Forms\Components\DateTimePicker::make('activeDiscount.start_date')
                                        ->label('Başlangıç Tarihi')
                                        ->seconds(false)
                                        ->default(now()),

                                    Forms\Components\DateTimePicker::make('activeDiscount.end_date')
                                        ->label('Bitiş Tarihi')
                                        ->seconds(false),
                                ])
                                ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ProductResource\Pages\CreateProduct),
                                
                            // SADECE DÜZENLEME (EDIT) SAYFASINDA GÖRÜNECEK ALANLAR
                            Forms\Components\TextInput::make('price')
                                ->label('Fiyat')
                                ->required()
                                ->numeric()
                                ->prefix('₺')
                                ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ProductResource\Pages\EditProduct),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('current_active_discount')
                                        ->label('Mevcut Aktif İndirim')
                                        ->formatStateUsing(function ($record) {
                                            $discount = $record?->activeDiscount;
                                            if (! $discount || $discount->is_active == 0) return 'Bu üründe aktif bir indirim bulunmuyor';
                                            
                                            return $discount->discount_type === 'percent' 
                                                ? "%{$discount->discount_value} İndirim" 
                                                : "{$discount->discount_value} TL Sabit İndirim";
                                        })
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('discounted_price_info')
                                        ->label('İndirimli Satış Fiyatı')
                                        ->prefix('₺')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(function ($record) {
                                            $price = $record?->price;
                                            $discount = $record?->activeDiscount;

                                            if (! $discount || $discount->is_active == 0) {
                                                return number_format($price, 2, ',', '.') . ' TL (İndirim Yok)';
                                            }

                                            if ($discount->discount_type === 'percent') {
                                                $discountedPrice = $price - ($price * ($discount->discount_value / 100));
                                            } else {
                                                $discountedPrice = $price - $discount->discount_value;
                                            }

                                            return number_format(max(0, $discountedPrice), 2, ',', '.') . ' TL';
                                        }),
                                ])
                                ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ProductResource\Pages\EditProduct),

                                // BAĞIMSIZ DEV BUTONLAR
                                Forms\Components\Actions::make([
                                    // BUTON 1: KAMPANYA GEÇMİŞİ (MODAL)
                                    Forms\Components\Actions\Action::make('campaignHistory')
                                        ->label('Kampanya Geçmişini İncele')
                                        ->icon('heroicon-m-clock')
                                        ->color('gray')
                                        ->button()
                                        ->modalHeading('Ürünün Kampanya ve İndirim Geçmişi')
                                        ->modalWidth('5xl')
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('Kapat')
                                        ->form(function ($record) {
                                            $discounts = \App\Models\ProductDiscount::where('product_id', $record->id)
                                                ->latest()
                                                ->get()
                                                ->map(function ($discount) {
                                                    return [
                                                        'campaign' => $discount->campaign_id ? "#{$discount->campaign_id} Kampanya" : 'Direkt İndirim',
                                                        'type' => $discount->discount_type === 'percent' ? 'Yüzdelik (%)' : 'Sabit Tutar (TL)',
                                                        'value' => $discount->discount_type === 'percent' ? "%{$discount->discount_value}" : "₺{$discount->discount_value}",
                                                        'dates' => \Illuminate\Support\Carbon::parse($discount->start_date)->format('d/m/Y H:i') . ' - ' . 
                                                                   ($discount->end_date ? \Illuminate\Support\Carbon::parse($discount->end_date)->format('d/m/Y H:i') : 'Süresiz'),
                                                        'status' => ($discount->is_active == 1 && (! $discount->end_date || now()->lt($discount->end_date))) ? 'Aktif' : 'Pasif / Bitti',
                                                    ];
                                                })
                                                ->toArray();

                                            return [
                                                Forms\Components\Repeater::make('past_discounts')
                                                    ->label('Geçmiş İndirim Kayıtları')
                                                    ->default($discounts)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('campaign')->label('Kampanya'),
                                                        Forms\Components\TextInput::make('type')->label('İndirim Türü'),
                                                        Forms\Components\TextInput::make('value')->label('İndirim Değeri'),
                                                        Forms\Components\TextInput::make('dates')->label('Tarih Aralığı'),
                                                        Forms\Components\TextInput::make('status')->label('Durum'),
                                                    ])
                                                    ->columns(5)
                                                    ->disabled()
                                                    ->addable(false)
                                                    ->deletable(false)
                                                    ->reorderable(false)
                                                    ->columnSpanFull()
                                            ];
                                        }),

                                    // BUTON 2: KAMPANYAYA GİT
                                    Forms\Components\Actions\Action::make('goToCampaign')
                                        ->label('Kampanya Detayına Git')
                                        ->icon('heroicon-m-arrow-top-right-on-square')
                                        ->color('primary')
                                        ->button()
                                        ->visible(fn ($record) => $record?->activeDiscount !== null && $record->activeDiscount->is_active != 0)
                                        ->action(function ($record) {
                                            $campaignId = $record->activeDiscount?->campaign_id;
                                            if ($campaignId) {
                                                redirect()->to(\App\Filament\Resources\CampaignResource::getUrl('edit', ['record' => $campaignId]));
                                            }
                                        }),

                                    // BUTON 3: KAMPANYAYI KALDIR
                                    Forms\Components\Actions\Action::make('removeCampaign')
                                        ->label('Aktif Kampanyayı Sonlandır')
                                        ->icon('heroicon-m-trash')
                                        ->color('danger')
                                        ->button()
                                        ->visible(fn ($record) => $record?->activeDiscount !== null && $record->activeDiscount->is_active != 0)
                                        ->requiresConfirmation()
                                        ->modalHeading('Kampanyayı Kaldır')
                                        ->modalDescription('Bu üründeki aktif indirimi sonlandırmak istediğinize emin misiniz?')
                                        ->modalSubmitActionLabel('Evet, Kaldır')
                                        ->action(function ($record) {
                                            $discount = $record->activeDiscount;
                                            if ($discount) {
                                                $discount->update(['is_active' => 0]);
                                                \Filament\Notifications\Notification::make()->title('Kampanya Kaldırıldı')->success()->send();
                                            }
                                        }),
                                ])
                                ->columnSpanFull()
                                ->alignment(Alignment::Start)
                                ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ProductResource\Pages\EditProduct),
                        ]), // Step Sonu

                    // 3. ADIM: DETAYLAR VE STOK
                    Step::make('Detaylar')
                        ->description('Stok, ağırlık ve özellikler')
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

                    // 4. ADIM: GÖRSELLER (TERTEMİZ ESKİ USUL)
                    Step::make('Görseller')
                        ->description('Fotoğraf yükleyin')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\FileUpload::make('temporary_gallery')
                                ->label('Ürün Galerisi Görselleri')
                                ->image()
                                ->multiple()
                                ->directory('product-images')
                                ->panelLayout('grid')
                                ->reorderable()
                                ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ProductResource\Pages\CreateProduct)
                                ->columnSpanFull(),
                                
                            Forms\Components\FileUpload::make('image')
                                ->label('Ana Görsel (Otomatik Seçilir)')
                                ->image()
                                ->disabled() 
                                ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ProductResource\Pages\EditProduct),
                        ]),
                ])
                ->columnSpanFull()
                ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('price')->money('TRY')->sortable(),
                Tables\Columns\TextColumn::make('weight')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('stock')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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
            // ESKİ DOST GERİ GELDİ: İlişki yöneticisi en altta paşa paşa listelenecek kanka!
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