<?php
// admin/header.php
// Shared admin header. Include it FIRST in every admin page (after setting $pageTitle / $useMap).
// It requires login, starts output buffering (so redirects work after this file),
// and defines small helpers: CSRF, flash messages, admin_redirect().
require_once __DIR__ . '/../includes/config.php';
require_admin();
ob_start();

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify()
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

function flash_set($message, $type = 'success')
{
    $_SESSION['flash'] = ['m' => $message, 't' => $type];
}

// Redirect to another admin page (discards buffered HTML first)
function admin_redirect($file)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . url('admin/' . $file));
    exit;
}

$pageTitle = isset($pageTitle) ? $pageTitle : 'Admin';
$useMap = !empty($useMap);
$current = basename($_SERVER['SCRIPT_NAME']);
$adminNav = [
    'dashboard.php'    => 'Dashboard',
    'destinations.php' => 'Destinations',
    'businesses.php'   => 'Businesses',
    'history.php'      => 'History',
    'messages.php'     => 'Messages',
];
$flash = null;
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($pageTitle) ?> | Admin | EXPLORE INABANGA</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <?php if ($useMap): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <?php endif; ?>
</head>
<body>
<div class="admin-bar">
    <div class="container">
        <strong>EXPLORE INABANGA Admin</strong>
        <nav>
            <?php foreach ($adminNav as $file => $label): ?>
            <a href="<?= e(url('admin/' . $file)) ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View site</a>
            <a href="<?= e(url('admin/logout.php')) ?>">Logout (<?= e(isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : '') ?>)</a>
        </nav>
    </div>
</div>
<main class="container admin-wrap">
<?php if ($flash): ?>
    <div class="alert <?= $flash['t'] === 'error' ? 'alert-error' : 'alert-success' ?>"><?= e($flash['m']) ?></div>
<?php endif; ?>