<?php
// admin/messages.php - view and delete visitor messages
$pageTitle = 'Messages';
require __DIR__ . '/header.php';

// ---- Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (csrf_verify()) {
        $st = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $st->execute([(int)$_POST['delete_id']]);
        flash_set('Message deleted.');
    } else {
        flash_set('Invalid request. Please try again.', 'error');
    }
    admin_redirect('messages.php');
}

$rows = $pdo->query("SELECT * FROM messages ORDER BY id DESC")->fetchAll();
?>
<div class="toolbar">
    <h1>Visitor Messages (<?= count($rows) ?>)</h1>
</div>

<?php if ($rows): ?>
    <?php foreach ($rows as $m): ?>
    <div class="msg-card">
        <h3><?= e($m['name']) ?></h3>
        <p class="muted">
            <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a> &middot; <?= e($m['created_at']) ?>
        </p>
        <p><?= nl2br(e($m['message'])) ?></p>
        <form method="post" data-confirm="Delete this message?">
            <?= csrf_field() ?>
            <input type="hidden" name="delete_id" value="<?= (int)$m['id'] ?>">
            <button type="submit" class="btn btn-small btn-danger">Delete</button>
        </form>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="empty">No messages yet. Messages sent from the "About &amp; Contact" page appear here.</p>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>