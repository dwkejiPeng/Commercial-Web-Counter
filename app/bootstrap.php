<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('PUBLIC_PATH', APP_ROOT . '/public');

$configFile = APP_PATH . '/config.php';
if (is_file($configFile)) {
    $GLOBALS['app_config'] = require $configFile;
    if (!empty($GLOBALS['app_config']['app']['timezone'])) {
        date_default_timezone_set($GLOBALS['app_config']['app']['timezone']);
    }
    $sessionName = $GLOBALS['app_config']['security']['session_name'] ?? 'COUNTERSESSID';
    if (session_status() === PHP_SESSION_NONE) {
        session_name($sessionName);
        session_start();
    }
} else {
    $GLOBALS['app_config'] = [];
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function config(string $key = '', $default = null)
{
    $config = $GLOBALS['app_config'] ?? [];
    if ($key === '') {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function is_installed(): bool
{
    return is_file(APP_PATH . '/config.php');
}

function require_installed(): void
{
    if (!is_installed()) {
        header('Location: /install/');
        exit;
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = config('db', []);
    if (!$db) {
        throw new RuntimeException('Database configuration is missing.');
    }

    $host = $db['host'] ?? '127.0.0.1';
    $port = (int)($db['port'] ?? 3306);
    $database = $db['database'] ?? '';
    $charset = $db['charset'] ?? 'utf8mb4';
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

    $pdo = new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = (string)($_POST['_csrf'] ?? '');
    if (!$token || !hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF token mismatch.');
    }
}

function post(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function getv(string $key, string $default = ''): string
{
    return trim((string)($_GET[$key] ?? $default));
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function send_cors(): void
{
    $allowed = config('security.cors_allowed_origins', ['*']);
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array('*', $allowed, true)) {
        header('Access-Control-Allow-Origin: *');
    } elseif ($origin && in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Max-Age: 86400');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function no_cache(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function clean_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) {
        return $url;
    }

    $scheme = strtolower($parts['scheme'] ?? 'https');
    $host = strtolower($parts['host']);
    $path = $parts['path'] ?? '/';
    if ($path === '') {
        $path = '/';
    }

    return $scheme . '://' . $host . $path;
}

function host_from_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }
    return strtolower((string)(parse_url($url, PHP_URL_HOST) ?: ''));
}

function normalize_domain(string $domain): string
{
    $host = host_from_url($domain);
    return preg_replace('/^www\./', '', $host);
}

function client_ip(): string
{
    if (config('security.trust_proxy_headers', false)) {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
            $_SERVER['HTTP_X_REAL_IP'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        ];
        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate === '') {
                continue;
            }
            $first = trim(explode(',', $candidate)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function visitor_hash(string $ip, string $userAgent = ''): string
{
    $salt = (string)config('security.visitor_hash_salt', '');
    return hash('sha256', $ip . '|' . $userAgent . '|' . $salt);
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }

    if (empty($_SESSION['user_id'])) {
        $user = null;
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $row = $stmt->fetch();

    if (!$row || $row['status'] !== 'active') {
        unset($_SESSION['user_id']);
        $user = null;
        return null;
    }

    $user = $row;
    return $user;
}

function require_login(): array
{
    require_installed();
    $user = current_user();
    if (!$user) {
        redirect('/login.php');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
    return $user;
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = (int)$user['id'];
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

function generate_site_key(): string
{
    do {
        $key = bin2hex(random_bytes(16));
        $stmt = db()->prepare('SELECT id FROM sites WHERE site_key = ? LIMIT 1');
        $stmt->execute([$key]);
    } while ($stmt->fetch());

    return $key;
}

function site_for_user(int $siteId, array $user): ?array
{
    if ($user['role'] === 'admin') {
        $stmt = db()->prepare('SELECT s.*, u.email AS owner_email, u.username AS owner_name FROM sites s JOIN users u ON u.id = s.user_id WHERE s.id = ? LIMIT 1');
        $stmt->execute([$siteId]);
    } else {
        $stmt = db()->prepare('SELECT * FROM sites WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$siteId, (int)$user['id']]);
    }
    $site = $stmt->fetch();
    return $site ?: null;
}

function allowed_domain_list(array $site): array
{
    $items = [];
    if (!empty($site['site_domain'])) {
        $items[] = normalize_domain($site['site_domain']);
    }
    if (!empty($site['base_url'])) {
        $items[] = normalize_domain($site['base_url']);
    }
    if (!empty($site['allowed_domains'])) {
        foreach (preg_split('/[\r\n,]+/', $site['allowed_domains']) as $domain) {
            $domain = normalize_domain(trim($domain));
            if ($domain !== '') {
                $items[] = $domain;
            }
        }
    }
    return array_values(array_unique(array_filter($items)));
}

function is_request_domain_allowed(array $site): bool
{
    $allowed = allowed_domain_list($site);
    if (!$allowed) {
        return true;
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = host_from_url($origin ?: $referer);

    if ($host === '') {
        return true;
    }

    $host = preg_replace('/^www\./', '', strtolower($host));
    return in_array($host, $allowed, true);
}

function app_base_url(): string
{
    return rtrim((string)config('app.base_url', ''), '/');
}
