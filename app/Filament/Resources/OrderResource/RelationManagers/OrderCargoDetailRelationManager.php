<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrderCargoDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'orderCargoDetails'; // Order modelindeki hasOne ilişkisi

    protected static ?string $title = 'Kargo ve Paket Detayları';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cargo_company')
                    ->label('Kargo Firması')
                    ->options([
                        'yurtiçi' => 'Yurtiçi Kargo',
                        'aras' => 'Aras Kargo',
                        'mng' => 'MNG Kargo',
                    ])->required(),

                Forms\Components\TextInput::make('tracking_code')
                    ->label('Takip Kodu'),

                // İŞTE BURASI: Kargonun içindeki ürünler (CargoItems)
                // OrderCargoDetail modelinde "public function cargoItems() { return $this->hasMany(CargoItem::class); }" olmalı kanka
         Forms\Components\Repeater::make('cargoItems')
                ->relationship('cargoItems') 
                ->label('Bu Paketteki Ürünler')
                ->schema([
                    // 1. DÜZENLEME: Alan adını veritabanındaki gibi 'order_item_id' yaptık kanka
                    Forms\Components\Select::make('order_item_id')
                        ->label('Ürün')
                        ->required()
                        ->columnSpan(4)
                        ->searchable()
                        ->preload()
                        // 2. DÜZENLEME: CargoItem modelindeki 'orderItem' ilişkisini hedef alıyoruz.
                        // Ekranda da o satıra bağlı ürünün adını (product.name) gösteriyoruz.
                        ->relationship(
                            name: 'orderItem', 
                            titleAttribute: 'id', // Geçici olarak id veriyoruz, aşağıda modifyQueryUsing içinde name'e çevireceğiz
                            modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query, RelationManager $livewire) {
                                $order = $livewire->getOwnerRecord();

                                // Sadece bu siparişe ait olan order_items satırlarını getir
                                // Ve eager loading (with) ile product ilişkisini yükle ki performans düşmesin
                                return $query->with('product')
                                    ->where('order_id', $order->id);
                            }
                        )
                        // 3. SİHİRLİ DOKUNUŞ: Seçenek listesinde 'id' yerine ürünün adını yazdırıyoruz kanka
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->product?->name ?? "Ürün Bulunamadı (#{$record->id})"),
                        
                    Forms\Components\TextInput::make('quantity')
                        ->label('Kargolanan Adet')
                        ->numeric()
                        ->default(1)
                        ->required()
                        ->columnSpan(2),
                ])->columns(6)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {

        $isAllShipped = function (RelationManager $livewire): bool {
        $order = $livewire->getOwnerRecord();

        // 1. Siparişte toplam kaç adet ürün istendiğini buluyoruz
        $totalOrderedQuantity = $order->orderItems()->sum('quantity');

        // 2. Bu siparişe bağlı oluşturulmuş kargoların içindeki (cargo_items) toplam kargolanan adedi buluyoruz
        // orderCargoDetails -> cargoItems ilişkisi üzerinden sum çekiyoruz kanka
        $totalShippedQuantity = \App\Models\CargoItem::whereIn(
            'order_cargo_detail_id', 
            $order->orderCargoDetails()->pluck('id') // Siparişe ait tüm kargo paketlerinin ID'leri
        )->sum('quantity');

        // Eğer kargolanan miktar, sipariş edilene eşit veya büyükse true döner (Yani her şey kargolanmıştır)
        return $totalShippedQuantity >= $totalOrderedQuantity;
        };


        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cargo_company')->label('Kargo Firması'),
                Tables\Columns\TextColumn::make('tracking_code')->label('Takip Kodu'),
                // Kaç çeşit ürün olduğunu saydırabiliriz kanka:
                Tables\Columns\TextColumn::make('cargo_items_count')->counts('cargoItems')->label('Paketteki Ürün Çeşidi'),
            ])
            ->headerActions([
                // Eğer kargo kaydı yoksa oluşturma butonu çıkar
                Tables\Actions\CreateAction::make()
                ->label('Yeni Kargo Paketi Oluştur')
                // SİHİRLİ DOKUNUŞ: Eğer tüm ürünler kargolandıysa bu butonu gizle kanka!
                ->visible(fn (RelationManager $livewire) => !$isAllShipped($livewire))
                ->after(function (RelationManager $livewire) {
                    // Üstteki ana sipariş kaydını çekiyoruz
                    $order = $livewire->getOwnerRecord();
                    
                    // Sipariş durumunu 'shipped' olarak güncelliyoruz kanka
                    $order->update([
                        'status' => 'shipped'
                    ]);
                }),
                
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->after(function (RelationManager $livewire) {
                    $livewire->getOwnerRecord()->update([
                        'status' => 'shipped'
                    ]);
                }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}