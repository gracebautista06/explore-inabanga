<?php
// includes/config.php
// Shared setup: session, SQLite connection, auto-create tables, auto-seed sample data, helpers.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

define('ROOT_DIR', realpath(__DIR__ . '/..'));
define('DATA_DIR', ROOT_DIR . '/data');
define('UPLOAD_DIR', ROOT_DIR . '/uploads');
define('DB_FILE', DATA_DIR . '/inabanga.sqlite');
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024); // 2 MB

// ---- Work out the base URL (works in /htdocs/explore-inabanga and with php -S) ----
$__base = '';
$__doc = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
if ($__doc) {
    $__r = str_replace('\\', '/', ROOT_DIR);
    $__d = str_replace('\\', '/', $__doc);
    if (strpos($__r, $__d) === 0) {
        $__base = rtrim(substr($__r, strlen($__d)), '/');
    }
}
define('BASE_URL', $__base);

// Make sure folders exist
if (!is_dir(DATA_DIR))   { @mkdir(DATA_DIR, 0775, true); }
if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0775, true); }

// ---- Lists used by filters and forms ----
$CATEGORIES     = ['Tourist Spot', 'Historical Site'];
$BUSINESS_TYPES = ['Food', 'Accommodation', 'Souvenir/Crafts', 'Tours/Services', 'Other'];

// ---- Database connection ----
try {
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    die('Database error. Make sure the "data" folder is writable and the pdo_sqlite PHP extension is enabled.');
}

// ---- Auto-create tables ----
$pdo->exec("CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS destinations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    category TEXT NOT NULL,
    short_desc TEXT,
    description TEXT,
    history TEXT,
    address TEXT,
    latitude REAL,
    longitude REAL,
    image TEXT DEFAULT '',
    featured INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS businesses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT NOT NULL,
    description TEXT,
    address TEXT,
    contact TEXT,
    email TEXT,
    latitude REAL,
    longitude REAL,
    image TEXT DEFAULT '',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS history_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    period TEXT,
    description TEXT,
    image TEXT DEFAULT '',
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    message TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT
)");

// ---- Auto-seed (runs only once) ----
$st = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
$st->execute(['seeded']);
if ((int)$st->fetchColumn() === 0) {
    seed_database($pdo);
}

function seed_database($pdo)
{
    $note = ' (Sample data – to be verified by LGU)';
    $pdo->beginTransaction();

    // Default admin account
    $st = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
    $st->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);

    // Destinations: name, category, short_desc, description, history, address, lat, lng, featured
    // Coordinates are APPROXIMATE placeholders around Inabanga.
    $dest = [
        ['Inabanga Parish Church', 'Historical Site',
         'The town\'s parish church, a center of community and faith.' . $note,
         'The parish church stands as one of the main landmarks of the town and is a gathering place for religious celebrations and community events.' . $note,
         'Churches in Bohol are often among the oldest structures in their towns. The specific history of this church should be provided by the parish or the LGU.' . $note,
         'Poblacion, Inabanga, Bohol', 9.9840, 124.0670, 1],
        ['Inabanga Town Plaza and Municipal Hall Area', 'Tourist Spot',
         'The civic heart of the municipality, good for a relaxed walk.' . $note,
         'The town center area where government offices, the plaza, and daily community life come together. A good starting point for visitors.' . $note,
         'The plaza area has long served as the center of civic and social activity in the municipality.' . $note,
         'Poblacion, Inabanga, Bohol', 9.9828, 124.0660, 1],
        ['Inabanga River', 'Tourist Spot',
         'A river that flows through the municipality and supports local livelihoods.' . $note,
         'The river is an important natural feature of Inabanga and a setting for scenic views, local fishing activity, and possible river-based tours.' . $note,
         'Rivers have traditionally shaped settlement, travel, and trade in coastal towns. The river\'s specific historical role should be confirmed by the LGU.' . $note,
         'Inabanga, Bohol', 9.9790, 124.0620, 1],
        ['Inabanga Mangrove Area', 'Tourist Spot',
         'Coastal mangroves that protect the shoreline and shelter marine life.' . $note,
         'Mangrove areas along the coast are valuable for nature viewing, education, and coastal protection. Visitors should follow local guidelines.' . $note,
         'Community efforts to protect mangroves may exist. Details should be provided by the LGU or local organizations.' . $note,
         'Coastal area, Inabanga, Bohol', 10.0010, 124.0760, 0],
        ['Inabanga Coastal Shoreline', 'Tourist Spot',
         'A stretch of coastline for sea views, sunsets, and fishing village life.' . $note,
         'The shoreline offers sea views and a look at coastal community life. Exact access points and facilities should be verified.' . $note,
         'Fishing and sea trade have been part of coastal life in the region for generations.' . $note,
         'Coastal barangays, Inabanga, Bohol', 10.0050, 124.0700, 0],
        ['Heritage Structures of the Poblacion', 'Historical Site',
         'Older buildings and markers in the town center worth a heritage walk.' . $note,
         'A placeholder entry for old houses, markers, and other heritage structures in the town center. Replace with specific sites once confirmed.' . $note,
         'Specific history of individual structures should be documented by the LGU or local historians.' . $note,
         'Poblacion, Inabanga, Bohol', 9.9836, 124.0678, 0],
    ];
    $st = $pdo->prepare("INSERT INTO destinations
        (name, category, short_desc, description, history, address, latitude, longitude, image, featured)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', ?)");
    foreach ($dest as $d) {
        $st->execute([$d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $d[6], $d[7], $d[8]]);
    }

    // Businesses: name, type, description, address, contact, email, lat, lng
    $biz = [
        ['Sample Local Eatery', 'Food',
         'A placeholder for a local eatery serving Boholano dishes and snacks.' . $note,
         'Poblacion, Inabanga, Bohol', '09XX-XXX-XXXX', 'eatery@example.com', 9.9825, 124.0675],
        ['Sample Riverside Homestay', 'Accommodation',
         'A placeholder for a simple homestay or guesthouse for overnight visitors.' . $note,
         'Near Inabanga River, Inabanga, Bohol', '09XX-XXX-XXXX', 'homestay@example.com', 9.9800, 124.0630],
        ['Sample Handicraft Shop', 'Souvenir/Crafts',
         'A placeholder for a shop selling local crafts and souvenirs.' . $note,
         'Poblacion, Inabanga, Bohol', '09XX-XXX-XXXX', 'crafts@example.com', 9.9845, 124.0655],
        ['Sample Community Tour Provider', 'Tours/Services',
         'A placeholder for a local guide offering river, mangrove, or heritage tours.' . $note,
         'Inabanga, Bohol', '09XX-XXX-XXXX', 'tours@example.com', 9.9815, 124.0690],
        ['Sample General Store', 'Other',
         'A placeholder for a neighborhood store serving residents and visitors.' . $note,
         'Poblacion, Inabanga, Bohol', '09XX-XXX-XXXX', 'store@example.com', 9.9832, 124.0645],
    ];
    $st = $pdo->prepare("INSERT INTO businesses
        (name, type, description, address, contact, email, latitude, longitude, image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, '')");
    foreach ($biz as $b) {
        $st->execute($b);
    }

    // History records: title, period, description, sort_order (generic, no invented dates)
    $hist = [
        ['Early Settlement', 'Early period (dates to be verified)',
         'Communities gathered along the river and coast, relying on fishing, farming, and trade. Details of the earliest settlement should be added from local records.' . $note, 1],
        ['Spanish Colonial Period', 'Spanish colonial era (dates to be verified)',
         'Like many towns in the Philippines, Inabanga was shaped by Spanish colonial rule, including the establishment of the parish and town center. Specific events and dates should be added by the LGU.' . $note, 2],
        ['American and Early Municipal Period', 'Early 20th century (dates to be verified)',
         'The period of changing governance, public education, and civic development. Specific milestones should be documented from official records.' . $note, 3],
        ['Modern Inabanga', 'Present day',
         'Today Inabanga continues to grow as a coastal municipality of Bohol, preserving its heritage while welcoming visitors. Add recent milestones and festivals here.' . $note, 4],
    ];
    $st = $pdo->prepare("INSERT INTO history_records (title, period, description, image, sort_order)
        VALUES (?, ?, ?, '', ?)");
    foreach ($hist as $h) {
        $st->execute([$h[0], $h[1], $h[2], $h[3]]);
    }

    $st = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('seeded', '1')");
    $st->execute();
    $pdo->commit();
}

// ---------------- Helper functions ----------------

// Escape output
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Build a URL relative to the project root
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

// Short preview of a long text (UTF-8 safe)
function excerpt($text, $n = 110)
{
    $text = trim(strip_tags((string)$text));
    if (strlen($text) <= $n) {
        return $text;
    }
    preg_match('/^.{0,' . (int)$n . '}/us', $text, $m);
    return rtrim($m[0]) . '...';
}

// Simple placeholder image (inline SVG as a data URI)
function placeholder_img($label, $seed = 0)
{
    $palettes = [
        ['#0f766e', '#14b8a6'], ['#1d4ed8', '#38bdf8'], ['#b45309', '#f59e0b'],
        ['#9a3412', '#fb923c'], ['#4d7c0f', '#a3e635'], ['#6d28d9', '#a78bfa'],
    ];
    $p = $palettes[abs((int)$seed) % count($palettes)];
    $label = excerpt($label, 30);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" stop-color="' . $p[0] . '"/><stop offset="1" stop-color="' . $p[1] . '"/>'
        . '</linearGradient></defs>'
        . '<rect width="800" height="500" fill="url(#g)"/>'
        . '<text x="400" y="240" text-anchor="middle" font-family="Arial, sans-serif" font-size="34" font-weight="bold" fill="#ffffff">'
        . e($label) . '</text>'
        . '<text x="400" y="285" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" fill="#ffffff" opacity="0.85">'
        . 'EXPLORE INABANGA</text></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// Uploaded image URL if it exists, otherwise a placeholder
function image_src($file, $label, $seed = 0)
{
    if ($file && file_exists(UPLOAD_DIR . '/' . basename($file))) {
        return url('uploads/' . rawurlencode(basename($file)));
    }
    return placeholder_img($label, $seed);
}

// Print one destination card (used on home and destinations pages)
function destination_card($d)
{
    $img = image_src($d['image'], $d['name'], $d['id']);
    echo '<article class="card">';
    echo '<a href="' . e(url('destination.php?id=' . (int)$d['id'])) . '"><img src="' . e($img) . '" alt="' . e($d['name']) . '"></a>';
    echo '<div class="card-body">';
    echo '<span class="badge">' . e($d['category']) . '</span>';
    echo '<h3>' . e($d['name']) . '</h3>';
    echo '<p>' . e(excerpt($d['short_desc'], 120)) . '</p>';
    echo '<a class="btn btn-small" href="' . e(url('destination.php?id=' . (int)$d['id'])) . '">View details</a>';
    echo '</div></article>';
}

// ---- Admin helpers ----
function is_admin()
{
    return !empty($_SESSION['admin_id']);
}

function require_admin()
{
    if (!is_admin()) {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}

// Handle an image upload. Returns the new file name, the old name if no file was sent,
// or false on error (message stored in $error).
function handle_upload($field, &$error, $old = '')
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return $old;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed (error code ' . (int)$f['error'] . '). The file may be too large.';
        return false;
    }
    if ($f['size'] > MAX_UPLOAD_BYTES) {
        $error = 'Image is too large. Maximum size is 2MB.';
        return false;
    }
    $info = @getimagesize($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($allowed[$info['mime']])) {
        $error = 'Only JPG, PNG, or WEBP images are allowed.';
        return false;
    }
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $name)) {
        $error = 'Could not save the image. Make sure the "uploads" folder is writable.';
        return false;
    }
    // Remove the old image when replaced
    if ($old && file_exists(UPLOAD_DIR . '/' . basename($old))) {
        @unlink(UPLOAD_DIR . '/' . basename($old));
    }
    return $name;
}