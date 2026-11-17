<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'About & Contact';

$errors = [];
$name = $email = $message = '';

// Handle the visitor message form (before any output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email   = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $message = trim(isset($_POST['message']) ? $_POST['message'] : '');

    if ($name === '' || strlen($name) > 100) { $errors[] = 'Please enter your name (max 100 characters).'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) { $errors[] = 'Please enter a valid email address.'; }
    if (strlen($message) < 5 || strlen($message) > 2000) { $errors[] = 'Message must be between 5 and 2000 characters.'; }

    if (!$errors) {
        $st = $pdo->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        $st->execute([$name, $email, $message]);
        header('Location: ' . url('about.php?sent=1#contact'));
        exit;
    }
}
$sent = isset($_GET['sent']);

include __DIR__ . '/includes/header.php';
?>
<section class="page-title">
    <div class="container">
        <h1>About &amp; Contact</h1>
        <p>Learn about this system and get in touch.</p>
    </div>
</section>

<section class="section container two-col">
    <div>
        <h2>About the System</h2>
        <p>
            EXPLORE INABANGA is a central web platform that promotes the tourism and culture of Inabanga, Bohol,
            preserves its historical records digitally, and connects local businesses with visitors.
        </p>
        <ul class="check-list">
            <li>Browse tourist spots and historical sites</li>
            <li>Find local eateries, stays, crafts, and tour services</li>
            <li>Explore everything on an interactive map</li>
            <li>Read the timeline of local heritage</li>
        </ul>

        <h2>LGU Tourism Office</h2>
        <div class="panel">
            <p><strong>Municipal Tourism Office</strong><br>Inabanga Municipal Hall, Inabanga, Bohol, Philippines</p>
            <p><strong>Phone:</strong> (038) XXX-XXXX<br>
               <strong>Email:</strong> tourism@example.com<br>
               <strong>Office hours:</strong> Monday to Friday, 8:00 AM to 5:00 PM</p>
            <p class="muted">Placeholder contact details. To be replaced with official LGU information.</p>
        </div>
    </div>

    <div id="contact">
        <h2>Send Us a Message</h2>
        <?php if ($sent): ?>
        <div class="alert alert-success">Thank you! Your message has been sent.</div>
        <?php endif; ?>
        <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('about.php')) ?>#contact" class="form">
            <label>Your name
                <input type="text" name="name" maxlength="100" required value="<?= e($name) ?>">
            </label>
            <label>Your email
                <input type="email" name="email" maxlength="150" required value="<?= e($email) ?>">
            </label>
            <label>Message
                <textarea name="message" rows="6" maxlength="2000" required><?= e($message) ?></textarea>
            </label>
            <button type="submit" class="btn">Send message</button>
        </form>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>