// admin/assets/js/admin-scripts.js - Main Admin Scripts

$(document).ready(function() {
    'use strict';
    
    // ===== Initialize Tooltips =====
    $('[data-toggle="tooltip"]').tooltip();
    
    // ===== Initialize Popovers =====
    $('[data-toggle="popover"]').popover();
    
    // ===== Initialize Select2 =====
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Select an option'
    });
    
    // ===== DataTables Initialization =====
    $('.datatable').DataTable({
        responsive: true,
        language: {
            search: '_INPUT_',
            searchPlaceholder: 'Search...',
            lengthMenu: '_MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'No entries found',
            infoFiltered: '(filtered from _MAX_ total entries)'
        },
        pageLength: 25,
        order: [[0, 'desc']]
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
    
    // ===== AJAX Form Submission =====
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const url = form.attr('action');
        const method = form.attr('method') || 'POST';
        const data = form.serialize();
        
        // Show loading
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: url,
            method: method,
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Operation completed successfully.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Reload or redirect
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    } else if (response.reload) {
                        setTimeout(function() {
                            location.reload();
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
    
    // ===== Date Range Picker =====
    if ($.fn.daterangepicker) {
        $('.daterange').daterangepicker({
            opens: 'left',
            locale: {
                format: 'YYYY-MM-DD',
                applyLabel: 'Apply',
                cancelLabel: 'Cancel'
            }
        });
    }
    
    // ===== Print Function =====
    $('.print-btn').on('click', function() {
        window.print();
    });
    
    // ===== Export Function =====
    $('.export-btn').on('click', function() {
        const type = $(this).data('type') || 'csv';
        const table = $(this).data('table') || '.datatable';
        
        // Get table data
        const tableData = $(table).DataTable().data();
        let csv = '';
        
        // Headers
        $(table + ' thead th').each(function() {
            csv += $(this).text() + ',';
        });
        csv = csv.slice(0, -1) + '\n';
        
        // Data
        tableData.each(function(row) {
            let rowData = '';
            $(row).each(function(cell) {
                rowData += cell + ',';
            });
            rowData = rowData.slice(0, -1) + '\n';
            csv += rowData;
        });
        
        // Download
        const blob = new Blob([csv], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'export_' + Date.now() + '.csv';
        link.click();
    });
    
    // ===== Live Search =====
    let searchTimeout;
    $('.live-search').on('keyup', function() {
        clearTimeout(searchTimeout);
        const input = $(this);
        const target = input.data('target');
        const delay = input.data('delay') || 300;
        
        searchTimeout = setTimeout(function() {
            const query = input.val().trim();
            if (query.length > 2) {
                $.ajax({
                    url: input.data('url') || window.location.href,
                    method: 'GET',
                    data: { search: query },
                    dataType: 'html',
                    success: function(response) {
                        $(target).html(response);
                    }
                });
            } else if (query.length === 0) {
                // Reload original content
                location.reload();
            }
        }, delay);
    });
    
    // ===== Infinite Scroll =====
    let loading = false;
    let page = 1;
    
    $('.infinite-scroll').on('scroll', function() {
        const element = $(this);
        if (element.scrollTop() + element.innerHeight() >= element[0].scrollHeight - 100) {
            if (!loading) {
                loading = true;
                page++;
                
                $.ajax({
                    url: window.location.href,
                    method: 'GET',
                    data: { page: page },
                    dataType: 'html',
                    success: function(response) {
                        const items = $(response).find('.infinite-item');
                        if (items.length > 0) {
                            element.append(items);
                            loading = false;
                        } else {
                            // No more items
                            element.append('<div class="text-center text-muted py-3">No more items to load.</div>');
                        }
                    },
                    error: function() {
                        loading = false;
                    }
                });
            }
        }
    });
    
    // ===== Theme Toggle (Dark/Light) =====
    $('.theme-toggle').on('click', function() {
        const icon = $(this).find('i');
        const currentTheme = $('html').data('theme') || 'light';
        
        if (currentTheme === 'light') {
            $('html').attr('data-theme', 'dark');
            icon.removeClass('fa-moon').addClass('fa-sun');
            localStorage.setItem('theme', 'dark');
        } else {
            $('html').removeAttr('data-theme');
            icon.removeClass('fa-sun').addClass('fa-moon');
            localStorage.setItem('theme', 'light');
        }
    });
    
    // Load saved theme
    if (localStorage.getItem('theme') === 'dark') {
        $('html').attr('data-theme', 'dark');
        $('.theme-toggle i').removeClass('fa-moon').addClass('fa-sun');
    }
    
    // ===== Keyboard Shortcuts =====
    $(document).on('keydown', function(e) {
        // Ctrl + S = Save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('form').submit();
        }
        
        // Ctrl + P = Print
        if (e.ctrlKey && e.key === 'p') {
            window.print();
        }
        
        // Escape = Close modal
        if (e.key === 'Escape') {
            $('.modal').modal('hide');
        }
    });
    
    // ===== Refresh Notifications =====
    function refreshNotifications() {
        if (typeof loadNotifications === 'function') {
            loadNotifications();
        }
    }
    
    // Refresh notifications every 60 seconds
    setInterval(refreshNotifications, 60000);
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
    return moment(date).format(format);
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
        'on_leave': 'warning'
    };
    return map[status] || 'secondary';
}

// Get status label
function getStatusLabel(status) {
    return status.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
}

// Toast notification
function showToast(message, type = 'success', duration = 3000) {
    const toast = `
        <div class="toast-wrapper position-fixed" style="top: 80px; right: 20px; z-index: 9999;">
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-${type} text-white">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
                    <strong class="mr-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                    <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">
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

// Confirm dialog
function confirmAction(message, callback) {
    Swal.fire({
        title: 'Are you sure?',
        text: message || 'This action cannot be undone.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
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

// ===== Export for use in other scripts =====
window.StaffTraining = {
    formatCurrency,
    formatDate,
    getStatusBadgeClass,
    getStatusLabel,
    showToast,
    confirmAction,
    copyToClipboard
};