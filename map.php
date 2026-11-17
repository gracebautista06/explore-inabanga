<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Interactive Map';
$useMap = true;

// Collect every destination and business that has coordinates
$places = [];
$rows = $pdo->query("SELECT * FROM destinations WHERE latitude IS NOT NULL AND longitude IS NOT NULL ORDER BY name")->fetchAll();
foreach ($rows as $r) {
    $places[] = [
        'key'   => 'd' . $r['id'],
        'kind'  => 'destination',
        'name'  => $r['name'],
        'label' => $r['category'],
        'desc'  => excerpt($r['short_desc'], 110),
        'lat'   => (float)$r['latitude'],
        'lng'   => (float)$r['longitude'],
        'url'   => url('destination.php?id=' . (int)$r['id']),
    ];
}
$rows = $pdo->query("SELECT * FROM businesses WHERE latitude IS NOT NULL AND longitude IS NOT NULL ORDER BY name")->fetchAll();
foreach ($rows as $r) {
    $places[] = [
        'key'   => 'b' . $r['id'],
        'kind'  => 'business',
        'name'  => $r['name'],
        'label' => $r['type'],
        'desc'  => excerpt($r['description'], 110),
        'lat'   => (float)$r['latitude'],
        'lng'   => (float)$r['longitude'],
        'url'   => url('businesses.php#b' . (int)$r['id']),
    ];
}
include __DIR__ . '/includes/header.php';
?>
<div class="map-layout">
    <aside class="map-side">
        <div class="map-legend">
            <strong>Places (<?= count($places) ?>)</strong>
            <span><i class="dot dot-dest"></i> Destinations</span>
            <span><i class="dot dot-biz"></i> Businesses</span>
        </div>
        <ul class="place-list" id="placeList">
            <?php foreach ($places as $p): ?>
            <li data-key="<?= e($p['key']) ?>">
                <i class="dot <?= $p['kind'] === 'destination' ? 'dot-dest' : 'dot-biz' ?>"></i>
                <div>
                    <strong><?= e($p['name']) ?></strong>
                    <small><?= e($p['label']) ?></small>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>
    <div id="bigMap"></div>
</div>
<script>
    var MAP_DATA = <?= json_encode($places, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>