<?php
require_once __DIR__ . '/includes/config.php';
$user = require_user();

$errors = [];
$welcome = isset($_GET['welcome']);
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null, '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if (!csrf_check()) {
        $errors[] = 'Session expired. Please try again.';
    } elseif ($action === 'logout') {
        unset($_SESSION['uid']);
        session_regenerate_id(true);
        flash('You have been logged out.');
        header('Location: login.php'); exit;
    } elseif ($action === 'save_profile') {
        $name = trim((string)($_POST['name'] ?? ''));
        $school = trim((string)($_POST['school'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Please enter your name (max 100 characters).';
        if (mb_strlen($school) > 150) $errors[] = 'School name is too long (max 150 characters).';
        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150)) $errors[] = 'Please enter a valid email address.';
        if (!$errors) {
            db()->prepare('UPDATE users SET name = ?, school = ?, email = ? WHERE id = ?')->execute([$name, $school, $email, $user['id']]);
            flash('Profile saved.');
            header('Location: ' . ($next !== '' ? $next : 'account.php')); exit;
        }
        $user['name'] = $name; $user['school'] = $school; $user['email'] = $email;
    }
}

// Chat history (from the database)
$payload = chat_payload(chat_fetch((int)$user['id'], 200));

// Inquiries submitted through the contact form with this phone number (stored in messages.json)
$inquiries = [];
foreach (array_reverse(read_json(MESSAGES_FILE)) as $m) {
    if (normalize_phone((string)($m['phone'] ?? '')) === $user['phone']) $inquiries[] = $m;
}

$flash = flash();
$pageTitle = 'My Account';
require __DIR__ . '/header.php';
?>
<section class="page-hero slim">
    <div class="container admin-top">
        <h1>My Account</h1>
        <form method="post" action="account.php"><?= csrf_field() ?><input type="hidden" name="action" value="logout">
            <button class="btn btn-outline" type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button></form>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
        <?php if ($welcome && $user['name'] === ''): ?><div class="alert alert-info">Welcome! Please tell us your name so our team knows who they are talking to.</div><?php endif; ?>
        <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <div class="account-grid">
            <div class="form-card">
                <h2 class="card-heading">Your Profile</h2>
                <form method="post" action="account.php">
                    <?= csrf_field() ?><input type="hidden" name="action" value="save_profile"><input type="hidden" name="next" value="<?= e($next) ?>">
                    <div class="form-row"><label>Phone (verified)</label><input type="text" value="<?= e($user['phone']) ?>" disabled></div>
                    <div class="form-row"><label for="name">Name</label><input type="text" id="name" name="name" required maxlength="100" value="<?= e($user['name']) ?>"></div>
                    <div class="form-row"><label for="school">School Name</label><input type="text" id="school" name="school" maxlength="150" value="<?= e($user['school']) ?>"></div>
                    <div class="form-row"><label for="email">Email</label><input type="email" id="email" name="email" maxlength="150" value="<?= e($user['email']) ?>"></div>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Profile</button>
                </form>
            </div>

            <div>
                <div class="form-card">
                    <div class="chat-top"><h2 class="card-heading" style="margin:0">Chat History</h2>
                        <a class="btn btn-sm btn-primary" href="chat.php"><i class="fa-solid fa-comments"></i> Open Live Chat</a></div>
                    <div class="chat-box history"><?php render_chat_messages($payload['messages'], 'user', 'You', 'Admin Team'); ?></div>
                </div>

                <div class="form-card" style="margin-top:1.5rem">
                    <h2 class="card-heading">Your Inquiries</h2>
                    <?php if (!$inquiries): ?>
                        <p class="muted-dark">No inquiries yet. <a href="contact.php">Send one</a> using this phone number and it will show up here with our reply.</p>
                    <?php else: foreach ($inquiries as $m): $pending = ($m['status'] ?? '') === 'Pending'; ?>
                        <div class="inquiry">
                            <div class="inq-top"><small><?= e($m['created_at'] ?? '') ?></small>
                                <span class="status <?= $pending ? 'pending' : 'replied' ?>"><?= e($m['status'] ?? 'Pending') ?></span></div>
                            <p><?= nl2br(e($m['message'] ?? '')) ?></p>
                            <div class="reply-box"><strong>Admin reply:</strong><br><?= !empty($m['reply']) ? nl2br(e($m['reply'])) : 'Awaiting response from our team...' ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
