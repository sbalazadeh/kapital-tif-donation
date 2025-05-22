/**
 * Kapital TIF Donation Plugin - Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        initCompanyFieldToggle();
        initStatusSync();
        initExportFunctionality();
    });
    
    /**
     * Initialize company field toggle in meta boxes
     */
    function initCompanyFieldToggle() {
        var $companySelect = $('#company');
        var $companyNameRow = $('#company_name_row');
        
        if ($companySelect.length === 0) {
            return;
        }
        
        function toggleCompanyNameField() {
            if ($companySelect.val() === 'Hüquqi şəxs') {
                $companyNameRow.show();
            } else {
                $companyNameRow.hide();
            }
        }
        
        $companySelect.on('change', toggleCompanyNameField);
        
        // Initialize on page load
        toggleCompanyNameField();
    }
    
    /**
     * Initialize payment status sync functionality
     */
    function initStatusSync() {
        var $syncButton = $('#sync_status_button');
        var $syncResult = $('#sync_status_result');
        
        if ($syncButton.length === 0) {
            return;
        }
        
        $syncButton.on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var postId = $button.data('post-id');
            
            // Disable button and show loading
            $button.prop('disabled', true);
            $syncResult.html('<span style="color:blue"><i class="dashicons dashicons-update spin"></i> Yenilənir...</span>');
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tif_sync_payment_status',
                    post_id: postId,
                    nonce: tif_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $syncResult.html('<span style="color:green"><i class="dashicons dashicons-yes"></i> Status yeniləndi: ' + response.data.status + '</span>');
                        
                        // Reload page after 1.5 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $syncResult.html('<span style="color:red"><i class="dashicons dashicons-no"></i> Xəta: ' + (response.data ? response.data.message : 'Bilinməyən xəta') + '</span>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    $syncResult.html('<span style="color:red"><i class="dashicons dashicons-no"></i> Serverlə əlaqə zamanı xəta baş verdi.</span>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    
                    // Hide result after 5 seconds
                    setTimeout(function() {
                        $syncResult.fadeOut();
                    }, 5000);
                }
            });
        });
        
        // Add CSS for spinning icon
        if ($('.tif-admin-css').length === 0) {
            $('head').append(`
                <style class="tif-admin-css">
                .dashicons.spin {
                    animation: tif-admin-spin 1s linear infinite;
                }
                
                @keyframes tif-admin-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                #sync_status_result {
                    margin-left: 10px;
                    font-weight: bold;
                }
                
                .tif-admin-notice {
                    padding: 10px 15px;
                    margin: 5px 0 15px;
                    border-left: 4px solid;
                    background: #fff;
                    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
                }
                
                .tif-admin-notice.notice-success {
                    border-left-color: #46b450;
                }
                
                .tif-admin-notice.notice-error {
                    border-left-color: #dc3232;
                }
                
                .tif-admin-notice.notice-warning {
                    border-left-color: #ffb900;
                }
                </style>
            `);
        }
    }
    
    /**
     * Initialize export functionality
     */
    function initExportFunctionality() {
        var $selectAllBtn = $('#select-all-btn');
        var $copyBtn = $('#copy-btn');
        var $copyStatus = $('#copy-status');
        var $exportTable = $('#export-table');
        
        if ($exportTable.length === 0) {
            return;
        }
        
        // Select all functionality
        $selectAllBtn.on('click', function() {
            if (window.getSelection && document.createRange) {
                var range = document.createRange();
                range.selectNode($exportTable[0]);
                
                var selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                
                // Show feedback
                $(this).text('Seçildi!').addClass('button-primary');
                
                setTimeout(function() {
                    $selectAllBtn.text('Hamısını Seç').removeClass('button-primary');
                }, 2000);
            } else {
                // Fallback for older browsers
                showAdminNotice('Brauzeriniz avtomatik seçim dəstəkləmir. Zəhmət olmasa manual olaraq seçin.', 'warning');
            }
        });
        
        // Copy functionality
        $copyBtn.on('click', function() {
            try {
                // First select the table
                if (window.getSelection && document.createRange) {
                    var range = document.createRange();
                    range.selectNode($exportTable[0]);
                    
                    var selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
                
                // Try to copy
                var successful = document.execCommand('copy');
                
                if (successful) {
                    $copyStatus.fadeIn().delay(3000).fadeOut();
                    $copyBtn.text('Kopyalandı!').addClass('button-primary');
                    
                    setTimeout(function() {
                        $copyBtn.text('Kopyala').removeClass('button-primary');
                    }, 2000);
                } else {
                    throw new Error('Copy command failed');
                }
                
                // Clear selection
                if (window.getSelection) {
                    window.getSelection().removeAllRanges();
                }
                
            } catch (err) {
                console.error('Copy error:', err);
                showAdminNotice('Kopyalama xətası baş verdi. Zəhmət olmasa manual olaraq kopyalayın.', 'error');
            }
        });
        
        // Alternative: Add CSV download functionality
        addCSVDownloadButton();
    }
    
    /**
     * Add CSV download button as alternative to copy/paste
     */
    function addCSVDownloadButton() {
        var $exportTable = $('#export-table');
        
        if ($exportTable.length === 0) {
            return;
        }
        
        var $downloadBtn = $('<button type="button" id="download-csv-btn" class="button">CSV Yüklə</button>');
        $('#copy-btn').after(' ', $downloadBtn);
        
        $downloadBtn.on('click', function() {
            var csv = [];
            var rows = $exportTable.find('tr');
            
            rows.each(function() {
                var row = [];
                $(this).find('th, td').each(function() {
                    var cellText = $(this).text().replace(/"/g, '""'); // Escape quotes
                    row.push('"' + cellText + '"');
                });
                csv.push(row.join(','));
            });
            
            var csvContent = csv.join('\n');
            var blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' }); // Add BOM for UTF-8
            
            // Create download link
            var link = document.createElement('a');
            if (link.download !== undefined) {
                var url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'ianeler-' + new Date().toISOString().split('T')[0] + '.csv');
                link.style.visibility = 'hidden';
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showAdminNotice('CSV faylı yükləndi!', 'success');
            } else {
                showAdminNotice('Brauzeriniz fayl yükləməni dəstəkləmir.', 'error');
            }
        });
    }
    
    /**
     * Show admin notice
     */
    function showAdminNotice(message, type) {
        type = type || 'info';
        
        var $notice = $('<div class="tif-admin-notice notice notice-' + type + ' is-dismissible"></div>')
            .html('<p>' + message + '</p>');
        
        // Add to the page
        if ($('.wrap h1').length > 0) {
            $('.wrap h1').after($notice);
        } else {
            $('.wrap').prepend($notice);
        }
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Add dismiss functionality
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Enhance post list table
     */
    function enhancePostListTable() {
        var $postTable = $('.wp-list-table');
        
        if ($postTable.length === 0 || !$postTable.hasClass('posts')) {
            return;
        }
        
        // Add quick actions for orders
        $postTable.find('tbody tr').each(function() {
            var $row = $(this);
            var $actionsCell = $row.find('.column-title .row-actions');
            
            if ($actionsCell.length > 0) {
                // Add sync status quick action
                var postId = $row.attr('id');
                if (postId) {
                    postId = postId.replace('post-', '');
                    
                    var $syncAction = $('<span class="tif-sync-status"> | <a href="#" data-post-id="' + postId + '">Status Yenilə</a></span>');
                    $actionsCell.append($syncAction);
                    
                    $syncAction.find('a').on('click', function(e) {
                        e.preventDefault();
                        
                        var $link = $(this);
                        var id = $link.data('post-id');
                        
                        $link.text('Yenilənir...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'tif_sync_payment_status',
                                post_id: id,
                                nonce: tif_admin_ajax.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    $link.text('Yeniləndi!').css('color', 'green');
                                    
                                    setTimeout(function() {
                                        location.reload();
                                    }, 1000);
                                } else {
                                    $link.text('Xəta!').css('color', 'red');
                                }
                            },
                            error: function() {
                                $link.text('Əlaqə xətası!').css('color', 'red');
                            }
                        });
                    });
                }
            }
        });
    }
    
    // Initialize post list table enhancements
    enhancePostListTable();

})(jQuery);