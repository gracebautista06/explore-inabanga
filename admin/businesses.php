<?php
// admin/businesses.php - list, add, edit, delete local businesses
$pageTitle = 'Businesses';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
if (!in_array($action, ['list', 'add', 'edit'], true)) {
    $action = 'list';
}
$useMap = ($action !== 'list');
require __DIR__ . '/header.php';

// ---- Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (csrf_verify()) {
        $delId = (int)$_POST['delete_id'];
        $st = $pdo->prepare("SELECT image FROM businesses WHERE id = ?");
        $st->execute([$delId]);
        $row = $st->fetch();
        if ($row) {
            if ($row['image'] && file_exists(UPLOAD_DIR . '/' . basename($row['image']))) {
                @unlink(UPLOAD_DIR . '/' . basename($row['image']));
            }
            $st = $pdo->prepare("DELETE FROM businesses WHERE id = ?");
            $st->execute([$delId]);
            flash_set('Business deleted.');
        }
    } else {
        flash_set('Invalid request. Please try again.', 'error');
    }
    admin_redirect('businesses.php');
}

$errors = [];
$uploadError = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$f = [
    'name' => '', 'type' => $BUSINESS_TYPES[0], 'description' => '', 'address' => '',
    'contact' => '', 'email' => '', 'latitude' => '', 'longitude' => '', 'image' => '',
];

if ($action === 'edit') {
    $st = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
    $st->execute([$id]);
    $existing = $st->fetch();
    if (!$existing) {
        flash_set('Business not found.', 'error');
        admin_redirect('businesses.php');
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
        foreach (['name', 'type', 'description', 'address', 'contact', 'email', 'latitude', 'longitude'] as $k) {
            $in[$k] = isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
        }
        $f = array_merge($f, $in);

        if ($in['name'] === '' || strlen($in['name']) > 150) {
            $errors[] = 'Name is required (max 150 characters).';
        }
        if (!in_array($in['type'], $BUSINESS_TYPES, true)) {
            $errors[] = 'Please choose a valid business type.';
        }
        if (strlen($in['contact']) > 50) {
            $errors[] = 'Contact number must be 50 characters or less.';
        }
        if ($in['email'] !== '' && (!filter_var($in['email'], FILTER_VALIDATE_EMAIL) || strlen($in['email']) > 150)) {
            $errors[] = 'Please enter a valid email address (or leave it empty).';
        }
        $latBlank = ($in['latitude'] === '');
        $lngBlank = ($in['longitude'] === '');
        if ($latBlank !== $lngBlank) {
            $errors[] = 'Enter both latitude and longitude, or leave both empty.';
        } elseif (!$latBlank) {
            if (!is_numeric($in['latitude']) || !is_numeric($in['longitude'])
                || (float)$in['latitude'] < -90 || (float)$in['latitude'] > 90
                || (float)$in['longitude'] < -180 || (float)$in['longitude'] > 180) {
                $errors[] = 'Latitude must be between -90 and 90, and longitude between -180 and 180.';
            }
        }

        if (!$errors) {
            $img = handle_upload('image', $uploadError, $f['image']);
            if ($img === false) {
                $errors[] = $uploadError;
            } else {
                $lat = $latBlank ? null : (float)$in['latitude'];
                $lng = $lngBlank ? null : (float)$in['longitude'];
                if ($action === 'add') {
                    $st = $pdo->prepare("INSERT INTO businesses
                        (name, type, description, address, contact, email, latitude, longitude, image)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $st->execute([$in['name'], $in['type'], $in['description'], $in['address'],
                        $in['contact'], $in['email'], $lat, $lng, $img]);
                    flash_set('Business added.');
                } else {
                    $st = $pdo->prepare("UPDATE businesses SET
                        name = ?, type = ?, description = ?, address = ?, contact = ?, email = ?,
                        latitude = ?, longitude = ?, image = ? WHERE id = ?");
                    $st->execute([$in['name'], $in['type'], $in['description'], $in['address'],
                        $in['contact'], $in['email'], $lat, $lng, $img, $id]);
                    flash_set('Business updated.');
                }
                admin_redirect('businesses.php');
            }
        }
    }
}
?>

<?php if ($action === 'list'): ?>
<?php $rows = $pdo->query("SELECT * FROM businesses ORDER BY id DESC")->fetchAll(); ?>
<div class="toolbar">
    <h1>Local Businesses</h1>
    <a class="btn" href="<?= e(url('admin/businesses.php?action=add')) ?>">+ Add business</a>
</div>
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th>Photo</th><th>Name</th><th>Type</th><th>Contact</th><th>Location</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><img class="thumb" src="<?= e(image_src($r['image'], $r['name'], $r['id'] + 2)) ?>" alt=""></td>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['type']) ?></td>
                <td><?= e($r['contact']) ?></td>
                <td><?= ($r['latitude'] !== null && $r['longitude'] !== null) ? e($r['latitude']) . ', ' . e($r['longitude']) : '&mdash;' ?></td>
                <td class="actions">
                    <a class="btn btn-small" href="<?= e(url('admin/businesses.php?action=edit&id=' . (int)$r['id'])) ?>">Edit</a>
                    <form method="post" data-confirm="Delete this business? This cannot be undone.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="6">No businesses yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
<div class="toolbar">
    <h1><?= $action === 'add' ? 'Add business' : 'Edit business' ?></h1>
    <a class="btn btn-light-dark" href="<?= e(url('admin/businesses.php')) ?>">&larr; Back to list</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <label class="full">Business name *
        <input type="text" name="name" maxlength="150" required value="<?= e($f['name']) ?>">
    </label>
    <label>Type *
        <select name="type">
            <?php foreach ($BUSINESS_TYPES as $t): ?>
            <option value="<?= e($t) ?>" <?= $f['type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Contact number
        <input type="text" name="contact" maxlength="50" value="<?= e($f['contact']) ?>">
    </label>
    <label class="full">Email
        <input type="email" name="email" maxlength="150" value="<?= e($f['email']) ?>">
    </label>
    <label class="full">Description
        <textarea name="description" rows="5"><?= e($f['description']) ?></textarea>
    </label>
    <label class="full">Address
        <input type="text" name="address" maxlength="200" value="<?= e($f['address']) ?>">
    </label>

    <label>Latitude
        <input type="text" id="latitude" name="latitude" inputmode="decimal" placeholder="e.g. 9.9833" value="<?= e($f['latitude']) ?>">
    </label>
    <label>Longitude
        <input type="text" id="longitude" name="longitude" inputmode="decimal" placeholder="e.g. 124.0667" value="<?= e($f['longitude']) ?>">
    </label>
    <div class="full">
        <p class="hint">
            <strong>How to get coordinates:</strong> click the map below to fill them in automatically, or
            in <em>Google Maps</em> right-click the place and click the numbers at the top of the menu to copy them
            (latitude first, then longitude), or in <em>OpenStreetMap</em> right-click the place and choose "Show address".
            Leave both empty and the business will not appear on the map.
        </p>
        <div id="pickerMap" class="picker-map"></div>
    </div>

    <label class="full">Photo
        <?php if ($f['image']): ?>
        <img src="<?= e(image_src($f['image'], $f['name'], $id + 2)) ?>" alt="Current photo" style="max-width:220px;border-radius:8px;margin:6px 0;">
        <?php endif; ?>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <span class="hint">JPG, PNG, or WEBP, max 2MB. <?= $f['image'] ? 'Choose a new file to replace the current photo.' : 'If empty, a placeholder image is shown.' ?></span>
    </label>

    <div class="full">
        <button type="submit" class="btn">Save business</button>
        <a class="btn btn-light-dark" href="<?= e(url('admin/businesses.php')) ?>">Cancel</a>
    </div>
</form>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>