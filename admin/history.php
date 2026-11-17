<?php
// admin/history.php - list, add, edit, delete history (timeline) records
$pageTitle = 'History Records';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
if (!in_array($action, ['list', 'add', 'edit'], true)) {
    $action = 'list';
}
$useMap = false; // no coordinates for history records
require __DIR__ . '/header.php';

// ---- Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (csrf_verify()) {
        $delId = (int)$_POST['delete_id'];
        $st = $pdo->prepare("SELECT image FROM history_records WHERE id = ?");
        $st->execute([$delId]);
        $row = $st->fetch();
        if ($row) {
            if ($row['image'] && file_exists(UPLOAD_DIR . '/' . basename($row['image']))) {
                @unlink(UPLOAD_DIR . '/' . basename($row['image']));
            }
            $st = $pdo->prepare("DELETE FROM history_records WHERE id = ?");
            $st->execute([$delId]);
            flash_set('History record deleted.');
        }
    } else {
        flash_set('Invalid request. Please try again.', 'error');
    }
    admin_redirect('history.php');
}

$errors = [];
$uploadError = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nextOrder = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM history_records")->fetchColumn();
$f = ['title' => '', 'period' => '', 'description' => '', 'sort_order' => $nextOrder, 'image' => ''];

if ($action === 'edit') {
    $st = $pdo->prepare("SELECT * FROM history_records WHERE id = ?");
    $st->execute([$id]);
    $existing = $st->fetch();
    if (!$existing) {
        flash_set('History record not found.', 'error');
        admin_redirect('history.php');
    }
    $f = array_merge($f, $existing);
}

// ---- Save (add / edit) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'list') {
    if (empty($_POST)) {
        $errors[] = 'The upload was too large for the server. Please use an image under 2MB.';
    } elseif (!csrf_verify()) {
        $errors[] = 'Your session form expired. Please reload the page and try again.';
    } else {
        $in = [];
        foreach (['title', 'period', 'description', 'sort_order'] as $k) {
            $in[$k] = isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
        }
        $f = array_merge($f, $in);

        if ($in['title'] === '' || strlen($in['title']) > 150) {
            $errors[] = 'Title is required (max 150 characters).';
        }
        if (strlen($in['period']) > 100) {
            $errors[] = 'Year / period must be 100 characters or less.';
        }
        if ($in['description'] === '') {
            $errors[] = 'Description is required.';
        }
        if ($in['sort_order'] === '' || !preg_match('/^-?\d{1,6}$/', $in['sort_order'])) {
            $errors[] = 'Sort order must be a whole number.';
        }

        if (!$errors) {
            $img = handle_upload('image', $uploadError, $f['image']);
            if ($img === false) {
                $errors[] = $uploadError;
            } else {
                if ($action === 'add') {
                    $st = $pdo->prepare("INSERT INTO history_records (title, period, description, image, sort_order)
                        VALUES (?, ?, ?, ?, ?)");
                    $st->execute([$in['title'], $in['period'], $in['description'], $img, (int)$in['sort_order']]);
                    flash_set('History record added.');
                } else {
                    $st = $pdo->prepare("UPDATE history_records SET
                        title = ?, period = ?, description = ?, image = ?, sort_order = ? WHERE id = ?");
                    $st->execute([$in['title'], $in['period'], $in['description'], $img, (int)$in['sort_order'], $id]);
                    flash_set('History record updated.');
                }
                admin_redirect('history.php');
            }
        }
    }
}
?>

<?php if ($action === 'list'): ?>
<?php $rows = $pdo->query("SELECT * FROM history_records ORDER BY sort_order, id")->fetchAll(); ?>
<div class="toolbar">
    <h1>History Records</h1>
    <a class="btn" href="<?= e(url('admin/history.php?action=add')) ?>">+ Add record</a>
</div>
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th>Order</th><th>Photo</th><th>Title</th><th>Year / period</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= (int)$r['sort_order'] ?></td>
                <td><img class="thumb" src="<?= e(image_src($r['image'], $r['title'], $r['id'] + 3)) ?>" alt=""></td>
                <td><?= e($r['title']) ?></td>
                <td><?= e($r['period']) ?></td>
                <td class="actions">
                    <a class="btn btn-small" href="<?= e(url('admin/history.php?action=edit&id=' . (int)$r['id'])) ?>">Edit</a>
                    <form method="post" data-confirm="Delete this history record? This cannot be undone.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="5">No history records yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
<div class="toolbar">
    <h1><?= $action === 'add' ? 'Add history record' : 'Edit history record' ?></h1>
    <a class="btn btn-light-dark" href="<?= e(url('admin/history.php')) ?>">&larr; Back to list</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <label class="full">Title *
        <input type="text" name="title" maxlength="150" required value="<?= e($f['title']) ?>">
    </label>
    <label>Year / period
        <input type="text" name="period" maxlength="100" placeholder="e.g. Spanish colonial era" value="<?= e($f['period']) ?>">
    </label>
    <label>Sort order <span class="hint">(lower numbers appear first on the timeline)</span>
        <input type="number" name="sort_order" step="1" value="<?= e($f['sort_order']) ?>">
    </label>
    <label class="full">Description *
        <textarea name="description" rows="7" required><?= e($f['description']) ?></textarea>
    </label>
    <label class="full">Photo
        <?php if ($f['image']): ?>
        <img src="<?= e(image_src($f['image'], $f['title'], $id + 3)) ?>" alt="Current photo" style="max-width:220px;border-radius:8px;margin:6px 0;">
        <?php endif; ?>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <span class="hint">JPG, PNG, or WEBP, max 2MB. <?= $f['image'] ? 'Choose a new file to replace the current photo.' : 'If empty, a placeholder image is shown.' ?></span>
    </label>
    <div class="full">
        <button type="submit" class="btn">Save record</button>
        <a class="btn btn-light-dark" href="<?= e(url('admin/history.php')) ?>">Cancel</a>
    </div>
</form>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>