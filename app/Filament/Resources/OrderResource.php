<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Tabs;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Sipariş Detayları')
                    ->tabs([
                        
                        // 1. SEKME: GENEL BİLGİLER VE MÜŞTERİ
                        Tabs\Tab::make('Genel Bilgiler')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Select::make('user_id')
                                    ->label('Müşteri')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('status')
                                    ->label('Sipariş Durumu')
                                    ->options([
                                        'paid' => 'Sipariş Alındı',
                                        'pending' => 'Ödeme Alınamadı',
                                        'shipped' => 'Kargoya Verildi',
                                        'completed' => 'Teslim Edildi',
                                        'cancelled' => 'İptal Edildi',
                                    ])
                                    ->required()
                                    ->default('Sipariş alındı.'),

                                DateTimePicker::make('ordered_at')
                                    ->label('Sipariş Tarihi')
                                    ->default(now()),

                                TextInput::make('invoice')
                                    ->label('Fatura No')
                                    ->maxLength(255),
                            ])->columns(2),

                        // 2. SEKME: SİPARİŞ KALEMLERİ (ORDER ITEMS)
                        Tabs\Tab::make('Sipariş Ürünleri')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Repeater::make('items')
                                    ->relationship('orderItems')
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Ürün')
                                            ->relationship('product', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                                $set('price', Product::find($state)?->price ?? 0)
                                            )
                                            ->columnSpan(3),

                                        TextInput::make('quantity')
                                            ->label('Adet')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->columnSpan(1),

                                        TextInput::make('price')
                                            ->label('Birim Fiyat')
                                            ->numeric()
                                            ->prefix('₺')
                                            ->required()
                                            ->columnSpan(2),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull()
                                    ->label('Ürün Ekle/Çıkar')
                                    ->createItemButtonLabel('Yeni Ürün Ekle'),
                            ]),

                        // 3. SEKME: Sadece Adres ve Maliyet Kaldı kanka! (Kargo Detayları Alt Tabloya Gitti)
                        Tabs\Tab::make('Adres')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                          Forms\Components\Textarea::make('shipping_address')
                            ->label('Teslimat ve Fatura Adresi')
                            ->required()
                            ->columnSpanFull()
                            ->rows(4)
                            // Veritabanındaki array/json yapısını okunaklı düz metne çeviriyoruz kanka:
                            ->formatStateUsing(function ($state) {
                                if (!$state) return '';
                                
                                // Eğer veri veritabanından JSON string olarak geliyorsa array'e çeviriyoruz
                                $address = is_string($state) ? json_decode($state, true) : $state;

                                if (!is_array($address)) return $state;

                                // Senin gönderdiğin tam key yapılarına göre eşliyoruz:
                                $fullName = $address['full_name'] ?? '';
                                $phone = $address['phone_number'] ?? '';
                                $type = $address['address_type'] ?? 'Ev';
                                $line = $address['address_line'] ?? '';
                                $district = $address['state'] ?? ''; // Sende ilçe 'state' olarak tutuluyor kanka
                                $city = $address['city'] ?? '';
                                $postalCode = $address['postal_code'] ? " (PK: {$address['postal_code']})" : '';

                                // Çıktıyı adminin kargo etiketine şak diye yapıştırabileceği kıvama getiriyoruz:
                                return "Alıcı: {$fullName} - Tlf: {$phone} [{$type} Adresi]\n" .
                                    "Adres: {$line}\n" .
                                    "{$district} / {$city}{$postalCode}";
                            }),

                          
                            ]),

                        // 4. SEKME: ÖDEME VE PARASAL ÖZET
                        Tabs\Tab::make('Ödeme & Muhasebe')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                TextInput::make('subTotal')
                                    ->label('Ara Toplam')
                                    ->required()
                                    ->numeric()
                                    ->prefix('₺'),

                                TextInput::make('discount_total')
                                    ->label('İndirim Toplamı')
                                    ->numeric()
                                    ->prefix('₺')
                                    ->default(0),
                                          TextInput::make('cargo_fee')
                                    ->label('Kargo Ücreti')
                                    ->required()
                                    ->numeric()
                                    ->prefix('₺')
                                    ->default(0.00),

                                TextInput::make('total')
                                    ->label('Genel Toplam')
                                    ->required()
                                    ->numeric()
                                    ->prefix('₺'),
                                    
                                    Forms\Components\Section::make('Iyzico / Ödeme Ağ Geçidi Detayları')
                                    ->relationship('payment') 
                                    ->schema([
                                        TextInput::make('status')
                                            ->label('Ödeme Durumu')
                                            ->placeholder('Örn: SUCCESS, FAILURE')
                                            ->disabled(), // Admin elle değiştiremesin, sadece görsün

                                        TextInput::make('provider_payment_id')
                                            ->label('Iyzico İşlem No (Provider ID)')
                                            ->disabled(),

                                        Select::make('payment_method')
                                            ->label('Ödeme Kanalı')
                                            ->options([
                                                'CREDIT_CARD' => 'Kredi Kartı',
                                                'DEBIT_CARD' => 'Banka Kartı',
                                            ])
                                            ->disabled(),

                                        TextInput::make('card_bank')
                                            ->label('Kart Tipi')
                                            ->placeholder('Visa, Mastercard, Troy vb.')
                                            ->disabled(),
                                    ])->columns(2)->columnSpanFull(),
                                
                            ])->columns(3),

                            
                    ])
                    ->columnSpanFull(),

                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Sipariş No')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Müşteri')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ordered_at')
                    ->label('Tarih')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Toplam Tutar')
                    ->money('TRY')
                    ->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Durum')
                    ->options([
                         'paid' => 'Sipariş Alındı',
                                        'pending' => 'Ödeme Alınamadı',
                                        'shipped' => 'Kargoya Verildi',
                                        'completed' => 'Teslim Edildi',
                                        'cancelled' => 'İptal Edildi',
                    ])
                    ->disabled()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Sipariş Durumu')
                    ->options([
                          'paid' => 'Sipariş Alındı',
                                        'pending' => 'Ödeme Alınamadı',
                                        'shipped' => 'Kargoya Verildi',
                                        'completed' => 'Teslim Edildi',
                                        'cancelled' => 'İptal Edildi',
                    ])
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

    // CRITICAL DOKUNUŞ: Kargo İlişki Yöneticisini Buraya Bağladık
    public static function getRelations(): array
    {
        return [
            RelationManagers\OrderCargoDetailRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}