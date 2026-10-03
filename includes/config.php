<?php
/**
 * Core configuration + helpers (flat-file JSON storage, sessions, CSRF).
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('CONTENT_FILE', DATA_PATH . '/content.json');
define('MESSAGES_FILE', DATA_PATH . '/messages.json');

/* Admin login. Default password: admin123  -> CHANGE IT (see README).
 * Generate a new hash:  php -r 'echo password_hash("YourPassword", PASSWORD_DEFAULT);' */
define('ADMIN_USER', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$tV/pEdPX3Z3NkH0LXKYJZefVyIACVbDf8tB.in0gFP8khQdAuoCLS');

date_default_timezone_set('Asia/Kolkata');

function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Read a JSON file safely (shared lock). Returns $default on failure. */
function read_json(string $file, array $default = []): array {
    if (!is_file($file)) return $default;
    $fh = fopen($file, 'rb');
    if (!$fh) return $default;
    flock($fh, LOCK_SH);
    $raw = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    $data = json_decode((string)$raw, true);
    return is_array($data) ? $data : $default;
}

/** Atomic write: temp file + rename, under an exclusive lock on a .lock file. */
function write_json(string $file, array $data): bool {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    $lock = fopen($file . '.lock', 'c');
    if (!$lock) return false;
    flock($lock, LOCK_EX);
    $tmp = $file . '.tmp' . getmypid();
    $ok = file_put_contents($tmp, $json) !== false && rename($tmp, $file);
    flock($lock, LOCK_UN);
    fclose($lock);
    return $ok;
}

/** Load site content; missing keys never break pages. */
function content(): array {
    static $c = null;
    if ($c === null) $c = read_json(CONTENT_FILE);
    return $c;
}

/** Fetch a value by path, e.g. c('home.hero_title'). */
function c(string $path, string $fallback = ''): string {
    $node = content();
    foreach (explode('.', $path) as $k) {
        if (!is_array($node) || !array_key_exists($k, $node)) return $fallback;
        $node = $node[$k];
    }
    return is_string($node) ? $node : $fallback;
}

function c_list(string $path): array {
    $node = content();
    foreach (explode('.', $path) as $k) {
        if (!is_array($node) || !array_key_exists($k, $node)) return [];
        $node = $node[$k];
    }
    return is_array($node) ? $node : [];
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

/* ---------- Auth ---------- */
function is_admin(): bool { return !empty($_SESSION['is_admin']); }

function flash(?string $msg = null, string $type = 'success'): ?array {
    if ($msg !== null) { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function nav_active(string $page): string {
    return basename($_SERVER['SCRIPT_NAME']) === $page ? ' class="active"' : '';
}

/* ---------- Atomic read-modify-write (prevents lost updates) ---------- */
/** $fn receives the current array and returns the new array to save (or null to skip). */
function update_json(string $file, callable $fn): bool {
    $lock = fopen($file . '.lock', 'c');
    if (!$lock) return false;
    flock($lock, LOCK_EX);
    $new = $fn(read_json($file));
    $ok = true;
    if (is_array($new)) {
        $json = json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp' . getmypid();
        $ok = $json !== false && file_put_contents($tmp, $json) !== false && rename($tmp, $file);
    }
    flock($lock, LOCK_UN);
    fclose($lock);
    return $ok;
}

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function is_ajax(): bool { return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch'; }

/* ---------- Live chat rendering (data comes from chat_payload() in db.php) ---------- */
function render_chat_messages(array $msgs, string $me, string $meLabel, string $themLabel): void {
    if (!$msgs) { echo '<p class="chat-empty">No messages yet. Say hello!</p>'; return; }
    foreach ($msgs as $m) {
        $mine = $m['from'] === $me;
        echo '<div class="chat-msg ' . ($mine ? 'me' : 'them') . '"><small>' . e($mine ? $meLabel : $themLabel)
           . ' &middot; ' . e($m['time']) . '</small><span>' . e($m['text']) . '</span></div>';
    }
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
