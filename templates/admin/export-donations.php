<?php
/**
 * Admin Export Donations Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('İanələri ixrac et', 'kapital-tif-donation'); ?></h1>
    
    <?php if (!$show_results): ?>
    <!-- Export Form -->
    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 5px; max-width: 600px;">
        <form method="post" action="">
            <?php wp_nonce_field($nonce_action); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="date_from"><?php _e('Başlanğıc tarixi', 'kapital-tif-donation'); ?></label>
                    </th>
                    <td><input type="date" id="date_from" name="date_from" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="date_to"><?php _e('Son tarix', 'kapital-tif-donation'); ?></label>
                    </th>
                    <td><input type="date" id="date_to" name="date_to" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="tif_export_donations" class="button button-primary" 
                       value="<?php esc_attr_e('İxrac et', 'kapital-tif-donation'); ?>">
            </p>
        </form>
    </div>
    
    <?php else: ?>
    <!-- Results Table -->
    <?php if (empty($donations)): ?>
        <div class="notice notice-warning">
            <p><?php _e('Göstərilən kriteriyalara uyğun ianə tapılmadı.', 'kapital-tif-donation'); ?></p>
        </div>
        <p>
            <a href="<?php echo admin_url('edit.php?post_type=odenis&page=tif-export-donations'); ?>" class="button">
                ← <?php _e('Geri Qayıt', 'kapital-tif-donation'); ?>
            </a>
        </p>
    <?php else: ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 5px; margin-bottom: 20px;">
            <h2 style="margin-top: 0;"><?php _e('İxrac Nəticələri', 'kapital-tif-donation'); ?></h2>
            <p><strong><?php _e('Tapılan ianə sayı:', 'kapital-tif-donation'); ?></strong> <?php echo count($donations); ?></p>
            
            <div style="background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;"><?php _e('Copy/Paste Təlimatları:', 'kapital-tif-donation'); ?></h3>
                <ol>
                    <li><?php _e('Aşağıdakı "Hamısını Seç" düyməsinə klikləyin', 'kapital-tif-donation'); ?></li>
                    <li><?php _e('Ctrl+C (və ya Cmd+C Mac-də) ilə kopyalayın', 'kapital-tif-donation'); ?></li>
                    <li><?php _e('Excel və ya digər cədvəl proqramını açın', 'kapital-tif-donation'); ?></li>
                    <li><?php _e('Ctrl+V (və ya Cmd+V Mac-də) ilə yapışdırın', 'kapital-tif-donation'); ?></li>
                </ol>
            </div>
            
            <button type="button" id="select-all-btn" class="button button-primary">
                <?php _e('Hamısını Seç', 'kapital-tif-donation'); ?>
            </button>
            <button type="button" id="copy-btn" class="button">
                <?php _e('Kopyala', 'kapital-tif-donation'); ?>
            </button>
            <span id="copy-status" style="margin-left: 10px; color: green; display: none;">
                ✓ <?php _e('Kopyalandı!', 'kapital-tif-donation'); ?>
            </span>
            
            <div id="data-container" style="margin-top: 20px; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; max-height: 600px; overflow-y: auto;">
                <table id="export-table" style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="background: #e0e0e0;">
                            <th style="border: 1px solid #ccc; padding: 8px;">ID</th>
                            <th style="border: 1px solid #ccc; padding: 8px;"><?php _e('Transaction ID', 'kapital-tif-donation'); ?></th>
                            <th style="border: 1px solid #ccc; padding: 8px;"><?php _e('Ad və soyad', 'kapital-tif-donation'); ?></th>
                            <th style="border: 1px solid #ccc; padding: 8px;"><?php _e('Telefon', 'kapital-tif-donation'); ?></th>
                            <th style="border: 1px solid #ccc; padding: 8px;"><?php _e('Məbləğ', 'kapital-tif-donation'); ?></th>
                            <th style="border: 1px solid #ccc; padding: 8px;"><?php _e('Təşkilat', 'kapital-tif-donation'); ?></th>
                            <th style="border: 1px solid #ccc; padding: 8px;"><?php _e('Qurumun adı', 'kapital-tif-donation'); ?></th>
                            <th style="border: 1px solid #ccc; padding: 8px;"><?php _e('VÖEN', 'kapital-tif-donation'); ?></th> <!-- YENİ COLUMN -->
                            <th style="border: 1px solid #ccc; padding: 8px;"><?php _e('Ödəniş tarixi', 'kapital-tif-donation'); ?></th>
                            <th style="border: 1px solid #ccc; padding: 8px;"><?php _e('Status', 'kapital-tif-donation'); ?></th>
                            <th style="border: 1px solid #ccc; padding: 8px;"><?php _e('Bank Order ID', 'kapital-tif-donation'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $donation): 
                            $id = $donation->ID;
                            $status_terms = wp_get_object_terms($id, 'odenis_statusu');
                            $status = !empty($status_terms) ? $status_terms[0]->name : get_post_meta($id, 'payment_status', true);
                        ?>
                        <tr>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo $id; ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo esc_html(get_post_meta($id, 'transactionId_local', true)); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo esc_html(get_post_meta($id, 'name', true)); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo esc_html(get_post_meta($id, 'phone', true)); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo esc_html(get_post_meta($id, 'amount', true)); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo esc_html(get_post_meta($id, 'company', true)); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo esc_html(get_post_meta($id, 'company_name', true)); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo esc_html(get_post_meta($id, 'voen', true)); ?></td> <!-- YENİ DATA -->
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo esc_html(get_post_meta($id, 'payment_date', true)); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo esc_html($status); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo esc_html(get_post_meta($id, 'bank_order_id', true)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <p style="margin-top: 20px;">
                <a href="<?php echo admin_url('edit.php?post_type=odenis&page=tif-export-donations'); ?>" class="button">
                    ← <?php _e('Yeni İxrac', 'kapital-tif-donation'); ?>
                </a>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#select-all-btn').on('click', function() {
                var range = document.createRange();
                range.selectNode(document.getElementById('export-table'));
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
            });
            
            $('#copy-btn').on('click', function() {
                var range = document.createRange();
                range.selectNode(document.getElementById('export-table'));
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                
                try {
                    var successful = document.execCommand('copy');
                    if (successful) {
                        $('#copy-status').fadeIn().delay(2000).fadeOut();
                    }
                } catch (err) {
                    alert('<?php _e("Kopyalama xətası baş verdi. Zəhmət olmasa manual olaraq kopyalayın.", "kapital-tif-donation"); ?>');
                }
                
                window.getSelection().removeAllRanges();
            });
        });
        </script>
    <?php endif; ?>
    <?php endif; ?>
</div>