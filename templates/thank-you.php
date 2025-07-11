<?php
/**
 * Thank You Page Template - SIMPLIFIED VERSION
 * Sertifikat avtomatik generate olunur və çap button ilə print edilir
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Debug üçün
error_log('Thank You Page Debug:');
error_log('Order ID: ' . $order_id);
error_log('Status: ' . $status);

// Test mode üçün nonce yoxlamasını keç
$is_test_mode = isset($_GET['test_mode']) || (strpos(get_post_meta($order_id, 'transactionId_local', true), 'TEST-') === 0);

if ($is_test_mode) {
    error_log('Test mode aktiv');
}

// Get order details
$name = get_post_meta($order_id, 'name', true);
$amount = get_post_meta($order_id, 'amount', true);
$transaction_id = get_post_meta($order_id, 'transactionId_local', true);
$date = get_post_meta($order_id, 'payment_date', true);
$iane_tesnifati = get_post_meta($order_id, 'iane_tesnifati', true);

// Format date
if (empty($date)) {
    $date = date('d F Y');
} else {
    $date_obj = date_create($date);
    if ($date_obj) {
        $date = date_format($date_obj, 'd F Y');
    }
}

// Certificate generation - SIMPLIFIED
$certificate_enabled = false;
$certificate_svg = '';
$certificate_error = '';

if (class_exists('TIF_Certificate')) {
    try {
        // Config-i yüklə
        $config_file = TIF_DONATION_CONFIG_DIR . 'config.php';
        if (file_exists($config_file)) {
            $config = require $config_file;
            
            $certificate_generator = new TIF_Certificate($config);
            $certificate_enabled = $certificate_generator->is_certificate_enabled($order_id);
            
            if ($certificate_enabled) {
                // Avtomatik sertifikat generation
                $certificate_svg = $certificate_generator->generate_certificate_for_thank_you($order_id);
                
                if ($certificate_svg) {
                    error_log("TIF Certificate: Auto-generated for thank you page - Order: {$order_id}");
                } else {
                    $certificate_error = 'Sertifikat yaradıla bilmədi.';
                    error_log("TIF Certificate: Generation failed for order: {$order_id}");
                }
            }
        }
    } catch (Exception $e) {
        $certificate_error = 'Sertifikat xətası: ' . $e->getMessage();
        error_log("TIF Certificate Error: " . $e->getMessage());
    }
}
?>

<div class="tif-thank-you-container">

    <?php if (($status === 'success' || $status === 'completed') && $certificate_enabled): ?>
    <!-- SIMPLIFIED Certificate Section -->
    <div class="tif-certificate-section">
        <div class="tif-certificate-header">
            <h2 style="color: #B9C294;"><?php _e('İanə Sertifikatınız', 'kapital-tif-donation'); ?></h2>
            <p><?php _e('İanənizə görə təşəkkür edirik. Sertifikatınızı aşağıda görə və çap edə bilərsiniz.', 'kapital-tif-donation'); ?></p>
        </div>

        <?php if (!empty($certificate_error)): ?>
        <!-- Certificate Error -->
        <div class="tif-certificate-error alert alert-danger">
            <p><strong><?php _e('Xəta:', 'kapital-tif-donation'); ?></strong> <?php echo esc_html($certificate_error); ?></p>
            <p><?php _e('Zəhmət olmasa daha sonra yenidən cəhd edin və ya bizimlə əlaqə saxlayın.', 'kapital-tif-donation'); ?></p>
        </div>
        
        <?php elseif (!empty($certificate_svg)): ?>
        <!-- Certificate Display - DIRECT SVG OUTPUT -->
        <div class="tif-certificate-display" id="tif-certificate-display">
            <div class="tif-certificate-content">
                <?php echo $certificate_svg; ?>
            </div>
        </div>

        <!-- PNG Download + Clean Print -->
        <div class="tif-certificate-actions">
            <button type="button" onclick="openCertificateWindow()" class="btn btn-success">
                <i class="fa fa-print"></i>
                <?php _e('Çap et', 'kapital-tif-donation'); ?>
            </button>
            
            <button type="button" onclick="downloadAsPNG()" class="btn btn-soft-success">
                <i class="fa fa-download"></i>
                <?php _e('PNG yüklə', 'kapital-tif-donation'); ?>
            </button>
        </div>

        <!-- Certificate Type Info -->
        <?php if (!empty($iane_tesnifati)): ?>
        <div class="tif-certificate-type-info">
            <p class="tif-certificate-type-label">
                <strong><?php _e('Sertifikat növü:', 'kapital-tif-donation'); ?></strong>
                <?php
                $type_names = array(
                    'tifiane' => __('Təhsilin İnkişafı Fondu', 'kapital-tif-donation'),
                    'qtdl' => __('Gənc Qızların Təhsilinə Dəstək', 'kapital-tif-donation'),
                    'qtp' => __('Qarabağ Təqaüd Proqramı', 'kapital-tif-donation')
                );
                echo esc_html($type_names[$iane_tesnifati] ?? $type_names['tifiane']);
                ?>
            </p>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <!-- No Certificate Available -->
        <div class="tif-certificate-not-available">
            <p><?php _e('Sertifikat hazırlanır, zəhmət olmasa bir az gözləyin.', 'kapital-tif-donation'); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Navigation Actions -->
    <div class="tif-actions">
        <a href="<?php echo esc_url(home_url('/donation/')); ?>" class="btn btn-soft-success">
            <?php _e('Yeni ianə et', 'kapital-tif-donation'); ?>
        </a>
        <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-success">
            <?php _e('Ana səhifəyə qayıt', 'kapital-tif-donation'); ?>
        </a>
    </div>
</div>

<!-- Certificate CSS - External file -->
<link rel="stylesheet" href="<?php echo TIF_DONATION_ASSETS_URL; ?>css/certificate.css?v=<?php echo TIF_DONATION_VERSION; ?>">

<!-- PNG Download + Clean Print JavaScript -->
<script>
// Ümumi SVG→PNG conversion funksiyası (DM Sans font ilə)
function convertSVGToPNG(svgElement, scale = 2, callback) {
    // DM Sans fontunu preload et
    const dmSans = new FontFace('DM Sans', 'url(https://fonts.gstatic.com/s/dmsans/v13/rP2tp2ywxg089UriCZ2IHSeH.woff2)', {
        weight: '600 800',
        style: 'normal'
    });
    
    dmSans.load().then(() => {
        document.fonts.add(dmSans);
        processSVGConversion(svgElement, scale, callback);
    }).catch(() => {
        processSVGConversion(svgElement, scale, callback);
    });
}

function processSVGConversion(svgElement, scale, callback) {
    // SVG-ə embedded font styles əlavə et
    const svgData = new XMLSerializer().serializeToString(svgElement);
    const fontEmbeddedSVG = `
        <svg viewBox="0 0 842 600" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@600;800&display=swap');
                    * {
                        font-family: 'DM Sans', Arial, sans-serif !important;
                    }
                </style>
            </defs>
            ${svgData.replace('<svg', '<g').replace('</svg>', '</g>')}
        </svg>
    `;
    
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    const svgWidth = svgElement.viewBox.baseVal.width || 842;
    const svgHeight = svgElement.viewBox.baseVal.height || 600;
    
    canvas.width = svgWidth * scale;
    canvas.height = svgHeight * scale;
    canvas.style.width = svgWidth + 'px';
    canvas.style.height = svgHeight + 'px';
    
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    const img = new Image();
    
    img.onload = function() {
        setTimeout(() => {
            ctx.scale(scale, scale);
            ctx.drawImage(img, 0, 0, svgWidth, svgHeight);
            callback(canvas);
        }, 100);
    };
    
    img.onerror = function() {
        alert('PNG yaradılarkən xəta baş verdi.');
    };
    
    const svgBlob = new Blob([fontEmbeddedSVG], {type: 'image/svg+xml;charset=utf-8'});
    const url = URL.createObjectURL(svgBlob);
    img.src = url;
}

// PNG Download funksiyası (sadələşdirilmiş)
function downloadAsPNG() {
    const svgElement = document.querySelector('.tif-certificate-content svg');
    if (!svgElement) {
        alert('Sertifikat tapılmadı.');
        return;
    }
    
    convertSVGToPNG(svgElement, 2, function(canvas) {
        canvas.toBlob(function(blob) {
            const certificateName = <?php echo json_encode(sanitize_file_name($name ?? "İanəçi")); ?>;
            const certificateDate = <?php echo json_encode(date("Y-m-d")); ?>;
            const orderId = <?php echo $order_id; ?>;
            const filename = `TIF_Sertifikat_${certificateName}_${certificateDate}.png`;
            
            // Download
            const url = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.href = url;
            downloadLink.download = filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            URL.revokeObjectURL(url);
            
            // Server save
            savePNGToServer(blob, orderId, certificateName);
        }, 'image/png', 1.0);
    });
}

// Print funksiyası (sadələşdirilmiş)
function openCertificateWindow() {
    const svgElement = document.querySelector('.tif-certificate-content svg');
    if (!svgElement) {
        alert('Sertifikat tapılmadı.');
        return;
    }
    
    convertSVGToPNG(svgElement, 3, function(canvas) {
        const pngDataURL = canvas.toDataURL('image/png', 1.0);
        const certificateName = <?php echo json_encode($name ?? "İanəçi"); ?>;
        const certificateDate = <?php echo json_encode(date("d.m.Y")); ?>;
        
        openPrintWindow(pngDataURL, certificateName, certificateDate);
    });
}

// Server save funksiyası (dəyişməz)
function savePNGToServer(blob, orderId, certificateName) {
    const formData = new FormData();
    formData.append('action', 'tif_save_certificate_png');
    formData.append('order_id', orderId);
    formData.append('certificate_png', blob, `certificate_${orderId}_${certificateName}.png`);
    formData.append('nonce', '<?php echo wp_create_nonce("tif_save_png"); ?>');
    
    const certificateActions = document.querySelector('.tif-certificate-actions');
    const statusMsg = document.createElement('div');
    statusMsg.id = 'save-status';
    statusMsg.innerHTML = '<p style="color: #007bff; margin-top: 10px;">💾 Sertifikat server-də saxlanır...</p>';
    certificateActions.appendChild(statusMsg);
    
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const statusMsg = document.getElementById('save-status');
        
        if (data.success) {
            statusMsg.innerHTML = '<p style="color: #28a745; margin-top: 10px;">✅ Sertifikat uğurla server-də saxlanıldı!</p>';
            setTimeout(() => {
                if (statusMsg) statusMsg.remove();
            }, 4000);
        } else {
            statusMsg.innerHTML = '<p style="color: #dc3545; margin-top: 10px;">❌ Server save xətası</p>';
            setTimeout(() => {
                if (statusMsg) statusMsg.remove();
            }, 5000);
        }
    })
    .catch(error => {
        const statusMsg = document.getElementById('save-status');
        if (statusMsg) {
            statusMsg.innerHTML = '<p style="color: #dc3545; margin-top: 10px;">❌ Network xətası</p>';
            setTimeout(() => statusMsg.remove(), 5000);
        }
    });
}

// Print window funksiyası (dəyişməz)
function openPrintWindow(imageDataURL, certificateName, certificateDate) {
    const newWindow = window.open('', '_blank', 'width=900,height=700,scrollbars=yes');
    
    if (!newWindow) {
        alert('Pop-up blocker aktivdir. Zəhmət olmasa icazə verin.');
        return;
    }
    
    const htmlContent = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>TIF İanə Sertifikatı - ${certificateName}</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            text-align: center;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .certificate-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: inline-block;
            margin: 20px auto;
        }
        .certificate-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        .actions {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-print { background: #007bff; color: white; }
        .btn-close { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.8; }
        @media print {
            .actions { display: none !important; }
            body { background: white; padding: 0; }
            .certificate-container { 
                box-shadow: none; 
                padding: 0; 
                margin: 0;
                background: transparent;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <img src="${imageDataURL}" alt="TIF Sertifikat" class="certificate-image">
    </div>
    
    <div class="actions">
        <button onclick="window.print()" class="btn btn-print">
            🖨️ Çap et / PDF saxla
        </button>
        <button onclick="window.close()" class="btn btn-close">
            ❌ Bağla
        </button>
    </div>
    
    <p style="color: #666; font-size: 12px; margin-top: 20px;">
        Sertifikat: ${certificateName} - ${certificateDate}
    </p>
</body>
</html>`;
    
    newWindow.document.write(htmlContent);
    newWindow.document.close();
    newWindow.focus();
}

// Page load scroll
document.addEventListener('DOMContentLoaded', function() {
    const certificateSection = document.querySelector('.tif-certificate-section');
    if (certificateSection) {
        setTimeout(function() {
            certificateSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 1000);
    }
});
</script>