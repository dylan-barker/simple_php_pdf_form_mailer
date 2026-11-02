<?php
/**
 * Shared bootstrap: configuration, session, security headers and helpers.
 *
 * Every entry point must define APP_RUNNING before including this file, so
 * direct browser access to library files cannot execute anything.
 */

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(404);
    exit;
}

define('APP_PRIVATE', dirname(__DIR__));
define('APP_PUBLIC', dirname(APP_PRIVATE));
define('APP_ROOT', APP_PUBLIC);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('This application requires PHP 8.1 or newer.');
}

$autoload = APP_PRIVATE . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Dependencies are not installed. Run composer install and upload the vendor directory.');
}
require $autoload;

// ---------------------------------------------------------------------------
// Configuration lookup
//
// The real config lives in private/, which sits inside the web root and is
// protected by its own .htaccess. APP_CONFIG can point at another file, for
// example one kept outside the web root on hosts that allow it.
// ---------------------------------------------------------------------------
$configCandidates = [];

$envConfig = getenv('APP_CONFIG');
if (is_string($envConfig) && $envConfig !== '') {
    $configCandidates[] = $envConfig;
}
$configCandidates[] = APP_PRIVATE . '/config.php';

$configFile = null;
foreach ($configCandidates as $candidate) {
    if (is_file($candidate) && is_readable($candidate)) {
        $configFile = $candidate;
        break;
    }
}

if ($configFile === null) {
    http_response_code(500);
    exit('Configuration file not found. Copy config.example.php to config.php and fill it in.');
}

$config = require $configFile;
if (!is_array($config)) {
    http_response_code(500);
    exit('Configuration file must return an array.');
}

// ---------------------------------------------------------------------------
// Runtime settings
// ---------------------------------------------------------------------------
$timezone = (string) ($config['timezone'] ?? 'UTC');
if (!in_array($timezone, timezone_identifiers_list(), true)) {
    $timezone = 'UTC';
}
date_default_timezone_set($timezone);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

// ---------------------------------------------------------------------------
// Writable scratch directory
//
// Never used to store the generated PDF. Only dompdf's temporary font work
// lands here, and each request gets a private subdirectory that is deleted
// before the response is sent.
// ---------------------------------------------------------------------------
$storageDir = $config['storage_dir'] ?? null;

if (!is_string($storageDir) || $storageDir === '') {
    $storageDir = APP_PRIVATE . '/tmp';
}

if (!is_dir($storageDir) && !@mkdir($storageDir, 0700, true) && !is_dir($storageDir)) {
    http_response_code(500);
    exit('Storage directory is not writable.');
}

if (!is_writable($storageDir)) {
    http_response_code(500);
    exit('Storage directory is not writable.');
}

define('APP_STORAGE', rtrim($storageDir, '/\\'));

$logFile = APP_STORAGE . '/error.log';
ini_set('error_log', $logFile);

// ---------------------------------------------------------------------------
// Session (holds only the CSRF token)
// ---------------------------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('spfm_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** HTML-escape a value for output. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Send the headers used on every response. */
function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('X-Robots-Tag: noindex, nofollow');
    header(
        "Content-Security-Policy: default-src 'self'; base-uri 'none'; " .
        "form-action 'self'; frame-ancestors 'none'; img-src 'self' data:; " .
        "style-src 'self'; script-src 'self'"
    );
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

/** Current CSRF token, created on first use. */
function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** Replace the CSRF token after a successful submission. */
function csrf_rotate(): string
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return $_SESSION['csrf_token'];
}

/** Constant-time CSRF check. */
function csrf_valid(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    $expected = $_SESSION['csrf_token'] ?? null;
    if (!is_string($expected) || $expected === '') {
        return false;
    }

    return hash_equals($expected, $token);
}

/** True when the caller expects JSON (fetch/XHR) rather than a page. */
function wants_json(): bool
{
    if (strcasecmp((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''), 'XMLHttpRequest') === 0) {
        return true;
    }

    return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
}

/** Write a diagnostic line without ever including personal data. */
function app_log(string $message): void
{
    $message = preg_replace('/\s+/', ' ', $message) ?? $message;
    error_log('[spfm] ' . mb_substr($message, 0, 500));
}

/** Log an exception, redacting any sensitive values that leaked into it. */
function app_log_exception(Throwable $e, array $redact = []): void
{
    $message = $e->getMessage();

    foreach ($redact as $value) {
        if (is_string($value) && mb_strlen($value) > 1) {
            $message = str_ireplace($value, '[redacted]', $message);
        }
    }

    app_log(get_class($e) . ': ' . $message);
}

/** Remove a directory tree without following symlinks. */
function remove_tree(string $path): void
{
    if (is_link($path) || is_file($path)) {
        @unlink($path);
        return;
    }

    if (!is_dir($path)) {
        return;
    }

    $entries = @scandir($path);
    if ($entries === false) {
        return;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        remove_tree($path . DIRECTORY_SEPARATOR . $entry);
    }

    @rmdir($path);
}

/** Per-request scratch directory for dompdf. Deleted at shutdown. */
function create_scratch_dir(): string
{
    $base = APP_STORAGE . '/run';

    if (!is_dir($base) && !@mkdir($base, 0700, true) && !is_dir($base)) {
        throw new RuntimeException('Unable to create the scratch directory.');
    }

    $dir = $base . '/r' . bin2hex(random_bytes(8));

    if (!@mkdir($dir, 0700)) {
        throw new RuntimeException('Unable to create the request scratch directory.');
    }

    $GLOBALS['spfm_scratch_dirs'][] = $dir;

    return $dir;
}

/** Delete every scratch directory created during this request. */
function cleanup_scratch_dirs(): void
{
    foreach ($GLOBALS['spfm_scratch_dirs'] ?? [] as $dir) {
        remove_tree($dir);
    }
    $GLOBALS['spfm_scratch_dirs'] = [];
}

$GLOBALS['spfm_scratch_dirs'] = $GLOBALS['spfm_scratch_dirs'] ?? [];
register_shutdown_function('cleanup_scratch_dirs');
