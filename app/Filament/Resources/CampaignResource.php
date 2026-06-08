<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Filament\Resources\CampaignResource\RelationManagers\ActiveProductDiscountsRelationManager;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Kampanyalar'; // Menüde şık dursun kanka

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3) // Sayfayı 3 sütuna bölüyoruz kanka
                    ->schema([
                        
                        // ======================================================================
                        // SOL TARAF: KAMPANYA GENEL BİLGİLERİ VE İNDİRİM AYARLARI (2 Sütun kaplasın)
                        // ======================================================================
                        Forms\Components\Group::make()
                            ->schema([
                                
                                // 1. KART: TEMEL BİLGİLER
                                Forms\Components\Card::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->label('Kampanya Adı')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Kampanya Aktif Mi?')
                                            ->required()
                                            ->default(true),
                                            
                                        Forms\Components\DateTimePicker::make('start_date')
                                            ->label('Başlangıç Tarihi')
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('end_date')
                                            ->label('Bitiş Tarihi')
                                            ->seconds(false),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\FileUpload::make('image')
                                                    ->label('Masaüstü Banner Görseli (Desktop)')
                                                    ->image()
                                                    ->directory('campaign-banners/desktop'),
                                                Forms\Components\FileUpload::make('mobile_image')
                                                    ->label('Mobil Banner Görseli (Mobile)')
                                                    ->image()
                                                    ->directory('campaign-banners/mobile')
                                            ]),
                                    ])->columns(2),

                                // 2. KART: İNDİRİM VE ÇAKIŞMA MOTURU AYARLARI (Unuttuğumuz canavar burası mq)
                                Forms\Components\Card::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('logic_title')
                                            ->label('⚙️ İndirim & Çakışma Yönetim Ayarları')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('discount_type')
                                            ->label('İndirim Türü')
                                            ->options([
                                                'percent' => 'Yüzdelik (%)',
                                                'fixed' => 'Sabit Tutar (TL)',
                                            ])
                                            ->required()
                                            ->disabledOn('edit')
                                            ->default('percent'),

                                        Forms\Components\TextInput::make('discount_value')
                                            ->label('İndirim Değeri')
                                            ->numeric()
                                            ->required()
                                            ->disabledOn('edit')
                                            ->placeholder('Örn: 20 veya 150'),

                                        Forms\Components\TextInput::make('priority')
                                            ->label('Kampanya Önceliği (Priority)')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->helperText('Sayı büyüdükçe öncelik artar. Çakışma anında arka plandaki Job buraya bakar.'),

                                        Forms\Components\Toggle::make('exclusive')
                                            ->label('Özel / Tekil Kampanya mı? (Exclusive)')
                                            ->inline(false)
                                            ->default(false)
                                            ->helperText('Aktif edilirse, bu kampanyadaki ürünler başka hiçbir kampanyaya dahil edilmez!'),
                                    ])->columns(2),
                            ])
                            ->columnSpan(2),

                        // ======================================================================
                        // SAĞ TARAF: KAMPANYA KURALLARI (RULES) (1 Sütun kaplasın)
                        // ======================================================================
                        Forms\Components\Card::make()
                            ->schema([
                                Forms\Components\Placeholder::make('rules_title')
                                    ->label('📋 Kampanya Kuralları / Limitleri'),

                                Forms\Components\Repeater::make('campaignRules')
                                    ->relationship('rules') // Modeldeki hasMany ilişkisi kanka
                                    ->schema([
                                        
                                        // 1. ADIM: KURAL ALANI
                                        Forms\Components\Select::make('field')
                                            ->label('Kural Alanı')
                                            ->options([
                                                'price' => 'Ürün Fiyatı',
                                                'category_id' => 'Belirli Kategori',
                                            ])
                                            ->required()
                                            ->live(), // Değişimi anında algılar mq

                                        // 2. ADIM: OPERATÖR
                                        Forms\Components\Select::make('operator')
                                            ->label('Karşılaştırma')
                                            ->options([
                                                '=' => 'Eşittir (=)',
                                                '>' => 'Büyüktür (>)',
                                                '<' => 'Küçüktür (<)',
                                                '>=' => 'Büyük Eşittir (>=)',
                                                '<=' => 'Küçük Eşittir (<=)',
                                                'in' => 'İçindeyse (IN)',
                                            ])
                                            ->required()
                                            ->default('='),

                                        // 3. ADIM: DİNAMİK DEĞERLER
                                        Forms\Components\Select::make('value')
                                            ->label('Kategori Seçin')
                                            ->visible(fn (Forms\Get $get) => $get('field') === 'category_id')
                                            ->relationship('category', 'name') // Category modeline bağlı
                                            ->searchable()
                                            ->preload()
                                            ->required(fn (Forms\Get $get) => $get('field') === 'category_id'),

                                        Forms\Components\TextInput::make('value')
                                            ->label('Fiyat Tutar Değeri')
                                            ->visible(fn (Forms\Get $get) => $get('field') === 'price' || !$get('field'))
                                            ->numeric()
                                            ->placeholder('Örn: 500')
                                            ->required(fn (Forms\Get $get) => $get('field') === 'price'),

                                    ])
                                    ->createItemButtonLabel('Yeni Kural Satırı Ekle')
                                    ->collapsible()
                                    ->default([]),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Web Banner'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Kampanya Adı')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Durum')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Başlangıç')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Bitiş')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Süresiz'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Kampanya Durumu')
                    ->options([
                        '1' => 'Sadece Aktifler',
                        '0' => 'Sadece Pasifler/Bitenler',
                    ])
                    ->placeholder('Tümü'),
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
            ActiveProductDiscountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            // KANKA DİKKAT: Burası EditReview kalmıştı patlardı, EditCampaign olarak düzelttim mq!
            'edit' => Pages\EditCampaign::route('/{record}/edit'), 
        ];
    }
}