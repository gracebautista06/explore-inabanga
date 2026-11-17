<?php
// includes/header.php
// Set $pageTitle and (optionally) $useMap = true BEFORE including this file.
require_once __DIR__ . '/config.php';
$pageTitle = isset($pageTitle) ? $pageTitle : 'Welcome';
$useMap = !empty($useMap);
$current = basename($_SERVER['SCRIPT_NAME']);
if ($current === 'destination.php') { $current = 'destinations.php'; }
$navItems = [
    'index.php'        => 'Home',
    'destinations.php' => 'Destinations',
    'businesses.php'   => 'Local Businesses',
    'map.php'          => 'Map',
    'heritage.php'     => 'Heritage',
    'about.php'        => 'About & Contact',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | EXPLORE INABANGA</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <?php if ($useMap): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <?php endif; ?>
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('index.php')) ?>">
            EXPLORE INABANGA
            <small>Tourism and Heritage Information System</small>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">&#9776;</button>
        <nav class="main-nav" id="mainNav">
            <ul>
                <?php foreach ($navItems as $file => $label): ?>
                <li><a href="<?= e(url($file)) ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>
<main>