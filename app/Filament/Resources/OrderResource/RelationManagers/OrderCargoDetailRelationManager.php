<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrderCargoDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'orderCargoDetails';

    protected static ?string $title = 'Kargo ve Paket Detayları';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
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

                        // ======================================================================
                        // SİHİRLİ DOKUNUŞ: 4'LÜ PROFESYONEL KARGO STATÜSÜ MQ!
                        // ======================================================================
                        Forms\Components\Select::make('status')
                            ->label('Kargo Durumu')
                            ->options([
                                'preparing' => 'Hazırlanıyor / Paketlendi',
                                'shipped'   => 'Kargoya Verildi (Şubede)',
                                'in_transit'=> 'Yolda / Transfer Merkezinde',
                                'delivered' => 'Teslim Edildi',
                            ])
                            ->required()
                            ->default('preparing')
                            ->live() // Durum değiştiğinde form anında tepki versin kanka
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // Sadece kargoya ilk verildiği anı yakalayıp tarihi basalım
                                if ($state === 'shipped' && !$get('shipped_at')) {
                                    $set('shipped_at', now()->format('Y-m-d H:i'));
                                } 
                                // Teslim edildiği an teslimat tarihini mermi gibi basalım
                                elseif ($state === 'delivered') {
                                    $set('delivered_at', now()->format('Y-m-d H:i'));
                                }
                            }),

                        Forms\Components\DateTimePicker::make('shipped_at')
                            ->label('Kargoya Verilme Tarihi')
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('Teslim Edilme Tarihi')
                            ->seconds(false),
                    ]),

                Forms\Components\Repeater::make('cargoItems')
                    ->relationship('cargoItems') 
                    ->label('Bu Paketteki Ürünler')
                    ->schema([
                        Forms\Components\Select::make('order_item_id')
                            ->label('Ürün')
                            ->required()
                            ->columnSpan(4)
                            ->searchable()
                            ->preload()
                            ->relationship(
                                name: 'orderItem', 
                                titleAttribute: 'id', 
                                modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query, RelationManager $livewire) {
                                    $order = $livewire->getOwnerRecord();
                                    return $query->with('product')
                                        ->where('order_id', $order->id);
                                }
                            )
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
            $totalOrderedQuantity = $order->orderItems()->sum('quantity');

            $totalShippedQuantity = \App\Models\CargoItem::whereIn(
                'order_cargo_detail_id', 
                $order->orderCargoDetails()->pluck('id') 
            )->sum('quantity');

            return $totalShippedQuantity >= $totalOrderedQuantity;
        };

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cargo_company')->label('Kargo Firması'),
                Tables\Columns\TextColumn::make('tracking_code')->label('Takip Kodu'),
                
                // Durumu tabloda renk cümbüşüyle gösterelim kanka:
                Tables\Columns\TextColumn::make('status')
                    ->label('Kargo Durumu')
                    ->badge()
                    ->colors([
                        'gray'    => 'preparing',
                        'warning' => 'shipped',
                        'info'    => 'in_transit', // Yolda olanlara mavi (info) çok şık durur mq
                        'success' => 'delivered',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'preparing'  => 'Hazırlanıyor',
                        'shipped'    => 'Kargoya Verildi',
                        'in_transit' => 'Yolda / Transfer',
                        'delivered'  => 'Teslim Edildi',
                        default      => $state,
                    }),

                Tables\Columns\TextColumn::make('cargo_items_count')->counts('cargoItems')->label('Paketteki Ürün Çeşidi'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Yeni Kargo Paketi Oluştur')
                    ->visible(fn (RelationManager $livewire) => !$isAllShipped($livewire))
                    ->after(function (RelationManager $livewire, $record) {
                        $order = $livewire->getOwnerRecord();
                        
                        // Kanka yeni paket oluşturulurken durumunu kontrol edip ana siparişi büküyoruz:
                        $orderStatus = $order->status; // Varsayılan olarak siparişin mevcut durumunu tutalım

                        if (in_array($record->status, ['shipped', 'in_transit'])) {
                            $orderStatus = 'shipped';
                        } elseif ($record->status === 'delivered') {
                            $orderStatus = 'delivered';
                        }

                        // Sadece siparişin durumu değişmesi gerekiyorsa update atalım ki gereksiz sorgu olmasın mq
                        if ($order->status !== $orderStatus) {
                            $order->update(['status' => $orderStatus]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function (RelationManager $livewire, $record) {
                        $order = $livewire->getOwnerRecord();
                        
                        // Aynı akıllı lojistik burada da çalışıyor kanka:
                        $orderStatus = $order->status;

                       if ($record->status === 'preparing') {
                            $orderStatus = 'paid';
                        } elseif (in_array($record->status, ['shipped', 'in_transit'])) {
                            $orderStatus = 'shipped';
                        } elseif ($record->status === 'delivered') {
                            $orderStatus = 'completed';
                        }

                        if ($order->status !== $orderStatus) {
                            $order->update(['status' => $orderStatus]);
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}