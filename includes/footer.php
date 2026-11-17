</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <p>&copy; <?= date('Y') ?> EXPLORE INABANGA: Tourism and Heritage Information System. Municipality of Inabanga, Bohol, Philippines.</p>
        <p><a href="<?= e(url('admin/login.php')) ?>">Admin login</a></p>
    </div>
</footer>
<?php if (!empty($useMap)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>