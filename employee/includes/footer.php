<?php
// employee/includes/footer.php - Employee Footer
?>
        </div><!-- end .main-content (if opened in page) -->
    </div><!-- end .wrapper -->
    
    <footer class="footer-employee">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted small">
                        &copy; <?php echo date('Y'); ?> THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p class="mb-0 text-muted small">
                        <i class="fas fa-user"></i> Logged in as <?php echo htmlspecialchars($full_name ?? 'Employee'); ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <script src="assets/js/employee-scripts.js"></script>
    
    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
    </script>
</body>
</html>

<style>
.footer-employee {
    background: white;
    padding: 15px 0;
    border-top: 1px solid #e2e8f0;
    margin-top: 30px;
}
@media (max-width: 768px) {
    .footer-employee { text-align: center; }
    .footer-employee .col-md-6 { margin-bottom: 5px; }
}
</style>