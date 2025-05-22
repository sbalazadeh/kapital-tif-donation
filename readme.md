# Kapital TIF Donation Plugin v2.0.0

Kapital Bank E-commerce API ilə inteqrasiya edilmiş peşəkar ianə toplama plugin-i.

## 📋 Xüsusiyyətlər

- ✅ **Kapital Bank API İnteqrasiyası** - Tam test və production dəstəyi
- ✅ **Responsive Ödəniş Formu** - Mobil və desktop uyğun
- ✅ **Avtomatik Status Yeniləmə** - Real-time ödəniş statusu
- ✅ **Admin Panel İdarəetməsi** - Tam administrativ nəzarət
- ✅ **İxrac Funksiyası** - CSV və Excel formatında
- ✅ **Təhlükəsizlik** - WordPress standartlarına uyğun
- ✅ **Çoxdilli Dəstək** - Azərbaycan dilində
- ✅ **Modulyar Struktur** - Genişlənə bilən kod təşkilatı

## 🗂️ Fayl Strukturu

```
kapital-tif-donation/
├── kapital-tif-donation.php           # Əsas plugin faylı
├── uninstall.php                      # Plugin silmə
├── README.md                          # Bu fayl
├── config/
│   └── config.php                     # Konfiqurasiya
├── includes/
│   ├── class-tif-donation.php         # Əsas class
│   ├── class-tif-admin.php            # Admin panel
│   ├── class-tif-frontend.php         # Frontend
│   ├── class-tif-api.php              # API əlaqəsi
│   └── class-tif-database.php         # Database əməliyyatları
├── templates/
│   ├── payment-form.php               # Ödəniş formu
│   ├── thank-you.php                  # Təşəkkür səhifəsi
│   ├── payment-failed.php             # Uğursuz ödəniş
│   └── admin/                         # Admin templateləri
│       ├── donation-details.php
│       ├── transaction-details.php
│       ├── export-donations.php
│       └── statistics.php
└── assets/
    ├── css/
    │   └── style.css                  # Frontend CSS
    └── js/
        ├── script.js                  # Frontend JS
        └── admin.js                   # Admin JS
```

## 🚀 Qurulum

### 1. Plugin Yükləmə
```bash
# WordPress wp-content/plugins/ qovluğuna kopyalayın
cp -r kapital-tif-donation/ /path/to/wordpress/wp-content/plugins/
```

### 2. Plugin Aktivləşdirmə
WordPress admin panelində **Plugins > Installed Plugins** bölməsinə gedin və "Kapital TIF Donation Integration" plugin-ini aktivləşdirin.

### 3. Konfiqurasiya (Test Mərhələsi)

**📍 Hazırda test mərhələsindəyik - production-a keçmək üçün bu addımları izləyin:**

**Test Mərhələsi** (hazırkı):
```php
// config/config.php
'test_mode' => true,
'debug' => array(
    'log_api_requests' => true,
),
'security' => array(
    'ssl_verify' => false,
),
```

**Production Mərhələsi** (gələcək):
```php
// config/config.php faylında bu dəyişiklikləri edin:

// Test modunu söndürün
'test_mode' => false,

// Production credentials yeniləyin
'production' => array(
    'api_url' => 'https://e-commerce.kapitalbank.az/api',
    'hpp_url' => 'https://e-commerce.kapitalbank.az/flex',
    'username' => 'YOUR_PRODUCTION_USERNAME', // Real credentials
    'password' => 'YOUR_PRODUCTION_PASSWORD', // Real credentials
),

// Debug-ı söndürün
'debug' => array(
    'log_api_requests' => false,
),

// SSL yoxlamanı aktivləşdirin
'security' => array(
    'ssl_verify' => true,
),
```

## 📖 İstifadə

### Shortcode-lar

#### Ödəniş Formu
```php
[tif_payment_form]
```

#### Nəticə Səhifəsi
```php
[tif_payment_result]
```

### Səhifə Strukturu

1. **Ödəniş Səhifəsi** (`/donation/`)
   - Shortcode: `[tif_payment_form]`
   - Shortcode: `[tif_payment_result]`

### Admin Panel

#### İanələr
- **WordPress Admin > İanələr** - Bütün ianələrin siyahısı
- **İanələri ixrac et** - CSV formatında ixrac
- **Statistika** - Ümumi statistika

#### Hər İanə üçün
- İanə məlumatları (ad, telefon, məbləğ)
- Əməliyyat məlumatları (bank order ID, approval code)
- Status sinxronizasiyası

## ⚙️ Konfiqurasiya Seçimləri

### Əsas Parametrlər
```php
'payment' => array(
    'currency' => 'AZN',
    'language' => 'az',
    'min_amount' => 1,
    'max_amount' => 10000,
    'timeout' => 30,
),
```

### Təhlükəsizlik
```php
'security' => array(
    'ssl_verify' => true, // Production üçün true
),
```

### Debug
```php
'debug' => array(
    'log_api_requests' => false, // Production üçün false
),
```

## 🧪 Test Mərhələsi

### Test Credentials
```
Username: TerminalSys/kapital
Password: kapital123
Test API URL: https://txpgtst.kapitalbank.az/api
Test HPP URL: https://txpgtst.kapitalbank.az/flex
```

### Test Kartları
```
Card 1:
PAN: 4169741330151778
ExpDate: 06/25
CVV: 119

Card 2:
PAN: 5239151747183468
ExpDate: 11/24
CVV2: 292
```

### Test Workflow
1. **Ödəniş formu doldur** - Fake məlumatlar istifadə edin
2. **Kapital test səhifəsinə yönələn**
3. **Test kartı ilə ödəniş edin**
4. **Status yenilənməsini yoxlayın**
5. **Admin paneldə nəticəni görmək**
6. **Export funksiyasını test edin**

### Production-a Keçiş Şərtləri
✅ Bütün test scenariları uğurlu keçirilmiş<br>
✅ API əlaqəsi stabil işləyir<br>
✅ Status sinxronizasiyası düzgün<br>
✅ Admin panel tam fəaliyyətdə<br>
✅ Export funksiyası işləyir<br>
✅ Log faylları təmiz<br>
✅ SSL sertifikatlar hazır<br>
✅ Production credentials əldə edilmiş

### Ödəniş Yaratma
```
POST /order
```

### Status Yoxlama
```
GET /order/{id}
```

### Geri Qaytarma
```
POST /order/{id}/exec-tran
```

## 🛡️ Təhlükəsizlik

- BasicAuth autentifikasiya
- WordPress nonce yoxlama
- SQL injection mühafizəsi
- XSS filtrasiya
- CSRF mühafizəsi

## 📊 Status Mapping

| Bank Status | WordPress Status |
|-------------|------------------|
| FullyPaid   | completed        |
| Preparing   | processing       |
| Declined    | failed           |
| Cancelled   | cancelled        |
| Pending     | pending          |

## 🔍 Troubleshooting

### Ümumi Problemlər

1. **API əlaqə xətası**
   - SSL sertifikatlarını yoxlayın
   - Firewall parametrlərini yoxlayın
   - Credentials düzgünlüyünü təsdiqləyin

2. **Status yenilənmir**
   - Cron job-ların işlədiyini yoxlayın
   - `wp_cron` aktivliyini təsdiqləyin

3. **Ödəniş redirect işləmir**
   - URL rewrite rules yoxlayın
   - `.htaccess` faylını yoxlayın

### Log Faylları
```
/wp-content/uploads/tif-donation-logs.txt
```

### Debug Modu
```php
// config.php-də
'debug' => array(
    'log_api_requests' => true,
),
```

## 🔄 Yeniləmələr

### v1.2.3-dən v2.0.0-a Keçid

1. Köhnə plugin-i deaktiv edin
2. Yeni plugin-i yükləyin
3. Məlumatlar avtomatik olaraq saxlanılır
4. Konfiqurasiya parametrlərini yoxlayın

## 🤝 Dəstək

### Texniki Dəstək
- WordPress PHP 7.4+
- WordPress 5.0+
- SSL sertifikatı (production)

### API Dokumentasiyası
[Kapital Bank E-commerce API](https://documenter.getpostman.com/view/14817621/2sA3dxCB1b)

## 📝 License

Bu plugin WordPress GPL v2 lisenziyası altında yayımlanır.

## 🏗️ Developer Notes

### Kod Strukturu
- **OOP yaklaşım** - Modern PHP class-based
- **WordPress hooks** - Action və filter hook-lar
- **Security first** - Təhlükəsizlik prioritet
- **Modulyar design** - Ayrı komponetlər

### Genişləndirmə
```php
// Custom hook-lar
do_action('tif_donation_created', $order_id);
do_action('tif_payment_completed', $order_id);

// Filter-lər
$amount = apply_filters('tif_donation_amount', $amount, $order_data);
```

### API Callback URL
```
/donation/?callback=1&wpid={order_id}
```

Bu struktur həm müasir WordPress standartlarına uyğundur, həm də gələcəkdə genişləndirmə üçün əlverişlidir.