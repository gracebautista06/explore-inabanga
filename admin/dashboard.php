<?php
// admin/dashboard.php - counts, recent messages, change password
$pageTitle = 'Dashboard';
require __DIR__ . '/header.php';

$pwErrors = [];

// ---- Change password ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!csrf_verify()) {
        $pwErrors[] = 'Your session form expired. Please reload the page and try again.';
    } else {
        $current = isset($_POST['current_password']) ? (string)$_POST['current_password'] : '';
        $new     = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

        $st = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
        $st->execute([(int)$_SESSION['admin_id']]);
        $row = $st->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $pwErrors[] = 'Your current password is incorrect.';
        }
        if (strlen($new) < 8) {
            $pwErrors[] = 'The new password must be at least 8 characters long.';
        }
        if ($new !== $confirm) {
            $pwErrors[] = 'The new password and confirmation do not match.';
        }
        if (!$pwErrors) {
            $st = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
            $st->execute([password_hash($new, PASSWORD_DEFAULT), (int)$_SESSION['admin_id']]);
            flash_set('Password changed successfully.');
            admin_redirect('dashboard.php');
        }
    }
}

// ---- Counts ----
$cDest = (int)$pdo->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$cBiz  = (int)$pdo->query("SELECT COUNT(*) FROM businesses")->fetchColumn();
$cHist = (int)$pdo->query("SELECT COUNT(*) FROM history_records")->fetchColumn();
$cMsg  = (int)$pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

$recent = $pdo->query("SELECT * FROM messages ORDER BY id DESC LIMIT 5")->fetchAll();

// Is the default password still in use?
$st = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
$st->execute([(int)$_SESSION['admin_id']]);
$me = $st->fetch();
$usingDefault = $me && password_verify('admin123', $me['password_hash']);
?>
<h1>Dashboard</h1>

<?php if ($usingDefault): ?>
<div class="alert alert-warn">
    <strong>Security notice:</strong> you are still using the default password (admin123).
    Please change it using the form at the bottom of this page.
</div>
<?php endif; ?>

<div class="stats">
    <div class="stat"><strong><?= $cDest ?></strong>Destinations<br><a href="<?= e(url('admin/destinations.php')) ?>">Manage</a></div>
    <div class="stat"><strong><?= $cBiz ?></strong>Businesses<br><a href="<?= e(url('admin/businesses.php')) ?>">Manage</a></div>
    <div class="stat"><strong><?= $cHist ?></strong>History records<br><a href="<?= e(url('admin/history.php')) ?>">Manage</a></div>
    <div class="stat"><strong><?= $cMsg ?></strong>Messages<br><a href="<?= e(url('admin/messages.php')) ?>">View</a></div>
</div>

<h2>Latest messages</h2>
<?php if ($recent): ?>
    <?php foreach ($recent as $m): ?>
    <div class="msg-card">
        <h3><?= e($m['name']) ?> <small class="muted">&lt;<?= e($m['email']) ?>&gt; &middot; <?= e($m['created_at']) ?></small></h3>
        <p><?= nl2br(e(excerpt($m['message'], 200))) ?></p>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="empty">No messages yet.</p>
<?php endif; ?>

<h2>Change password</h2>
<?php if ($pwErrors): ?>
<div class="alert alert-error">
    <?php foreach ($pwErrors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>
<form method="post" class="form-grid" action="<?= e(url('admin/dashboard.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="change_password" value="1">
    <label class="full">Current password
        <input type="password" name="current_password" required>
    </label>
    <label>New password (min. 8 characters)
        <input type="password" name="new_password" minlength="8" required>
    </label>
    <label>Confirm new password
        <input type="password" name="confirm_password" minlength="8" required>
    </label>
    <div class="full"><button type="submit" class="btn">Change password</button></div>
</form>
<?php include __DIR__ . '/footer.php'; ?>