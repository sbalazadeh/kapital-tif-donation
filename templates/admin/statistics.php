<?php
/**
 * Admin Statistics Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('İanə Statistikaları', 'kapital-tif-donation'); ?></h1>
    
    <div class="tif-stats-container">
        <!-- Overview Cards -->
        <div class="tif-stats-cards">
            <div class="tif-stats-card">
                <div class="tif-stats-card-content">
                    <h3><?php _e('Ümumi İanələr', 'kapital-tif-donation'); ?></h3>
                    <p class="tif-stats-number"><?php echo number_format($stats['total']); ?></p>
                </div>
            </div>
            
            <div class="tif-stats-card">
                <div class="tif-stats-card-content">
                    <h3><?php _e('Ümumi Məbləğ', 'kapital-tif-donation'); ?></h3>
                    <p class="tif-stats-number"><?php echo number_format($stats['total_amount'], 2); ?> AZN</p>
                </div>
            </div>
            
            <?php if (isset($stats['by_status']['Completed'])): ?>
            <div class="tif-stats-card tif-stats-card-success">
                <div class="tif-stats-card-content">
                    <h3><?php _e('Uğurlu Ödənişlər', 'kapital-tif-donation'); ?></h3>
                    <p class="tif-stats-number"><?php echo number_format($stats['by_status']['Completed']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($stats['by_status']['Failed'])): ?>
            <div class="tif-stats-card tif-stats-card-danger">
                <div class="tif-stats-card-content">
                    <h3><?php _e('Uğursuz Ödənişlər', 'kapital-tif-donation'); ?></h3>
                    <p class="tif-stats-number"><?php echo number_format($stats['by_status']['Failed']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Status Breakdown -->
        <?php if (isset($stats['by_status']) && !empty($stats['by_status'])): ?>
        <div class="tif-stats-section">
            <h2><?php _e('Status üzrə Bölgü', 'kapital-tif-donation'); ?></h2>
            <div class="tif-stats-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Status', 'kapital-tif-donation'); ?></th>
                            <th><?php _e('Say', 'kapital-tif-donation'); ?></th>
                            <th><?php _e('Faiz', 'kapital-tif-donation'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_status'] as $status_name => $count): 
                            $percentage = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($status_name); ?></strong></td>
                            <td><?php echo number_format($count); ?></td>
                            <td><?php echo number_format($percentage, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Activity -->
        <div class="tif-stats-section">
            <h2><?php _e('Son Fəaliyyət', 'kapital-tif-donation'); ?></h2>
            <p><?php _e('Son ianələr haqqında məlumat dashboard widget-də göstərilir.', 'kapital-tif-donation'); ?></p>
            <p>
                <a href="<?php echo admin_url('edit.php?post_type=odenis'); ?>" class="button button-primary">
                    <?php _e('Bütün İanələri Göstər', 'kapital-tif-donation'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=odenis&page=tif-export-donations'); ?>" class="button">
                    <?php _e('İanələri İxrac Et', 'kapital-tif-donation'); ?>
                </a>
            </p>
        </div>
    </div>
</div>

<style>
.tif-stats-container {
    margin-top: 20px;
}

.tif-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.tif-stats-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.tif-stats-card-success {
    border-left: 4px solid #46b450;
}

.tif-stats-card-danger {
    border-left: 4px solid #dc3232;
}

.tif-stats-card-content h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tif-stats-number {
    font-size: 28px;
    font-weight: bold;
    color: #333;
    margin: 0;
    line-height: 1.2;
}

.tif-stats-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.tif-stats-section h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
    color: #333;
}

.tif-stats-table-container {
    margin-top: 15px;
}

.tif-stats-table-container table {
    border: 1px solid #ccd0d4;
}

.tif-stats-table-container th,
.tif-stats-table-container td {
    padding: 12px;
}

@media (max-width: 768px) {
    .tif-stats-cards {
        grid-template-columns: 1fr;
    }
    
    .tif-stats-number {
        font-size: 24px;
    }
}
</style>