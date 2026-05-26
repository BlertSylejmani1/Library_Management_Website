<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Library Management System');
define('APP_VERSION', '2.0.0');
define('APP_ROOT', dirname(__DIR__));
define('ROLE_ADMIN', 'admin');
define('ROLE_STUDENT', 'student');
define('COOKIE_THEME', 'ath_theme');
define('COOKIE_EXPIRE', time() + 60 * 60 * 24 * 30);

define('DB_HOST', getenv('LIBRARY_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('LIBRARY_DB_PORT') ?: '3306');
define('DB_NAME', getenv('LIBRARY_DB_NAME') ?: 'library_management');
define('DB_USER', getenv('LIBRARY_DB_USER') ?: 'root');
define('DB_PASS', getenv('LIBRARY_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_BOOK_DIR', APP_ROOT . '/uploads/books');
define('MAIL_LOG_FILE', APP_ROOT . '/storage/logs/mail.log');

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if (preg_match('#/(pages|ajax)$#', $scriptDirectory)) {
    $scriptDirectory = dirname($scriptDirectory);
}
$scriptDirectory = rtrim($scriptDirectory, '/');
define('BASE_URL', $scriptDirectory === '/' ? '' : $scriptDirectory);

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function getSessionUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (($_SESSION['user']['role'] ?? ROLE_STUDENT) !== ROLE_ADMIN) {
        redirect('pages/dashboard.php');
    }
}

function roleLabel(string $role): string
{
    return $role === ROLE_STUDENT ? 'Student' : 'Administrator';
}

function getCurrentTheme(): string
{
    return $_COOKIE[COOKIE_THEME] ?? 'dark';
}

function flash(string $key, string $message, string $type = 'success'): void
{
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type,
    ];
}

function pullFlash(string $key): ?array
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $flash = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $flash;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_or_fail(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $current = $_SESSION['csrf_token'] ?? '';

    if ($submitted === '' || $current === '' || !hash_equals($current, $submitted)) {
        http_response_code(419);
        throw new RuntimeException('Invalid CSRF token. Please refresh the page and try again.');
    }
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
