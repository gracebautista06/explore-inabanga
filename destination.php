<?php
require_once __DIR__ . '/includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$st = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
$st->execute([$id]);
$d = $st->fetch();

if (!$d) {
    http_response_code(404);
    $pageTitle = 'Not found';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section container"><h1>Destination not found</h1>'
       . '<p><a class="btn" href="' . e(url('destinations.php')) . '">Back to destinations</a></p></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $d['name'];
$useMap = ($d['latitude'] !== null && $d['longitude'] !== null && $d['latitude'] !== '' && $d['longitude'] !== '');
$img = image_src($d['image'], $d['name'], $d['id']);
include __DIR__ . '/includes/header.php';
?>
<section class="section container">
    <p><a href="<?= e(url('destinations.php')) ?>">&larr; Back to destinations</a></p>
    <div class="detail">
        <div class="detail-main">
            <img class="detail-img" src="<?= e($img) ?>" alt="<?= e($d['name']) ?>">
            <span class="badge"><?= e($d['category']) ?></span>
            <h1><?= e($d['name']) ?></h1>
            <h2>Description</h2>
            <p><?= nl2br(e($d['description'])) ?></p>
            <h2>History and Significance</h2>
            <p><?= nl2br(e($d['history'])) ?></p>
        </div>
        <aside class="detail-side">
            <div class="panel">
                <h3>Address</h3>
                <p><?= e($d['address']) ?></p>
                <?php if ($useMap): ?>
                <p class="muted">Coordinates: <?= e($d['latitude']) ?>, <?= e($d['longitude']) ?></p>
                <p>
                    <a class="btn btn-small" href="<?= e(url('map.php#d' . (int)$d['id'])) ?>">View on full map</a>
                    <a class="btn btn-small btn-light-dark" target="_blank" rel="noopener"
                       href="https://www.openstreetmap.org/?mlat=<?= e($d['latitude']) ?>&amp;mlon=<?= e($d['longitude']) ?>#map=17/<?= e($d['latitude']) ?>/<?= e($d['longitude']) ?>">Open in OpenStreetMap</a>
                </p>
                <?php endif; ?>
            </div>
            <?php if ($useMap): ?>
            <div id="detailMap" class="small-map"
                 data-lat="<?= e($d['latitude']) ?>" data-lng="<?= e($d['longitude']) ?>"
                 data-name="<?= e($d['name']) ?>"></div>
            <?php endif; ?>
        </aside>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>