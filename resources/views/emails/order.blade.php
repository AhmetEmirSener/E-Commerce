<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siparişiniz Alındı - YunusPet</title>
    <style>
        /* Temel E-posta Sıfırlamaları */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }

        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .email-wrapper { width: 100%; background-color: #f5f5f5; padding: 40px 20px; box-sizing: border-box;    }
        .card-container { max-width: 550px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; border: 1px solid #e5e7eb; overflow: hidden; }

        /* HEADER */
        .card-header { padding: 16px 24px; border-bottom: 1px solid #f3f4f6; text-align: center; }
        .logo-text { font-size: 20px; font-weight: 700; color: #ff6000; letter-spacing: -0.5px; text-decoration: none; }
        .logo-text span { color: #1f2937; }

        /* BAŞARI BANNERI */
        .success-banner { padding: 30px 24px 20px; text-align: center; }
        .success-icon { font-size: 40px; line-height: 1; margin: 0 0 15px 0; }
        .success-title { font-size: 22px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0; }
        .success-text { font-size: 14px; color: #6b7280; margin: 0; line-height: 1.5; }

        .card-body { padding: 0 24px 30px; }

        /* BİLGİ KARTLARI (Adres & Ödeme) */
        .info-grid { width: 100%; margin-bottom: 24px; }
        .info-cell { width: 50%; vertical-align: top; padding: 16px; background-color: #f9fafb; border-radius: 8px; border: 1px solid #f3f4f6; }
        .info-spacer { width: 12px; } /* İki kutu arası boşluk */
        .info-title { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 8px 0; }
        .info-content { font-size: 13px; color: #1f2937; line-height: 1.5; margin: 0; }
        .info-content strong { font-weight: 600; }

        /* SİPARİŞ ÖZETİ BÖLÜMÜ */
        .section-title { font-size: 15px; font-weight: 600; color: #1f2937; margin: 0 0 16px 0; border-bottom: 2px solid #f3f4f6; padding-bottom: 8px; }
        
        /* ÜRÜN LİSTESİ */
        .product-table { width: 100%; margin-bottom: 20px; }
        .product-row td { padding: 12px 0; border-bottom: 1px solid #f9fafb; vertical-align: top; }
        .img-cell { width: 60px; padding-right: 12px; }
        .product-img { width: 60px; height: 60px; border-radius: 6px; border: 1px solid #e5e7eb; object-fit: contain; background-color: #ffffff; }
        .name-cell { width: auto; vertical-align: top; padding-top: 4px; }
        .product-name { font-size: 13px; font-weight: 500; color: #1f2937; margin: 0 0 4px 4px; line-height: 1.4; }
        .product-qty { font-size: 12px; color: #6b7280; margin: 0; margin-left: 4px }
        .price-cell { width: 100px; text-align: right; vertical-align: top; padding-top: 4px; }
        .original-price { font-size: 11px; color: #9ca3af; text-decoration: line-through; display: block; margin-bottom: 2px; }
        .current-price { font-size: 14px; font-weight: 600; color: #1f2937; display: block; }

        /* FİYAT ÖZETİ */
        .totals-table { width: 100%; max-width: 300px; margin-left: auto; }
        .totals-row td { padding: 8px 0; font-size: 13px; color: #6b7280; text-align: right; }
        .totals-row td:first-child { text-align: left; }
        .discount-row td { color: #10b981; } /* İndirim satırı yeşil olsun */
        .grand-total td { padding-top: 12px; font-size: 16px; font-weight: 700; color: #1f2937; border-top: 1px solid #e5e7eb; }
        .grand-total .total-price { color: #10b981; }

        /* FOOTER */
        .card-footer { padding: 20px 24px; background-color: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center; }
        .footer-text { font-size: 12px; color: #9ca3af; line-height: 1.5; margin: 0; }
        .footer-link { color: #3b82f6; text-decoration: none; font-weight: 500; }

        @media only screen and (max-width: 480px) {
            .email-wrapper { padding: 20px 10px; }
            .info-cell { display: block; width: 100%; box-sizing: border-box; margin-bottom: 12px; }
            .info-spacer { display: none; }
            .totals-table { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="card-container">
            
            <!-- HEADER -->
            <div class="card-header">
                <a href="{{ url('/') }}" style="text-decoration: none;">
                    <img src="{{ asset('favLast.png') }}" 
                         alt="YunusPet" 
                         width="120" 
                         style="display: block; margin: 0 auto; border: 0; width: 120px; height: auto;">
                </a>
            </div>

            <!-- SUCCESS BANNER -->
            <div class="success-banner">
                <h1 class="success-title">Siparişiniz Alındı!</h1>
                <p class="success-text">
                    Teşekkür ederiz <strong>{{$order->user->name }}</strong>. Siparişinizi aldık ve hazırlamaya başladık. <br> 
                    Sipariş No: <strong style="color:#3b82f6;">#{{ $order->id }}</strong>
                </p>
            </div>

            <div class="card-body">
                
                <!-- ADRES VE ÖDEME BİLGİLERİ -->
                <table class="info-grid">
                    <tr>
                        <td class="info-cell">
                            <p class="info-title">Teslimat Adresi</p>
                            <p class="info-content">
                                <strong>{{ $order->shipping_address['full_name']  }}</strong><br>
                                {{ $order->shipping_address['address_line'] }}<br>
                                {{ $order->shipping_address['state'] }} / {{ $order->shipping_address['city']  }}<br>
                                {{ $order->shipping_address['phone_number']}}
                            </p>
                        </td>
                        <td class="info-spacer"></td>
                        <td class="info-cell">
                            <p class="info-title">Ödeme Yöntemi</p>
                            <p class="info-content">
                                {{ $order->payment->card_bank ?? 'Banka' }} - Sonu: **** {{ $order->payment->last_four }}<br>
                                {{ $order->payment->installment_count == 1 ? 'Tek Çekim' : $order->payment->installment_count . ' Taksit' }}
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- SİPARİŞ ÖZETİ BAŞLIĞI -->
                <h2 class="section-title">Sipariş Özeti</h2>

                <!-- ÜRÜNLER DÖNGÜSÜ -->
                <table class="product-table">
                    @foreach($order->orderItems as $item)
                    <tr class="product-row">
                        <td class="img-cell">
                            <!-- Eğer API'den tam URL gelmiyorsa başına url('/') veya env('APP_URL') koyabilirsin -->
                            <img src="{{ asset($item->product->image) }}" alt="{{ $item->product->name }}" class="product-img">
                        </td>
                        <td class="name-cell">
                            <p class="product-name">{{ $item->product->name }}</p>
                            <p class="product-qty">Adet: {{ $item->quantity }}</p>
                        </td>
                        <td class="price-cell">
                            <!-- İndirim varsa üstte çizili şekilde original_price göster -->
                            @if($item->original_price > $item->price)
                                <span class="original-price">{{ number_format($item->original_price * $item->quantity, 2, ',', '.') }} ₺</span>
                            @endif
                            <!-- Güncel Fiyat -->
                            <span class="current-price">{{ number_format($item->total, 2, ',', '.') }} ₺</span>
                        </td>
                    </tr>
                    @endforeach
                </table>

                <!-- FİYAT TOPLAMLARI -->
                <table class="totals-table">
                    <tr class="totals-row">
                        <td>Ara Toplam:</td>
                        <td>{{ number_format($order->subTotal, 2, ',', '.') }} ₺</td>
                    </tr>
                    
                    <!-- Sadece indirim varsa bu satırı göster -->
                    @if($order->discount_total > 0)
                    <tr class="totals-row discount-row">
                        <td>İndirimler:</td>
                        <td>-{{ number_format($order->discount_total, 2, ',', '.') }} ₺</td>
                    </tr>
                    @endif

                    <tr class="totals-row">
                        <td>Kargo Ücreti:</td>
                        <td>
                            @if($order->cargo_fee == 0)
                                Ücretsiz
                            @else
                                {{ number_format($order->cargo_fee, 2, ',', '.') }} ₺
                            @endif
                        </td>
                    </tr>
                    
                    <tr class="totals-row grand-total">
                        <td>Genel Toplam:</td>
                        <td class="total-price">{{ number_format($order->total, 2, ',', '.') }} ₺</td>
                    </tr>
                </table>

            </div>

            <!-- FOOTER -->
            <div class="card-footer">
                <p class="footer-text">
                    Siparişiniz kargoya verildiğinde size tekrar haber vereceğiz. <br> 
                    Sorularınız için <a href="{{ url('/iletisim') }}" class="footer-link">Müşteri Hizmetleri</a> ile iletişime geçebilirsiniz.
                </p>
            </div>

        </div>
    </div>
</body>
</html>