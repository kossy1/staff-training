<?php
// admin/includes/footer.php - Admin Panel Footer
?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer-admin">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-code"></i> Made with <i class="fas fa-heart text-danger"></i> by Kossyvibes
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <!-- Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    
    <!-- Bootstrap 4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    
    <!-- Custom Admin Scripts -->
    <script src="assets/js/admin-scripts.js"></script>
    <script src="assets/js/charts.js"></script>
    
    <!-- Page Specific Scripts -->
    <?php if (isset($page_scripts)) echo $page_scripts; ?>
    
    <!-- CSRF Token -->
    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
    </script>
</body>
</html>

<style>
.footer-admin {
    background: white;
    padding: 20px 0;
    border-top: 1px solid #e2e8f0;
    margin-top: 30px;
}
.footer-admin p {
    font-size: 0.9rem;
}
@media (max-width: 768px) {
    .footer-admin {
        text-align: center;
    }
    .footer-admin .col-md-6 {
        margin-bottom: 5px;
    }
}
</style>