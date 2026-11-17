<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Home';

$featured = $pdo->query("SELECT * FROM destinations WHERE featured = 1 ORDER BY id LIMIT 3")->fetchAll();
if (!$featured) {
    $featured = $pdo->query("SELECT * FROM destinations ORDER BY id DESC LIMIT 3")->fetchAll();
}
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container">
        <h1>Welcome to Inabanga, Bohol</h1>
        <p>Discover our rivers, coastline, heritage sites, and local businesses.</p>
        <a class="btn" href="<?= e(url('destinations.php')) ?>">Explore Destinations</a>
        <a class="btn btn-light" href="<?= e(url('map.php')) ?>">Open the Map</a>
    </div>
</section>

<section class="section container">
    <h2>About Inabanga</h2>
    <p>
        Inabanga is a coastal municipality in the province of Bohol, Philippines. This website brings together
        its tourist spots, historical sites, local businesses, and historical records in one place, to promote
        tourism, preserve local heritage, and connect visitors with the community.
    </p>
</section>

<section class="section container">
    <h2>Featured Destinations</h2>
    <div class="grid">
        <?php foreach ($featured as $d) { destination_card($d); } ?>
    </div>
    <p class="center"><a class="btn" href="<?= e(url('destinations.php')) ?>">See all destinations</a></p>
</section>

<section class="section container">
    <h2>Quick Links</h2>
    <div class="quick-links">
        <a href="<?= e(url('destinations.php')) ?>"><strong>Destinations</strong><span>Tourist spots and historical sites</span></a>
        <a href="<?= e(url('businesses.php')) ?>"><strong>Local Businesses</strong><span>Food, stays, crafts, and tours</span></a>
        <a href="<?= e(url('map.php')) ?>"><strong>Interactive Map</strong><span>Find places around town</span></a>
        <a href="<?= e(url('heritage.php')) ?>"><strong>Heritage</strong><span>Timeline of local history</span></a>
        <a href="<?= e(url('about.php')) ?>"><strong>Contact Us</strong><span>Reach the tourism office</span></a>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>