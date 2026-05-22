<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertResource\Pages;
use App\Models\Advert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AdvertResource extends Resource
{
    protected static ?string $model = Advert::class;

    // Menüdeki ikonu daha uygun bir şeye çevirdik (Megafon/İlan)
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'İlanlar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. Temel Bilgiler Bölümü
                Forms\Components\Section::make('İlan Bilgileri')->schema([
                    // Model ilişkilerini kullanarak Select yaptık
                        Forms\Components\Select::make('product_id')
                        ->label('Ürün')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live() // Alanın anlık olarak dinlenmesini sağlar
                        ->afterStateUpdated(function (\Filament\Forms\Set $set, ?string $state) {
                            if ($state) {
                                $product = \App\Models\Product::find($state);
                                if ($product && $product->category_id) {
                                    $set('category_id', $product->category_id);
                                }
                            } else {
                                $set('category_id', null);
                            }
                    }),

                    Forms\Components\Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->dehydrated()
                        ->required(),

                    Forms\Components\TextInput::make('title')
                        ->label('İlan Başlığı')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),

                   

          
                    Forms\Components\Select::make('status')
                        ->label('Durum')
                        ->options([
                            'Aktif' => 'Aktif',
                            'Beklemede' => 'Beklemede',
                            'Pasif' => 'Pasif',
                            'Süresi_doldu' => 'Süresi Doldu',
                        ])
                        ->required()
                        ->default('aktif'),

                  

                    Forms\Components\Toggle::make('is_featured')
                        ->label('Öne Çıkan İlan')
                        ->default(false),
                        
                    // İlanlarda genelde zengin metin editörü daha şık durur
                    Forms\Components\Textarea::make('description')
                        ->label('Açıklama')
                        ->columnSpanFull(),
                ])->columns(2),

                // 2. Medya Bölümü
                /*
                Forms\Components\Section::make('Görseller')->schema([
                    // Textarea yerine çoklu dosya yükleyici
                    Forms\Components\FileUpload::make('images')
                        ->label('İlan Görselleri')
                        ->image()
                        ->multiple()
                        ->panelLayout('grid')
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
                */

                // 3. İstatistikler Bölümü (Daha düzenli görünmesi için katlanabilir yaptık)
                Forms\Components\Section::make('İstatistikler')
                    ->description('Bu alanlar sistem tarafından otomatik güncellenir.')
                    ->schema([
                        Forms\Components\TextInput::make('views')
                            ->label('Görüntülenme')
                            ->numeric()
                            ->default(0)
                            ->disabled(), // Adminin elle views girmesini engeller

                        Forms\Components\TextInput::make('avg_rating')
                            ->label('Ortalama Puan')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('rating_sum')
                            ->label('Toplam Puan')
                            ->numeric()
                            ->default(0)
                            ->disabled(),

                        Forms\Components\TextInput::make('total_comments')
                            ->label('Yorum Sayısı')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])->columns(4)->collapsed(), // Sayfa açılışında kapalı gelsin
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ID yerine ilişki üzerinden ürün ve kategori isimlerini çekiyoruz
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Ürün')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable(),

         

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Beklemede' => 'warning',
                        'Pasif' => 'danger',
                        'süresi_doldu' => 'gray',
                        default => 'primary',
                    }),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Öne Çıkan')
                    ->boolean(),

                Tables\Columns\TextColumn::make('views')
                    ->label('Görüntülenme')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Tablo kalabalık olmasın diye gizledik

            ])
            ->filters([
                // İstersen buraya "Sadece Aktif İlanlar" filtresi ekleyebilirsin
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // Silme butonu ekledik
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdverts::route('/'),
            'create' => Pages\CreateAdvert::route('/create'),
            'edit' => Pages\EditAdvert::route('/{record}/edit'),
        ];
    }
}
