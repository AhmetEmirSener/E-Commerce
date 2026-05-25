<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Aylık Ciro Analizi';
    
    // Grafiğin dashboard'da kaplayacağı genişlik (Full ekran olması için 2 veya full yapabilirsin kanka)
    protected static ?string $maxHeight = '300px';
    protected static ?int $sort = 2; // Ortada bu görünecek
    protected function getData(): array
    {
        $months = [];
        $totals = [];

        // Son 12 ayı geriye doğru hesaplayıp döngüye alıyoruz kanka
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            
            // Grafiğin altındaki X ekseni etiketleri (Örn: Oca 2026, Şub 2026)
            $months[] = $month->translatedFormat('M Y'); 

            // O aya ait toplam ciroyu (İptal edilmemiş olanları) veritabanından çekiyoruz
            $monthlyCiro = Order::whereMonth('ordered_at', $month->month)
                ->whereYear('ordered_at', $month->year)
                ->where('status', '!=', 'İptal Edildi')
                ->sum('total');

            $totals[] = $monthlyCiro;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Aylık Ciro (₺)',
                    'data' => $totals,
                    // Grafiğin çizgi ve dolgu renkleri (YunusPet premium temasına uygun şık bir yeşil/mavi tonu)
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => 'start', // Çizginin altını hafifçe doldurur (Area Chart yapar)
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        // Grafiğin tipi: 'line' (çizgi), 'bar' (sütun) veya 'pie' (pasta) olabilir. Line en profesyonel duranıdır.
        return 'line';
    }
}