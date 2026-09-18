<?php
// trainer/includes/footer.php - Trainer Panel Footer
// This file closes the wrapper div opened in header.php
?>
        </div><!-- end .main-content if opened in page -->
    </div><!-- end .wrapper -->

    <!-- Footer -->
    <footer class="footer-trainer">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted small">
                        &copy; <?php echo date('Y'); ?> 
                        <strong>THE POLYTECHNIC, IBADAN</strong> - SKILL DEVELOPMENT CENTRE. 
                        All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p class="mb-0 text-muted small">
                        <i class="fas fa-user-tie text-primary"></i> 
                        Logged in as 
                        <strong><?php echo htmlspecialchars($full_name ?? $_SESSION['username'] ?? 'Trainer'); ?></strong>
                        <span class="mx-2">|</span>
                        <span class="badge badge-info">Trainer</span>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- ============================================ -->
    <!-- SCRIPTS -->
    <!-- ============================================ -->
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <!-- Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    
    <!-- Bootstrap 4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    
    <!-- Custom Trainer Scripts -->
    <script>
    // ============================================
    // TRAINER PANEL COMMON SCRIPTS
    // ============================================
    (function() {
        'use strict';
        
        // ===== SIDEBAR CONTROLS =====
        window.toggleSidebar = function() {
            var sidebar = document.getElementById('sidebar');
            var backdrop = document.getElementById('sidebarBackdrop');
            
            if (!sidebar) return;
            
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        };
        
        window.openSidebar = function() {
            var sidebar = document.getElementById('sidebar');
            var backdrop = document.getElementById('sidebarBackdrop');
            
            if (sidebar) {
                sidebar.classList.add('open');
                sidebar.style.transform = 'translateX(0)';
            }
            if (backdrop) {
                backdrop.classList.add('show');
                backdrop.style.display = 'block';
            }
            document.body.style.overflow = 'hidden';
        };
        
        window.closeSidebar = function() {
            var sidebar = document.getElementById('sidebar');
            var backdrop = document.getElementById('sidebarBackdrop');
            
            if (sidebar) {
                sidebar.classList.remove('open');
                sidebar.style.transform = 'translateX(-100%)';
            }
            if (backdrop) {
                backdrop.classList.remove('show');
                backdrop.style.display = 'none';
            }
            document.body.style.overflow = '';
        };
        
        // ===== AUTO-HIDE ALERTS =====
        setTimeout(function() {
            document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
        
        // ===== CONFIRM DELETE HELPER =====
        window.confirmDelete = function(message, callback) {
            if (typeof Swal === 'undefined') {
                if (confirm(message || 'Are you sure?')) {
                    if (typeof callback === 'function') callback();
                }
                return;
            }
            
            Swal.fire({
                title: 'Are you sure?',
                text: message || 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, proceed!',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed && typeof callback === 'function') {
                    callback();
                }
            });
        };
        
        // ===== TOAST NOTIFICATION HELPER =====
        window.showToast = function(message, type, duration) {
            type = type || 'success';
            duration = duration || 3000;
            
            if (typeof Swal === 'undefined') {
                alert(message);
                return;
            }
            
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: duration,
                timerProgressBar: true,
                didOpen: function(toast) {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            
            Toast.fire({
                icon: type,
                title: message
            });
        };
        
        // ===== FORMAT CURRENCY =====
        window.formatNaira = function(amount) {
            return '₦' + parseFloat(amount || 0).toLocaleString('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };
        
        // ===== COPY TO CLIPBOARD =====
        window.copyToClipboard = function(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    if (typeof showToast === 'function') {
                        showToast('Copied to clipboard!', 'success', 2000);
                    }
                });
            } else {
                // Fallback
                var temp = document.createElement('textarea');
                temp.value = text;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                if (typeof showToast === 'function') {
                    showToast('Copied to clipboard!', 'success', 2000);
                }
            }
        };
        
        // ===== INITIALIZE DATATABLES =====
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            // Only initialize tables without explicit data-table-init="false"
            $('table.datatable:not([data-table-init="false"])').each(function() {
                if (!$.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable({
                        responsive: true,
                        pageLength: 15,
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
                }
            });
        }
        
        // ===== GLOBAL ERROR HANDLER =====
        window.addEventListener('error', function(e) {
            // Only log errors, don't do anything drastic
            console.error('Trainer panel error:', e.message);
        });
        
        // ===== KEYBOARD SHORTCUTS =====
        document.addEventListener('keydown', function(e) {
            // Escape key closes sidebar
            if (e.key === 'Escape') {
                var sidebar = document.getElementById('sidebar');
                if (sidebar && sidebar.classList.contains('open')) {
                    closeSidebar();
                }
                
                // Close open modals
                if (typeof $ !== 'undefined') {
                    $('.modal').modal('hide');
                }
            }
        });
        
        console.log('%c Trainer Panel Loaded ', 'background: #667eea; color: white; padding: 3px 8px; border-radius: 3px;');
    })();
    </script>
    
    <!-- Page Specific Scripts -->
    <?php if (isset($page_scripts)) echo $page_scripts; ?>
    
    <!-- CSRF Token for AJAX requests -->
    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
    </script>
    
</body>
</html>

<style>
/* ===== FOOTER STYLES ===== */
.footer-trainer {
    background: white;
    padding: 15px 0;
    border-top: 1px solid #e2e8f0;
    margin-top: 30px;
    transition: all 0.3s ease;
}

.footer-trainer .container-fluid {
    padding-left: 30px;
    padding-right: 30px;
}

.footer-trainer p {
    line-height: 1.6;
}

.footer-trainer .badge {
    padding: 4px 10px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Desktop: Offset for sidebar */
@media (min-width: 992px) {
    .footer-trainer {
        margin-left: 260px;
    }
}

/* Mobile */
@media (max-width: 991.98px) {
    .footer-trainer .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .footer-trainer {
        text-align: center;
    }
    
    .footer-trainer .col-md-6 {
        margin-bottom: 8px;
    }
    
    .footer-trainer .col-md-6:last-child {
        margin-bottom: 0;
    }
    
    .footer-trainer .text-md-right {
        text-align: center !important;
    }
}

/* Small phones */
@media (max-width: 576px) {
    .footer-trainer {
        padding: 12px 0;
    }
    
    .footer-trainer p {
        font-size: 0.75rem;
        line-height: 1.5;
    }
    
    .footer-trainer .badge {
        font-size: 0.65rem;
        padding: 3px 8px;
    }
    
    .footer-trainer p .mx-2 {
        display: none;
    }
}

/* Print */
@media print {
    .footer-trainer {
        display: none;
    }
}
</style>