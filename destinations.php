<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Destinations';

$q   = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat = isset($_GET['category']) ? trim($_GET['category']) : '';

$sql = "SELECT * FROM destinations WHERE 1=1";
$params = [];
if ($cat !== '' && in_array($cat, $CATEGORIES, true)) {
    $sql .= " AND category = ?";
    $params[] = $cat;
} else {
    $cat = '';
}
if ($q !== '') {
    $sql .= " AND (name LIKE ? OR short_desc LIKE ? OR description LIKE ? OR address LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
$sql .= " ORDER BY name";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="page-title">
    <div class="container">
        <h1>Destinations</h1>
        <p>Tourist spots and historical sites in Inabanga.</p>
    </div>
</section>

<section class="section container">
    <form method="get" class="filter-bar">
        <input type="text" name="q" placeholder="Search destinations..." value="<?= e($q) ?>">
        <select name="category">
            <option value="">All categories</option>
            <?php foreach ($CATEGORIES as $c): ?>
            <option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Filter</button>
        <a href="<?= e(url('destinations.php')) ?>" class="btn btn-light-dark">Reset</a>
    </form>

    <p class="muted"><?= count($rows) ?> result(s) found.</p>

    <?php if ($rows): ?>
    <div class="grid">
        <?php foreach ($rows as $d) { destination_card($d); } ?>
    </div>
    <?php else: ?>
    <p class="empty">No destinations match your search.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>