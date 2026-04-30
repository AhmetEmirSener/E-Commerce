<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YunusPet Doğrulama</title>
    <style>
        /* E-posta Client Resetleri */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5; /* Senin body arkaplanın */
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        
        .email-wrapper {
            width: 100%;
            background-color: #f5f5f5;
            padding: 40px 20px;
        }

        .card-container {
            max-width: 480px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px; /* Senin .card border-radius'un */
            border: 1px solid #e5e7eb; /* Senin genel border rengin */
            overflow: hidden;
        }

        /* NAVBAR TARZI HEADER */
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            text-align: center;
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #ff6000; /* Senin nav-brand rengin */
            letter-spacing: -0.5px;
            text-decoration: none;
            display: inline-block;
        }

        .logo-text span {
            color: #1f2937; /* Senin span rengin */
        }

        /* İÇERİK ALANI */
        .card-body {
            padding: 30px 24px;
            text-align: center;
        }

        .content-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 12px 0;
        }

        .content-text {
            font-size: 13px;
            color: #6b7280; /* Senin address-text / input-label rengin */
            line-height: 1.6;
            margin: 0 0 24px 0;
        }

        /* OTP KUTUSU (Senin input alanları tarzında) */
        .otp-wrapper {
            background-color: #f9fafb; /* Senin ürün kartları arkaplanı */
            border: 1px dashed #3b82f6; /* Senin aktif input rengin */
            border-radius: 8px;
            padding: 24px;
            margin: 0 auto 24px auto;
            max-width: 240px;
        }

        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 32px;
            font-weight: 700;
            color: #3b82f6; /* Senin primary buton rengin */
            letter-spacing: 8px;
            margin: 0;
            text-align: center;
        }

        /* GÜVENLİK BİLGİSİ (Senin security-note tarzında) */
        .security-badge {
            display: inline-block;
            background-color: #eff6ff; /* Senin inst-opt.selected arkaplanı */
            border: 1px solid #3b82f6;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            color: #2563eb;
            margin-bottom: 8px;
        }

        .security-text {
            font-size: 12px;
            color: #9ca3af;
            margin: 0;
        }

        /* FOOTER (Senin price-breakdown alanın gibi ayrılmış) */
        .card-footer {
            padding: 16px 20px;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }

        .footer-text {
            font-size: 11px;
            color: #9ca3af;
            line-height: 1.5;
            margin: 0;
        }

        .footer-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        @media only screen and (max-width: 480px) {
            .email-wrapper { padding: 20px 10px; }
            .card-body { padding: 24px 16px; }
            .otp-code { font-size: 28px; letter-spacing: 6px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="card-container">
            
            <!-- ÜST HEADER (Senin Navbar Tarzın) -->
            <div class="card-header">
                <a class="logo-text">Yunus<span>Pet</span></a>
            </div>

            <!-- İÇERİK -->
            <div class="card-body">
                <!-- DİNAMİK BAŞLIK -->
                <h1 class="content-title">
                    @if($title === 'register')
                    <p>Hesabınızı doğrulamak için</p>
                    @elseif($title === 'password-reset')
                    <p>Şifrenizi sıfırlamak için</p>
                    @elseif($title === 'email-change')
                    <p>Mail adresinizi değiştirmek için</p>
                    @endif
                    
                </h1>
            
                <!-- DİNAMİK MESAJ -->
                <p class="content-text">
                    {{ $otpMessage ?? '' }}
                </p>

                <!-- OTP KODU (Aktif input tarzı mavi vurgulu) -->
                <div class="otp-wrapper">
                    <p class="otp-code">{{ $otp }}</p>
                </div>

                <!-- GÜVENLİK (Senin address-badge / inst-opt.selected tarzın) -->
                <div class="security-badge">
                    ⏱️ Kod 5 dakika geçerlidir
                </div>
                <p class="security-text">
                    Lütfen bu kodu personelimiz dahil kimseyle paylaşmayın.
                </p>
            </div>

            <!-- ALT BİLGİ -->
            <div class="card-footer">
                <p class="footer-text">
                    Bu işlemi siz başlatmadıysanız kod paylaşılmadığı sürece hiç bir etkisi olmayacaktır.
                </p>
            </div>

        </div>
    </div>
</body>
</html>