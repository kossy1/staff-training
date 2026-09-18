// admin/assets/js/admin-scripts.js - Complete Admin Scripts (Fixed)
// Last Updated: 2024
// Fixed: Removed auto-submit and global reload loops

$(document).ready(function() {
    'use strict';
    
    // ============================================
    // INITIALIZE PLUGINS
    // ============================================
    
    // Initialize Tooltips
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Initialize Popovers
    if ($.fn.popover) {
        $('[data-toggle="popover"]').popover();
    }
    
    // Initialize Select2
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Select an option'
        });
    }
    
    // ============================================
    // DATATABLES INITIALIZATION
    // ============================================
    if ($.fn.DataTable) {
        $('.datatable').each(function() {
            // Skip if already initialized
            if ($.fn.DataTable.isDataTable(this)) return;
            
            $(this).DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: 'Search...',
                    lengthMenu: '_MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries found',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                    emptyTable: 'No data available'
                }
            });
        });
    }
    
    // ============================================
    // CONFIRM DELETE
    // ============================================
    $(document).on('click', '.confirm-delete', function(e) {
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
    
    // ============================================
    // AJAX FORM SUBMISSION
    // ============================================
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const url = form.attr('action');
        const method = form.attr('method') || 'POST';
        const data = new FormData(this);
        
        // Show loading on submit button
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: url,
            method: method,
            data: data,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Operation completed successfully.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Reload or redirect based on response
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    } else if (response.reload) {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Something went wrong.',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred. Please try again.',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });
    
    // ============================================
    // AUTO-HIDE ALERTS
    // ============================================
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);
    
    // ============================================
    // PASSWORD TOGGLE
    // ============================================
    $(document).on('click', '.toggle-password', function() {
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
    
    // ============================================
    // FILE INPUT PREVIEW
    // ============================================
    $(document).on('change', '.file-input-preview', function() {
        const file = this.files[0];
        const preview = $(this).data('preview');
        
        if (file && preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(preview).attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });
    
    // ============================================
    // PRINT FUNCTION
    // ============================================
    $(document).on('click', '.print-btn', function(e) {
        e.preventDefault();
        window.print();
    });
    
    // ============================================
    // EXPORT FUNCTION
    // ============================================
    $(document).on('click', '.export-btn', function() {
        const type = $(this).data('type') || 'csv';
        const table = $(this).data('table') || '.datatable';
        const filename = $(this).data('filename') || 'export';
        
        // Get table data
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(table)) {
            const tableData = $(table).DataTable().data();
            let csv = '';
            
            // Headers
            $(table + ' thead th').each(function() {
                csv += '"' + $(this).text() + '",';
            });
            csv = csv.slice(0, -1) + '\n';
            
            // Data
            tableData.each(function(row) {
                let rowData = '';
                $(row).each(function(cell) {
                    rowData += '"' + String(cell).replace(/"/g, '""') + '",';
                });
                rowData = rowData.slice(0, -1) + '\n';
                csv += rowData;
            });
            
            // Download
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename + '_' + new Date().toISOString().slice(0, 10) + '.csv';
            link.click();
        }
    });
    
    // ============================================
    // LIVE SEARCH
    // ============================================
    let searchTimeout;
    $(document).on('keyup', '.live-search', function() {
        clearTimeout(searchTimeout);
        const input = $(this);
        const target = input.data('target');
        const delay = input.data('delay') || 300;
        const url = input.data('url') || window.location.href;
        
        searchTimeout = setTimeout(function() {
            const query = input.val().trim();
            
            if (query.length >= 2) {
                $.ajax({
                    url: url,
                    method: 'GET',
                    data: { search: query },
                    dataType: 'html',
                    success: function(response) {
                        if (target) {
                            $(target).html(response);
                        }
                    }
                });
            } else if (query.length === 0) {
                // Reset when cleared
                if (target) {
                    const originalHtml = $(target).data('original') || '';
                    if (originalHtml) {
                        $(target).html(originalHtml);
                    }
                }
            }
        }, delay);
    });
    
    // ============================================
    // THEME TOGGLE
    // ============================================
    $(document).on('click', '.theme-toggle', function() {
        const icon = $(this).find('i');
        const html = $('html');
        const currentTheme = html.attr('data-theme') || 'light';
        
        if (currentTheme === 'light') {
            html.attr('data-theme', 'dark');
            icon.removeClass('fa-moon').addClass('fa-sun');
            localStorage.setItem('theme', 'dark');
            $(document).trigger('themeChanged', [true]);
        } else {
            html.removeAttr('data-theme');
            icon.removeClass('fa-sun').addClass('fa-moon');
            localStorage.setItem('theme', 'light');
            $(document).trigger('themeChanged', [false]);
        }
    });
    
    // Load saved theme
    if (localStorage.getItem('theme') === 'dark') {
        $('html').attr('data-theme', 'dark');
        $('.theme-toggle i').removeClass('fa-moon').addClass('fa-sun');
    }
    
    // ============================================
    // KEYBOARD SHORTCUTS (SAFE - NO AUTO SUBMIT)
    // ============================================
    $(document).on('keydown', function(e) {
        // Escape key closes modals and sidebar
        if (e.key === 'Escape') {
            $('.modal').modal('hide');
            
            // Close sidebar if open
            if ($('#sidebar').hasClass('open')) {
                $('#sidebar').removeClass('open');
                $('#sidebarBackdrop').removeClass('show');
            }
        }
        
        // Ctrl+B toggles sidebar (only if not in an input field)
        if (e.ctrlKey && e.key === 'b') {
            // Don't trigger if user is typing in an input
            const tag = e.target.tagName.toLowerCase();
            if (tag !== 'input' && tag !== 'textarea') {
                e.preventDefault();
                if (typeof toggleSidebar === 'function') {
                    toggleSidebar();
                }
            }
        }
        
        // Ctrl+K focuses search (only if search field exists)
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            const $search = $('#globalSearch');
            if ($search.length) {
                e.preventDefault();
                $search.focus();
            }
        }
    });
    
    // ============================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ============================================
    $('a[href^="#"]').not('[data-toggle="collapse"]').not('[data-toggle="tab"]').on('click', function(e) {
        const href = $(this).attr('href');
        if (href === '#' || href === '#!' || href === 'javascript:void(0)') return;
        
        const $target = $(href);
        if ($target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $target.offset().top - 80
            }, 500);
        }
    });
    
    // ============================================
    // STICKY HEADERS (Optional)
    // ============================================
    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 100) {
            $('.sticky-on-scroll').addClass('sticky');
        } else {
            $('.sticky-on-scroll').removeClass('sticky');
        }
    });
    
    // ============================================
    // BACK TO TOP BUTTON
    // ============================================
    if ($('#backToTop').length === 0) {
        $('body').append('<button id="backToTop" class="btn btn-primary" style="position:fixed;bottom:30px;right:30px;display:none;z-index:999;border-radius:50%;width:50px;height:50px;"><i class="fas fa-arrow-up"></i></button>');
    }
    
    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 300) {
            $('#backToTop').fadeIn();
        } else {
            $('#backToTop').fadeOut();
        }
    });
    
    $(document).on('click', '#backToTop', function() {
        $('html, body').animate({ scrollTop: 0 }, 500);
    });
    
    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    // Auto-init any .toast elements
    $('.toast').toast('show');
    
    // ============================================
    // FORM VALIDATION HELPERS
    // ============================================
    // Prevent form double submission
    $('form').on('submit', function() {
        const $btn = $(this).find('button[type="submit"]').first();
        if ($btn.length && !$btn.prop('disabled')) {
            $btn.prop('disabled', true);
            setTimeout(function() {
                $btn.prop('disabled', false);
            }, 3000);
        }
    });
    
    // ============================================
    // RESPONSIVE TABLES
    // ============================================
    $('.table-responsive').each(function() {
        if ($(this).find('table').width() > $(this).width()) {
            $(this).css('overflow-x', 'auto');
        }
    });
    
    // ============================================
    // DEBUG INFO (Only in development)
    // ============================================
    console.log('%c Admin Scripts Loaded ', 'background: #667eea; color: white; padding: 3px 8px; border-radius: 3px;');
    console.log('Version: 2.0.0 (No auto-submit/reload)');
});

// ============================================
// UTILITY FUNCTIONS (Global)
// ============================================

/**
 * Format currency
 */
function formatCurrency(amount, currency = 'NGN') {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

/**
 * Format date
 */
function formatDate(date, format = 'MMM DD, YYYY') {
    if (typeof moment !== 'undefined') {
        return moment(date).format(format);
    }
    const d = new Date(date);
    return d.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

/**
 * Get status badge class
 */
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
        'revoked': 'danger',
        'paid': 'success',
        'unpaid': 'danger',
        'failed': 'danger',
        'scheduled': 'primary',
        'approved': 'success',
        'rejected': 'danger',
        'resolved': 'info'
    };
    return map[status] || 'secondary';
}

/**
 * Show toast notification
 */
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
                <div class="toast-body">${message}</div>
            </div>
        </div>
    `;
    
    const $toast = $(toast);
    $('body').append($toast);
    
    setTimeout(function() {
        $toast.fadeOut('slow', function() {
            $(this).remove();
        });
    }, duration);
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('Copied to clipboard!', 'success', 2000);
        });
    } else {
        // Fallback
        const temp = $('<input>');
        $('body').append(temp);
        temp.val(text).select();
        document.execCommand('copy');
        temp.remove();
        showToast('Copied to clipboard!', 'success', 2000);
    }
}

/**
 * Confirm action with SweetAlert
 */
function confirmAction(message, callback) {
    Swal.fire({
        title: 'Are you sure?',
        text: message || 'This action cannot be undone.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

/**
 * Debounce function
 */
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
}

// ============================================
// EXPORT TO WINDOW
// ============================================
window.AdminApp = {
    formatCurrency,
    formatDate,
    getStatusBadgeClass,
    showToast,
    copyToClipboard,
    confirmAction,
    debounce
};