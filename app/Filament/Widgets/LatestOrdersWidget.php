<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class LatestOrdersWidget extends BaseWidget
{
    protected static ?string $heading = 'Sipariş Operasyon Merkezi';

    // Widget sıralamasında bu tablo en altta dursun diye 3 veriyoruz
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
           ->query(
            Order::query()->latest('ordered_at')
        )
            ->filters([
                Tables\Filters\SelectFilter::make('filtre')
                    ->label('Sipariş Görünümü')
                    ->options([
                        'latest' => 'En Yeni Siparişler',
                        'bugun' => 'Bugünkü Siparişler',
                        'kargolanmamis' => 'Kargoya Verilmeyenler',
                    ])
                    ->default('bugun') // Panel açıldığında ilk bu seçili gelsin
                    ->query(function (Builder $query, array $data) {
                        // Filtre tamamen temizlendiyse tüm siparişleri serbest bırak kanka
                        if (! isset($data['value']) || blank($data['value'])) {
                            return $query->take(null); 
                        }

                        // Admin kutudan bir şey seçtiyse sorguyu büküyoruz:
                        return match ($data['value']) {
                            'latest' => $query->limit(5),
                            
                            'bugun' => $query
                                ->whereBetween('ordered_at', [Carbon::today(), Carbon::today()->endOfDay()])
                                ->whereNotIn('status', ['cancelled', 'pending', 'refunded'])
                                ->take(null),
                                
                            'kargolanmamis' => $query
                                ->whereIn('status', ['paid'])
                                ->take(null),
                                
                            default => $query
                        };
                    })
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Sipariş No')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Müşteri')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Toplam Tutar')
                    ->money('TRY')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    // Veritabanındaki İngilizce (paid, pending vs.) kodlara göre renk veriyoruz
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'paid'      => 'info',
                        'shipped'   => 'primary', // Kargoya verildi durumu
                        'completed' => 'success', // Teslim edildi durumu
                        'cancelled' => 'danger',
                        'refunded'  => 'danger',
                        default     => 'gray',
                    })
                    // Veritabanında 'paid' yazan şeyi admin paneline Türkçe 'Ödendi' diye basıyoruz kanka
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'   => 'Ödeme Bekliyor',
                        'paid'      => 'Ödendi (Kargo Bekliyor)',
                        'shipped'   => 'Kargoya Verildi',
                        'completed' => 'Teslim Edildi',
                        'cancelled' => 'İptal Edildi',
                        'refunded'  => 'İade Edildi',
                        default     => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('ordered_at')
                    ->label('Sipariş Tarihi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('İncele')
                    ->url(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-m-eye')
                    ->color('gray'),
            ]);
    }
}