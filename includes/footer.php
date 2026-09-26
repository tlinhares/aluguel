    </div><!-- /.wrapper -->

    <!-- Toast container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <!-- App JS -->
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    <?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>
