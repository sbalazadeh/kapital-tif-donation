# Kapital TIF Donation Plugin v2.1.0

Kapital Bank E-commerce API ilə inteqrasiya edilmiş peşəkar ianə toplama plugin-i.

## 📋 Xüsusiyyətlər

- ✅ **Kapital Bank API İnteqrasiyası** - Tam test və production dəstəyi
- ✅ **Responsive Ödəniş Formu** - Fiziki və Hüquqi şəxs tab-ları
- ✅ **VÖEN Field Integration** - Hüquqi şəxslər üçün VÖEN dəstəyi
- ✅ **Avtomatik Status Yeniləmə** - Real-time ödəniş statusu
- ✅ **Admin Panel İdarəetməsi** - Tam administrativ nəzarət
- ✅ **İxrac Funksiyası** - CSV və Excel formatında (VÖEN daxil)
- ✅ **Təhlükəsizlik** - WordPress standartlarına uyğun
- ✅ **Çoxdilli Dəstək** - Azərbaycan dilində
- ✅ **Modulyar Struktur** - Genişlənə bilən kod təşkilatı

## 🆕 v2.1.0 Yeniliklər

### VÖEN Field Integration
- **Hüquqi şəxs formu** üçün VÖEN field-i əlavə edildi
- **10 rəqəm validation** - VÖEN düzgün formatda olmalıdır
- **Admin panel-də VÖEN column** - Hüquqi şəxslər üçün VÖEN göstərilir
- **Export funksiyasında VÖEN** - CSV ixracında VÖEN sütunu
- **Conditional validation** - VÖEN yalnız hüquqi şəxs üçün məcburidir

### Form Structure Yeniləmə
- **Tab-based interface** - Fiziki/Hüquqi şəxs seçimi
- **Smart field toggle** - Növə görə field-lər göstərilir
- **Enhanced validation** - Real-time form validation

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
│   ├── payment-form.php               # Ödəniş formu (VÖEN daxil)
│   ├── thank-you.php                  # Təşəkkür səhifəsi
│   ├── payment-failed.php             # Uğursuz ödəniş
│   └── admin/                         # Admin templateləri
│       ├── donation-details.php       # VÖEN field daxil
│       ├── transaction-details.php
│       ├── export-donations.php       # VÖEN column daxil
│       └── statistics.php
└── assets/
    ├── css/
    │   └── style.css                  # Frontend CSS
    └── js/
        ├── script.js                  # Frontend JS (VÖEN validation)
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

### 3. Konfiqurasiya

**Production Mərhələsi** (hazırkı):
```php
// config/config.php faylında bu parametrlər:

// Production mode
'test_mode' => false,

// Production credentials
'production' => array(
    'api_url' => 'https://e-commerce.kapitalbank.az/api',
    'hpp_url' => 'https://e-commerce.kapitalbank.az/flex',
    'username' => 'YOUR_PRODUCTION_USERNAME',
    'password' => 'YOUR_PRODUCTION_PASSWORD',
),

// Debug disabled
'debug' => array(
    'log_api_requests' => false,
),

// SSL active
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

### Form Strukturu

#### Fiziki Şəxs Tab
- **Ad və soyad** (məcburi)
- **Mobil nömrə** (məcburi)
- **Məbləğ** (məcburi)

#### Hüquqi Şəxs Tab
- **Şəxsin adı** (məcburi)
- **Qurumun adı** (məcburi)
- **Qurumun VÖENİ** (məcburi, 10 rəqəm)
- **Əlaqə vasitəsi** (məcburi)
- **Məbləğ** (məcburi)

### Admin Panel

#### İanələr
- **WordPress Admin > İanələr** - Bütün ianələrin siyahısı
- **VÖEN Column** - Hüquqi şəxslər üçün VÖEN göstərilir
- **İanələri ixrac et** - CSV formatında ixrac (VÖEN daxil)
- **Statistika** - Ümumi statistika

#### Hər İanə üçün
- İanə məlumatları (ad, telefon, məbləğ, VÖEN)
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

### VÖEN Validation
```javascript
// Frontend validation
- VÖEN: 10 rəqəm məcburi (hüquqi şəxs üçün)
- Real-time formatting
- Form submission validation
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

#### Fiziki Şəxs Test
1. **Ödəniş formu** - Fiziki şəxs tab seç
2. **Form doldur** - Ad, telefon, məbləğ (VÖEN YOX)
3. **Ödəniş et** - Test kartı ilə
4. **Admin yoxla** - VÖEN "—" göstərilir

#### Hüquqi Şəxs Test
1. **Ödəniş formu** - Hüquqi şəxs tab seç
2. **Form doldur** - Ad, qurum adı, VÖEN (10 rəqəm), telefon, məbləğ
3. **VÖEN validation** - 10 rəqəmdən az olarsa xəta
4. **Ödəniş et** - Test kartı ilə
5. **Admin yoxla** - VÖEN düzgün göstərilir
6. **Export test** - CSV-də VÖEN column var

### Production-a Keçiş Şərtləri
✅ Bütün test scenariları uğurlu keçirilmiş<br>
✅ API əlaqəsi stabil işləyir<br>
✅ Status sinxronizasiyası düzgün<br>
✅ Admin panel tam fəaliyyətdə<br>
✅ Export funksiyası işləyir (VÖEN daxil)<br>
✅ VÖEN validation düzgün işləyir<br>
✅ Form tab switching düzgün<br>
✅ SSL sertifikatlar hazır<br>
✅ Production credentials əldə edilmiş

## 🛡️ Təhlükəsizlik

- BasicAuth autentifikasiya
- WordPress nonce yoxlama
- SQL injection mühafizəsi
- XSS filtrasiya
- CSRF mühafizəsi
- VÖEN data sanitization

## 📊 Status Mapping

| Bank Status | WordPress Status |
|-------------|------------------|
| FullyPaid   | completed        |
| Preparing   | processing       |
| Declined    | failed           |
| Cancelled   | cancelled        |
| Pending     | pending          |

## 📋 Database Fields

### Məcburi Fields
- **name** - Ad və soyad
- **phone** - Telefon nömrəsi
- **amount** - İanə məbləği
- **company** - Fiziki/Hüquqi şəxs

### Şərti Fields
- **company_name** - Qurumun adı (hüquqi şəxs üçün)
- **voen** - Qurumun VÖENİ (hüquqi şəxs üçün)

### Avtomatik Fields
- **transactionId_local** - Sistem ID
- **payment_status** - Ödəniş statusu
- **payment_date** - Ödəniş tarixi

## 🔍 Troubleshooting

### VÖEN Əlaqədar Problemlər

1. **VÖEN validation işləmir**
   - JavaScript yüklənməsini yoxlayın
   - Browser console-da xəta yoxlayın
   - Form field name-lərini təsdiqləyin

2. **VÖEN admin panel-də görünmür**
   - Post meta yoxlayın: `get_post_meta($post_id, 'voen', true)`
   - Company type-ını yoxlayın (hüquqi şəxs olmalı)

3. **Export-da VÖEN sütunu yox**
   - Template cache-ni təmizləyin
   - Plugin-i yenidən aktivləşdirin

### Ümumi Problemlər

1. **API əlaqə xətası**
   - SSL sertifikatlarını yoxlayın
   - Firewall parametrlərini yoxlayın
   - Credentials düzgünlüyünü təsdiqləyin

2. **Status yenilənmir**
   - Cron job-ların işlədiyini yoxlayın
   - `wp_cron` aktivliyini təsdiqləyin

3. **Form submit işləmir**
   - Required field validation yoxlayın
   - JavaScript xətalarını yoxlayın
   - Network tab-da AJAX requests yoxlayın

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

## 🔄 Version History

### v2.1.0 (Current)
- ✅ VÖEN field integration
- ✅ Tab-based form interface
- ✅ Enhanced validation
- ✅ Export VÖEN column
- ✅ Admin panel VÖEN support

### v2.0.0
- ✅ Kapital Bank API integration
- ✅ WordPress custom post types
- ✅ Admin panel management
- ✅ Export functionality

## 🤝 Dəstək

### Texniki Dəstək
- WordPress PHP 7.4+
- WordPress 5.0+
- SSL sertifikatı (production)
- JavaScript enabled browsers

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
- **Progressive enhancement** - JavaScript optional

### VÖEN Implementation
```php
// Database field
'voen' => sanitize_text_field($voen)

// Validation (frontend + backend)
if ($company_type === 'Hüquqi şəxs' && strlen($clean_voen) !== 10)

// Display logic (admin)
if ($company === 'Hüquqi şəxs' && !empty($voen))
```

### Genişləndirmə
```php
// Custom hook-lar
do_action('tif_donation_created', $order_id);
do_action('tif_payment_completed', $order_id);

// VÖEN hook-ları
do_action('tif_voen_validated', $voen, $order_id);

// Filter-lər
$amount = apply_filters('tif_donation_amount', $amount, $order_data);
$voen = apply_filters('tif_donation_voen', $voen, $company_data);
```

### API Callback URL
```
/donation/?callback=1&wpid={order_id}
```

## 🎯 Production Checklist

- ✅ VÖEN field tam test edilib
- ✅ Fiziki/Hüquqi şəxs forms test edilib  
- ✅ Admin panel VÖEN columns test edilib
- ✅ Export funksiyası VÖEN ilə test edilib
- ✅ API integration stabil işləyir
- ✅ SSL sertifikatlar quraşdırılıb
- ✅ Production credentials təyin edilib
- ✅ Debug mode söndürülüb
- ✅ Performance optimization edilib

---

Bu struktur həm müasir WordPress standartlarına uyğundur, həm də gələcəkdə genişləndirmə üçün əlverişlidir. VÖEN field integration-ı mükəmməl şəkildə tamamlanıb və production üçün hazırdır! 🚀