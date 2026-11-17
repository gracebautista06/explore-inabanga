<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Local Businesses';

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
if ($type !== '' && in_array($type, $BUSINESS_TYPES, true)) {
    $st = $pdo->prepare("SELECT * FROM businesses WHERE type = ? ORDER BY name");
    $st->execute([$type]);
} else {
    $type = '';
    $st = $pdo->query("SELECT * FROM businesses ORDER BY name");
}
$rows = $st->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="page-title">
    <div class="container">
        <h1>Local Businesses</h1>
        <p>Support local: eateries, stays, crafts, and tour services.</p>
    </div>
</section>

<section class="section container">
    <form method="get" class="filter-bar">
        <select name="type">
            <option value="">All types</option>
            <?php foreach ($BUSINESS_TYPES as $t): ?>
            <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Filter</button>
        <a href="<?= e(url('businesses.php')) ?>" class="btn btn-light-dark">Reset</a>
    </form>

    <p class="muted"><?= count($rows) ?> result(s) found.</p>

    <?php if ($rows): ?>
    <div class="grid">
        <?php foreach ($rows as $b): ?>
        <article class="card" id="b<?= (int)$b['id'] ?>">
            <img src="<?= e(image_src($b['image'], $b['name'], $b['id'] + 2)) ?>" alt="<?= e($b['name']) ?>">
            <div class="card-body">
                <span class="badge badge-blue"><?= e($b['type']) ?></span>
                <h3><?= e($b['name']) ?></h3>
                <p><?= e($b['description']) ?></p>
                <ul class="info-list">
                    <?php if ($b['address']): ?><li><strong>Address:</strong> <?= e($b['address']) ?></li><?php endif; ?>
                    <?php if ($b['contact']): ?><li><strong>Contact:</strong> <?= e($b['contact']) ?></li><?php endif; ?>
                    <?php if ($b['email']): ?><li><strong>Email:</strong> <a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></li><?php endif; ?>
                </ul>
                <?php if ($b['latitude'] !== null && $b['longitude'] !== null && $b['latitude'] !== ''): ?>
                <a class="btn btn-small" href="<?= e(url('map.php#b' . (int)$b['id'])) ?>">View on map</a>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="empty">No businesses found for this filter.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>