<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/database.php';

function admin_session_start(): void
{
    $cfg = app_config();
    if (session_status() === PHP_SESSION_NONE) {
        session_name($cfg['session_name']);
        session_start();
    }
}

function admin_logged_in(): bool
{
    admin_session_start();
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}

function admin_login(string $username, string $password): bool
{
    $pdo = db();
    $st = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = ? LIMIT 1');
    $st->execute([$username]);
    $row = $st->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    admin_session_start();
    $_SESSION['admin_id'] = (int) $row['id'];
    $_SESSION['admin_user'] = $username;
    return true;
}

function admin_logout(): void
{
    admin_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * @return array<string,mixed>|null
 */
function current_admin(PDO $pdo): ?array
{
    admin_session_start();
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    $st = $pdo->prepare('SELECT id, username, full_name, email, phone, photo_path FROM admin_users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();

    return $row ?: null;
}
