</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <p>EXPLORE INABANGA admin panel</p>
        <p><a href="<?= e(url('index.php')) ?>">Back to public site</a></p>
    </div>
</footer>
<?php if (!empty($useMap)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>
<?php
// Send the buffered page to the browser (started in admin/header.php)
if (ob_get_level() > 0) {
    ob_end_flush();
}