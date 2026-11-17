<?php
// admin/destinations.php - list, add, edit, delete destinations
$pageTitle = 'Destinations';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
if (!in_array($action, ['list', 'add', 'edit'], true)) {
    $action = 'list';
}
$useMap = ($action !== 'list'); // Leaflet is only needed on the form
require __DIR__ . '/header.php';

// ---- Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (csrf_verify()) {
        $delId = (int)$_POST['delete_id'];
        $st = $pdo->prepare("SELECT image FROM destinations WHERE id = ?");
        $st->execute([$delId]);
        $row = $st->fetch();
        if ($row) {
            if ($row['image'] && file_exists(UPLOAD_DIR . '/' . basename($row['image']))) {
                @unlink(UPLOAD_DIR . '/' . basename($row['image']));
            }
            $st = $pdo->prepare("DELETE FROM destinations WHERE id = ?");
            $st->execute([$delId]);
            flash_set('Destination deleted.');
        }
    } else {
        flash_set('Invalid request. Please try again.', 'error');
    }
    admin_redirect('destinations.php');
}

$errors = [];
$uploadError = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$f = [
    'name' => '', 'category' => $CATEGORIES[0], 'short_desc' => '', 'description' => '',
    'history' => '', 'address' => '', 'latitude' => '', 'longitude' => '', 'featured' => 0, 'image' => '',
];

if ($action === 'edit') {
    $st = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
    $st->execute([$id]);
    $existing = $st->fetch();
    if (!$existing) {
        flash_set('Destination not found.', 'error');
        admin_redirect('destinations.php');
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
        foreach (['name', 'category', 'short_desc', 'description', 'history', 'address', 'latitude', 'longitude'] as $k) {
            $in[$k] = isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
        }
        $in['featured'] = isset($_POST['featured']) ? 1 : 0;
        $f = array_merge($f, $in); // keep what the user typed

        if ($in['name'] === '' || strlen($in['name']) > 150) {
            $errors[] = 'Name is required (max 150 characters).';
        }
        if (!in_array($in['category'], $CATEGORIES, true)) {
            $errors[] = 'Please choose a valid category.';
        }
        if (strlen($in['short_desc']) > 300) {
            $errors[] = 'Short description must be 300 characters or less.';
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
                    $st = $pdo->prepare("INSERT INTO destinations
                        (name, category, short_desc, description, history, address, latitude, longitude, image, featured)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $st->execute([$in['name'], $in['category'], $in['short_desc'], $in['description'],
                        $in['history'], $in['address'], $lat, $lng, $img, $in['featured']]);
                    flash_set('Destination added.');
                } else {
                    $st = $pdo->prepare("UPDATE destinations SET
                        name = ?, category = ?, short_desc = ?, description = ?, history = ?, address = ?,
                        latitude = ?, longitude = ?, image = ?, featured = ? WHERE id = ?");
                    $st->execute([$in['name'], $in['category'], $in['short_desc'], $in['description'],
                        $in['history'], $in['address'], $lat, $lng, $img, $in['featured'], $id]);
                    flash_set('Destination updated.');
                }
                admin_redirect('destinations.php');
            }
        }
    }
}
?>

<?php if ($action === 'list'): ?>
<?php $rows = $pdo->query("SELECT * FROM destinations ORDER BY id DESC")->fetchAll(); ?>
<div class="toolbar">
    <h1>Destinations</h1>
    <a class="btn" href="<?= e(url('admin/destinations.php?action=add')) ?>">+ Add destination</a>
</div>
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th>Photo</th><th>Name</th><th>Category</th><th>Featured</th><th>Location</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><img class="thumb" src="<?= e(image_src($r['image'], $r['name'], $r['id'])) ?>" alt=""></td>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['category']) ?></td>
                <td><?= $r['featured'] ? 'Yes' : 'No' ?></td>
                <td><?= ($r['latitude'] !== null && $r['longitude'] !== null) ? e($r['latitude']) . ', ' . e($r['longitude']) : '&mdash;' ?></td>
                <td class="actions">
                    <a class="btn btn-small" href="<?= e(url('admin/destinations.php?action=edit&id=' . (int)$r['id'])) ?>">Edit</a>
                    <form method="post" data-confirm="Delete this destination? This cannot be undone.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="6">No destinations yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
<div class="toolbar">
    <h1><?= $action === 'add' ? 'Add destination' : 'Edit destination' ?></h1>
    <a class="btn btn-light-dark" href="<?= e(url('admin/destinations.php')) ?>">&larr; Back to list</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <label class="full">Name *
        <input type="text" name="name" maxlength="150" required value="<?= e($f['name']) ?>">
    </label>
    <label>Category *
        <select name="category">
            <?php foreach ($CATEGORIES as $c): ?>
            <option value="<?= e($c) ?>" <?= $f['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Featured
        <span class="hint"><br></span>
        <input type="checkbox" name="featured" value="1" <?= !empty($f['featured']) ? 'checked' : '' ?>>
        Show on the home page (first 3 featured are shown)
    </label>
    <label class="full">Short description <span class="hint">(shown on cards and map popups, max 300 characters)</span>
        <textarea name="short_desc" rows="2" maxlength="300"><?= e($f['short_desc']) ?></textarea>
    </label>
    <label class="full">Description
        <textarea name="description" rows="5"><?= e($f['description']) ?></textarea>
    </label>
    <label class="full">History / significance
        <textarea name="history" rows="5"><?= e($f['history']) ?></textarea>
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
            Leave both empty and the place will not appear on the map.
        </p>
        <div id="pickerMap" class="picker-map"></div>
    </div>

    <label class="full">Photo
        <?php if ($f['image']): ?>
        <img src="<?= e(image_src($f['image'], $f['name'], $id)) ?>" alt="Current photo" style="max-width:220px;border-radius:8px;margin:6px 0;">
        <?php endif; ?>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <span class="hint">JPG, PNG, or WEBP, max 2MB. <?= $f['image'] ? 'Choose a new file to replace the current photo.' : 'If empty, a placeholder image is shown.' ?></span>
    </label>

    <div class="full">
        <button type="submit" class="btn">Save destination</button>
        <a class="btn btn-light-dark" href="<?= e(url('admin/destinations.php')) ?>">Cancel</a>
    </div>
</form>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>