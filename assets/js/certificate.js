/**
 * TIF Certificate Frontend JavaScript
 * Path: assets/js/certificate.js
 */

window.TIFCertificate = (function() {
    'use strict';

    // Private variables
    let isInitialized = false;
    let currentCertificateData = null;
    let elements = {};

    // Configuration
    const config = {
        ajaxUrl: tif_certificate_ajax.ajax_url || '/wp-admin/admin-ajax.php',
        nonce: tif_certificate_ajax.nonce || '',
        translations: tif_certificate_ajax.translations || {}
    };

    /**
     * Initialize the certificate functionality
     */
    function init() {
        if (isInitialized) return;

        // Cache DOM elements
        cacheElements();

        // Bind events
        bindEvents();

        isInitialized = true;
    }

    /**
     * Cache frequently used DOM elements
     */
    function cacheElements() {
        elements = {
            previewBtn: document.getElementById('tif-preview-certificate'),
            downloadBtn: document.getElementById('tif-download-certificate'),
            printBtn: document.getElementById('tif-print-certificate'),
            preview: document.getElementById('tif-certificate-preview'),
            previewContent: document.querySelector('.tif-preview-content'),
            loadingSpinner: document.querySelector('.tif-loading-spinner'),
            messages: document.querySelector('.tif-certificate-messages'),
            errorMsg: document.querySelector('.tif-certificate-error'),
            successMsg: document.querySelector('.tif-certificate-success')
        };
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Preview button
        if (elements.previewBtn) {
            elements.previewBtn.addEventListener('click', handlePreviewClick);
        }

        // Print button
        if (elements.printBtn) {
            elements.printBtn.addEventListener('click', handlePrintClick);
        }

        // Download button handler with nonce
        if (elements.downloadBtn) {
            elements.downloadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get order ID and create nonce
                const orderId = elements.previewBtn?.getAttribute('data-order-id');
                if (!orderId) {
                    showError('Order ID tapılmadı.');
                    return;
                }
                
                // Create download URL with nonce
                const downloadUrl = new URL(this.href);
                downloadUrl.searchParams.set('nonce', tif_certificate_ajax.download_nonce);
                
                // Navigate to download URL
                window.location.href = downloadUrl.toString();
                
                // Track download event
                trackEvent('certificate_download', {
                    order_id: orderId,
                    certificate_type: elements.previewBtn?.getAttribute('data-certificate-type')
                });
            });
        }

        // Keyboard accessibility
        document.addEventListener('keydown', handleKeyboardShortcuts);
    }

    /**
     * Handle preview button click
     */
    function handlePreviewClick(e) {
        e.preventDefault();
        
        const orderId = e.target.getAttribute('data-order-id');
        const certificateType = e.target.getAttribute('data-certificate-type');

        if (!orderId) {
            showError('Sertifikat məlumatları tapılmadı.');
            return;
        }

        previewCertificate(orderId, certificateType);
    }

    /**
     * Handle print button click
     */
    function handlePrintClick(e) {
        e.preventDefault();
        printCertificate();
    }

    /**
     * Handle download button click (for analytics)
     */
    function handleDownloadClick(e) {
        // Track download event
        trackEvent('certificate_download', {
            order_id: elements.previewBtn?.getAttribute('data-order-id'),
            certificate_type: elements.previewBtn?.getAttribute('data-certificate-type')
        });
    }

    /**
     * Handle keyboard shortcuts
     */
    function handleKeyboardShortcuts(e) {
        // Ctrl+P for print
        if (e.ctrlKey && e.key === 'p' && currentCertificateData) {
            e.preventDefault();
            printCertificate();
        }
    }

    /**
     * Preview certificate via AJAX
     */
    function previewCertificate(orderId, certificateType = 'tif') {
        showLoading();
        hideMessages();

        const formData = new FormData();
        formData.append('action', 'tif_preview_certificate');
        formData.append('order_id', orderId);
        formData.append('type', certificateType);
        formData.append('nonce', config.nonce);

        fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                displayCertificate(data.data.svg);
                showSuccess('Sertifikat uğurla yükləndi.');
                
                // Enable print button
                if (elements.printBtn) {
                    elements.printBtn.style.display = 'inline-flex';
                }

                // Track preview event
                trackEvent('certificate_preview', {
                    order_id: orderId,
                    certificate_type: certificateType
                });
            } else {
                showError(data.data?.message || 'Sertifikat yüklənə bilmədi.');
            }
        })
        .catch(error => {
            hideLoading();
            showError('Xəta baş verdi. Zəhmət olmasa yenidən cəhd edin.');
            console.error('Certificate preview error:', error);
        });
    }

    /**
     * Display certificate in preview area
     */
    function displayCertificate(svgContent) {
        if (!elements.previewContent || !svgContent) return;

        // Store certificate data
        currentCertificateData = svgContent;

        // Update preview content
        elements.previewContent.innerHTML = svgContent;
        
        // Add loaded animation
        elements.preview.classList.add('tif-certificate-loaded');

        // Update preview button text
        if (elements.previewBtn) {
            const icon = elements.previewBtn.querySelector('i');
            const text = elements.previewBtn.childNodes[elements.previewBtn.childNodes.length - 1];
            
            if (icon) icon.className = 'fas fa-sync-alt';
            if (text) text.textContent = ' Yenilə';
        }
    }

    /**
     * Print certificate
     */
    function printCertificate() {
        if (!currentCertificateData) {
            showError('Çap etmək üçün əvvəlcə sertifikatı önizləməni görün.');
            return;
        }

        // Create print window
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        
        if (!printWindow) {
            showError('Pop-up blocker aktivdir. Zəhmət olmasa icazə verin.');
            return;
        }

        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>TIF İanə Sertifikatı</title>
                <style>
                    body {
                        margin: 0;
                        padding: 20px;
                        font-family: Arial, sans-serif;
                        background: white;
                    }
                    .certificate-print {
                        width: 100%;
                        max-width: 800px;
                        margin: 0 auto;
                        text-align: center;
                    }
                    .certificate-print svg {
                        max-width: 100%;
                        height: auto;
                    }
                    @media print {
                        body { padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="certificate-print">
                    ${currentCertificateData}
                </div>
                <div class="no-print" style="text-align: center; margin-top: 20px;">
                    <button onclick="window.print();" style="padding: 10px 20px; font-size: 16px;">
                        Çap et
                    </button>
                    <button onclick="window.close();" style="padding: 10px 20px; font-size: 16px; margin-left: 10px;">
                        Bağla
                    </button>
                </div>
            </body>
            </html>
        `;

        printWindow.document.write(printContent);
        printWindow.document.close();

        // Auto print after load
        printWindow.onload = function() {
            setTimeout(() => {
                printWindow.print();
            }, 500);
        };

        // Track print event
        trackEvent('certificate_print', {
            order_id: elements.previewBtn?.getAttribute('data-order-id'),
            certificate_type: elements.previewBtn?.getAttribute('data-certificate-type')
        });
    }

    /**
     * Show loading spinner
     */
    function showLoading() {
        if (elements.loadingSpinner) {
            elements.loadingSpinner.style.display = 'flex';
        }
        
        if (elements.previewContent) {
            elements.previewContent.style.opacity = '0.5';
        }

        if (elements.previewBtn) {
            elements.previewBtn.disabled = true;
            elements.previewBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yüklənir...';
        }
    }

    /**
     * Hide loading spinner
     */
    function hideLoading() {
        if (elements.loadingSpinner) {
            elements.loadingSpinner.style.display = 'none';
        }
        
        if (elements.previewContent) {
            elements.previewContent.style.opacity = '1';
        }

        if (elements.previewBtn) {
            elements.previewBtn.disabled = false;
            elements.previewBtn.innerHTML = '<i class="fas fa-eye"></i> Önizləmə';
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        if (elements.errorMsg && elements.messages) {
            elements.errorMsg.textContent = message;
            elements.errorMsg.style.display = 'block';
            elements.messages.style.display = 'block';
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                elements.errorMsg.style.display = 'none';
                if (!elements.successMsg || elements.successMsg.style.display === 'none') {
                    elements.messages.style.display = 'none';
                }
            }, 5000);
        } else {
            alert(message); // Fallback
        }
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        if (elements.successMsg && elements.messages) {
            elements.successMsg.textContent = message;
            elements.successMsg.style.display = 'block';
            elements.messages.style.display = 'block';
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                elements.successMsg.style.display = 'none';
                if (!elements.errorMsg || elements.errorMsg.style.display === 'none') {
                    elements.messages.style.display = 'none';
                }
            }, 3000);
        }
    }

    /**
     * Hide all messages
     */
    function hideMessages() {
        if (elements.messages) {
            elements.messages.style.display = 'none';
        }
        if (elements.errorMsg) {
            elements.errorMsg.style.display = 'none';
        }
        if (elements.successMsg) {
            elements.successMsg.style.display = 'none';
        }
    }

    /**
     * Track events for analytics
     */
    function trackEvent(eventName, data = {}) {
        // Google Analytics tracking
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                custom_map: data
            });
        }

        // Custom tracking hook
        if (typeof tif_certificate_ajax.track_events !== 'undefined' && tif_certificate_ajax.track_events) {
            console.log('TIF Certificate Event:', eventName, data);
        }
    }

    /**
     * Utility function to format certificate data
     */
    function formatCertificateData(data) {
        if (typeof data === 'string') return data;
        
        // If SVG is wrapped in response object
        if (data.svg) return data.svg;
        
        return '';
    }

    /**
     * Check if certificate is ready for download
     */
    function isCertificateReady() {
        return currentCertificateData !== null;
    }

    /**
     * Reset certificate state
     */
    function resetCertificate() {
        currentCertificateData = null;
        
        if (elements.previewContent) {
            elements.previewContent.innerHTML = '<p class="tif-preview-text">Sertifikatı görmək üçün "Önizləmə" düyməsini basın</p>';
        }
        
        if (elements.printBtn) {
            elements.printBtn.style.display = 'none';
        }
        
        if (elements.previewBtn) {
            elements.previewBtn.innerHTML = '<i class="fas fa-eye"></i> Önizləmə';
        }
        
        elements.preview?.classList.remove('tif-certificate-loaded');
    }

    // Public API
    return {
        init: init,
        previewCertificate: previewCertificate,
        printCertificate: printCertificate,
        resetCertificate: resetCertificate,
        isCertificateReady: isCertificateReady
    };

})();

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof TIFCertificate !== 'undefined') {
            TIFCertificate.init();
        }
    });
} else {
    // DOM is already ready
    if (typeof TIFCertificate !== 'undefined') {
        TIFCertificate.init();
    }
}