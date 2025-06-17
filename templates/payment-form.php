<?php
/**
 * Payment Form Template - Mövcud Dizayn Saxlanaraq
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Nav Scroller - Mövcud Struktur Saxlanıldı -->
<div class="js-nav-scroller hs-nav-scroller-horizontal">
    <span class="hs-nav-scroller-arrow-prev" style="display: none;">
        <a class="hs-nav-scroller-arrow-link" href="javascript:;">
            <i class="bi-chevron-left"></i>
        </a>
    </span>

    <span class="hs-nav-scroller-arrow-next" style="display: none;">
        <a class="hs-nav-scroller-arrow-link" href="javascript:;">
            <i class="bi-chevron-right"></i>
        </a>
    </span>
    
    <!-- Nav - Eyni Structure -->
    <ul class="nav nav-segment nav-pills nav-fill mx-auto mb-7" id="featuresTab" role="tablist" style="max-width: 50rem;">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" href="#featuresThree" id="featuresThree-tab" data-bs-toggle="tab" data-bs-target="#featuresThree" role="tab" aria-controls="featuresThree" aria-selected="false">
                <?php _e('Fiziki şəxs', 'kapital-tif-donation'); ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" href="#featuresFour" id="featuresFour-tab" data-bs-toggle="tab" data-bs-target="#featuresFour" role="tab" aria-controls="featuresFour" aria-selected="false">
                <?php _e('Hüquqi şəxs', 'kapital-tif-donation'); ?>
            </a>
        </li>
    </ul>
    <!-- End Nav -->
</div>
<!-- End Nav Scroller -->

<!-- Tab Content - Mövcud Structure -->
<div class="tab-content" id="featuresTabContent">

    <!-- Fiziki Şəxs Tab - Minimal Dəyişiklik -->
    <div class="tab-pane fade show active" id="featuresThree" role="tabpanel" aria-labelledby="featuresThree-tab">
        <form action="<?php echo esc_url(home_url('/donation/')); ?>" method="get">
            <input type="hidden" name="gotopayment" value="1">
            <input type="hidden" name="fiziki_huquqi" value="Fiziki şəxs">
            
            <div class="mb-5">
                <h4 class="my-7"><?php _e('Fiziki şəxs haqqında informasiya', 'kapital-tif-donation'); ?></h4>

                <div class="row">
                    <div class="col-lg-12">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label"><?php _e('Fiziki şəxsin Soyadı, Adı, Ata adı', 'kapital-tif-donation'); ?></label>

                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-person-fill"></i>
                                </span>
                                <input type="text" class="form-control form-control-lg" 
                                       name="ad_soyad" 
                                       placeholder="<?php esc_attr_e('Soyad, Ad, Ata adı', 'kapital-tif-donation'); ?>" 
                                       required>
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label"><?php _e('Mobil nömrə', 'kapital-tif-donation'); ?></label>

                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-telephone-inbound-fill"></i>
                                </span>
                                <input type="text" class="form-control form-control-lg" 
                                       name="telefon_nomresi" 
                                       placeholder="+994(xx)xxx-xx-xx" 
                                       required>
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label"><?php printf(__('Məbləğ (%s)', 'kapital-tif-donation'), $config['payment']['currency']); ?></label>

                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi bi-cash"></i>
                                </span>
                                <input type="number" step="0.01" 
                                       min="<?php echo esc_attr($config['payment']['min_amount']); ?>" 
                                       max="<?php echo esc_attr($config['payment']['max_amount']); ?>" 
                                       class="form-control form-control-lg" 
                                       name="mebleg" 
                                       required>
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->
                </div>
                <!-- End Row -->
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-success btn-lg"><?php _e('Ödənişə keç', 'kapital-tif-donation'); ?></button>
            </div>
        </form>
    </div>

    <!-- Hüquqi Şəxs Tab - Əsas Dəyişikliklər Burada -->
    <div class="tab-pane fade" id="featuresFour" role="tabpanel" aria-labelledby="featuresFour-tab">
        <form action="<?php echo esc_url(home_url('/donation/')); ?>" method="get">
            <input type="hidden" name="gotopayment" value="1">
            <input type="hidden" name="fiziki_huquqi" value="Hüquqi şəxs">
            
            <div class="mb-5">
                <h4 class="my-7"><?php _e('Hüquqi şəxs haqqında informasiya', 'kapital-tif-donation'); ?></h4>

                <div class="row">
                    <div class="col-lg-12">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label"><?php _e('Şəxsin Soyadı, Adı, Ata adı', 'kapital-tif-donation'); ?></label>

                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-person-fill"></i>
                                </span>
                                <input type="text" class="form-control form-control-lg" 
                                       name="ad_soyad" 
                                       placeholder="<?php esc_attr_e('Soyad, Ad, Ata adı', 'kapital-tif-donation'); ?>" 
                                       required>
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form - DƏYİŞİKLİK: "Təşkilatın adı" → "Qurumun adı" -->
                        <div class="mb-4">
                            <label class="form-label"><?php _e('Qurumun adı', 'kapital-tif-donation'); ?></label>

                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-building"></i>
                                </span>
                                <input type="text" class="form-control form-control-lg" 
                                       name="teskilat_adi" 
                                       placeholder="<?php esc_attr_e('Qurumun adı', 'kapital-tif-donation'); ?>" 
                                       required>
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form - YENİ FIELD: Qurumun VÖENİ -->
                        <div class="mb-4">
                            <label class="form-label"><?php _e('Qurumun VÖENİ', 'kapital-tif-donation'); ?></label>

                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-hash"></i>
                                </span>
                                <input type="text" class="form-control form-control-lg" 
                                       name="voen" 
                                       placeholder="<?php esc_attr_e('Qurumun VÖENİ', 'kapital-tif-donation'); ?>" 
                                       maxlength="10" 
                                       pattern="[0-9]{10}" 
                                       required>
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label"><?php _e('Əlaqə vasitəsi', 'kapital-tif-donation'); ?></label>

                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-telephone-inbound-fill"></i>
                                </span>
                                <input type="text" class="form-control form-control-lg" 
                                       name="telefon_nomresi" 
                                       placeholder="+994(xx)xxx-xx-xx" 
                                       required>
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label"><?php printf(__('Məbləğ (%s)', 'kapital-tif-donation'), $config['payment']['currency']); ?></label>

                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi bi-cash"></i>
                                </span>
                                <input type="number" step="0.01" 
                                       min="<?php echo esc_attr($config['payment']['min_amount']); ?>" 
                                       max="<?php echo esc_attr($config['payment']['max_amount']); ?>" 
                                       class="form-control form-control-lg" 
                                       name="mebleg" 
                                       required>
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->
                </div>
                <!-- End Row -->
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-success btn-lg"><?php _e('Ödənişə keç', 'kapital-tif-donation'); ?></button>
            </div>
        </form>
    </div>
</div>
<!-- End Tab Content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[name="telefon_nomresi"]');
    phoneInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            // Format phone number (9 digits max)
            if (value.length > 9) {
                value = value.substring(0, 9);
            }
            
            this.value = value;
        });
    });
    
    // VÖEN formatting (10 digits only)
    const voenInput = document.querySelector('input[name="voen"]');
    if (voenInput) {
        voenInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            // Limit to 10 digits
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            this.value = value;
        });
    }
});
</script>