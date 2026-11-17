<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Heritage and History';
$records = $pdo->query("SELECT * FROM history_records ORDER BY sort_order, id")->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<section class="page-title">
    <div class="container">
        <h1>Heritage and History</h1>
        <p>A timeline of Inabanga's story.</p>
    </div>
</section>

<section class="section container">
    <?php if ($records): ?>
    <div class="timeline">
        <?php foreach ($records as $r): ?>
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-card">
                <span class="badge badge-amber"><?= e($r['period']) ?></span>
                <h3><?= e($r['title']) ?></h3>
                <?php if ($r['image'] && file_exists(UPLOAD_DIR . '/' . basename($r['image']))): ?>
                <img src="<?= e(image_src($r['image'], $r['title'], $r['id'])) ?>" alt="<?= e($r['title']) ?>">
                <?php else: ?>
                <img src="<?= e(placeholder_img($r['title'], $r['id'] + 3)) ?>" alt="<?= e($r['title']) ?>">
                <?php endif; ?>
                <p><?= nl2br(e($r['description'])) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="empty">No historical records yet.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>