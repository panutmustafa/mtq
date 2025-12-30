<footer class="mt-4 text-center text-muted">
        <p>Developed by <a href="https://panutmustafa.my.id">Panut, S.Pd.</a> | SDN Jomblang 2</p>
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle sidebar on mobile
    document.querySelector('.navbar-toggler').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
</script>
<?php include __DIR__ . '/logout_modal.php'; ?>
</body>
</html>