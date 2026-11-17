<?php
// admin/login.php
require_once __DIR__ . '/../includes/config.php';

if (is_admin()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    $st = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $st->execute([$username]);
    $admin = $st->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        header('Location: ' . url('admin/dashboard.php'));
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Admin Login | EXPLORE INABANGA</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="login-box">
    <h1>Admin Login</h1>
    <p class="muted">EXPLORE INABANGA: Tourism and Heritage Information System</p>

    <div class="alert alert-warn">
        Default account: <strong>admin</strong> / <strong>admin123</strong>.<br>
        Please change this password after your first login (Dashboard &rarr; Change password).
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('admin/login.php')) ?>">
        <label>Username
            <input type="text" name="username" value="<?= e($username) ?>" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn">Log in</button>
        <a class="btn btn-light-dark" href="<?= e(url('index.php')) ?>">Back to site</a>
    </form>
</div>
</body>
</html>