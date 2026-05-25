<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class OrderStatsWidget extends BaseWidget
{
    // Sayfa açıldığında bu widget'ların kaç saniyede bir otomatik yenileneceğini seçebilirsin (Opsiyonel)
    protected static ?string $pollingInterval = '30s'; 
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Bugünün Tarih Sınırları
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        // 2. Bugünün Cirosu (Sadece iptal edilmemiş siparişlerin toplamı)
        $todayCiro = Order::whereBetween('ordered_at', [$todayStart, $todayEnd])
            ->where('status', '!=', 'İptal Edildi')
            ->sum('total');

        // 3. Bugünün Sipariş Sayısı
        $todayOrderCount = Order::whereBetween('ordered_at', [$todayStart, $todayEnd])->count();

        // 4. Bekleyen / Aksiyon Alınması Gereken Siparişler
        $pendingOrders = Order::whereIn('status', ['Sipariş alındı.', 'Hazırlanıyor'])->count();

        // 5. Kritik Stok Uyarı (Stoku 5 ve altı olan ürünler)
        $lowStockCount = Product::where('stock', '<=', 5)->count();

        return [
            // CİRO KARTI
            Stat::make('Bugünkü Ciro', '₺' . number_format($todayCiro, 2, ',', '.'))
                ->description('Bugün net kasaya giren tutar')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('success'),

            // SİPARİŞ SAYISI KARTI
            Stat::make('Bugünkü Sipariş', $todayOrderCount . ' Adet')
                ->description('Siteden geçilen siparişler')
                ->descriptionIcon('heroicon-m-shopping-cart', IconPosition::Before)
                ->color('info'),

            // BEKLEYEN SİPARİŞLER KARTI
            Stat::make('Bekleyen Siparişler', $pendingOrders . ' Sipariş')
                ->description('Kargolanmayı bekleyenler')
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->color($pendingOrders > 0 ? 'warning' : 'success'),

            // KRİTİK STOK KARTI
            Stat::make('Kritik Stoktaki Ürünler', $lowStockCount . ' Ürün')
                ->description('Stoku 5 ve altına düşenler')
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}