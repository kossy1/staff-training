// assets/js/main.js - Main JavaScript Functions

$(document).ready(function() {
    'use strict';
    
    // ===== Initialize Tooltips =====
    $('[data-toggle="tooltip"]').tooltip();
    
    // ===== Initialize Popovers =====
    $('[data-toggle="popover"]').popover();
    
    // ===== Auto-hide Alerts =====
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);
    
    // ===== Password Toggle =====
    $('.toggle-password').on('click', function() {
        const input = $(this).closest('.input-group').find('input');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // ===== Confirm Delete =====
    $('.confirm-delete').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const message = $(this).data('message') || 'Are you sure you want to delete this item?';
        
        Swal.fire({
            title: 'Are you sure?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
    
    // ===== File Input Preview =====
    $('.file-input-preview').on('change', function() {
        const file = this.files[0];
        const preview = $(this).data('preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(preview).attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });
    
    // ===== Print Function =====
    $('.print-btn').on('click', function() {
        window.print();
    });
    
    // ===== Back to Top =====
    $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
            $('#backToTop').fadeIn();
        } else {
            $('#backToTop').fadeOut();
        }
    });
    
    $('#backToTop').on('click', function() {
        $('html, body').animate({ scrollTop: 0 }, 500);
    });
});

// ===== Utility Functions =====

// Format currency
function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// Format date
function formatDate(date, format = 'MMM DD, YYYY') {
    if (typeof moment !== 'undefined') {
        return moment(date).format(format);
    }
    return new Date(date).toLocaleDateString();
}

// Get status badge class
function getStatusBadgeClass(status) {
    const map = {
        'active': 'success',
        'inactive': 'danger',
        'pending': 'warning',
        'completed': 'success',
        'cancelled': 'danger',
        'ongoing': 'info',
        'upcoming': 'primary',
        'enrolled': 'info',
        'in_progress': 'warning',
        'dropped': 'danger',
        'on_leave': 'warning',
        'expired': 'danger',
        'revoked': 'danger'
    };
    return map[status] || 'secondary';
}

// Show toast notification
function showToast(message, type = 'success', duration = 3000) {
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    
    const toast = `
        <div class="toast-wrapper position-fixed" style="top: 80px; right: 20px; z-index: 9999;">
            <div class="toast show" role="alert">
                <div class="toast-header bg-${type} text-white">
                    <i class="fas ${icons[type] || 'fa-info-circle'} mr-2"></i>
                    <strong class="mr-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                    <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        </div>
    `;
    
    $('body').append(toast);
    
    setTimeout(function() {
        $('.toast-wrapper').fadeOut('slow', function() {
            $(this).remove();
        });
    }, duration);
}

// Copy to clipboard
function copyToClipboard(text) {
    const temp = $('<input>');
    $('body').append(temp);
    temp.val(text).select();
    document.execCommand('copy');
    temp.remove();
    showToast('Copied to clipboard!', 'success', 2000);
}

// Get URL parameters
function getUrlParams() {
    const params = {};
    window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
        params[key] = decodeURIComponent(value);
    });
    return params;
}

// Validate email
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate phone number
function isValidPhone(phone) {
    const re = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
    return re.test(phone);
}

// Get file extension
function getFileExtension(filename) {
    return filename.split('.').pop().toLowerCase();
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };

    // assets/js/main.js - Add Naira currency functions

/**
 * Format Naira currency
 */
function formatNaira(amount, withSymbol = true) {
    const formatted = Number(amount).toLocaleString('en-NG', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return withSymbol ? '₦' + formatted : formatted;
}

/**
 * Format Naira with short notation
 */
function formatNairaShort(amount) {
    const num = Number(amount);
    if (num >= 1000000000) {
        return '₦' + (num / 1000000000).toFixed(1) + 'B';
    }
    if (num >= 1000000) {
        return '₦' + (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return '₦' + (num / 1000).toFixed(1) + 'K';
    }
    return '₦' + num.toFixed(2);
}

/**
 * Parse Naira string to number
 */
function parseNaira(nairaString) {
    const cleaned = nairaString.replace(/[₦,]/g, '').trim();
    return parseFloat(cleaned) || 0;
}

/**
 * Get currency symbol
 */
function getCurrencySymbol() {
    return '₦';
}

/**
 * Get currency code
 */
function getCurrencyCode() {
    return 'NGN';
}

/**
 * Get currency name
 */
function getCurrencyName() {
    return 'Naira';
}

// Update formatCurrency to use Naira
function formatCurrency(amount) {
    return formatNaira(amount);
}
}