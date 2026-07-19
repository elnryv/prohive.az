<?php
declare(strict_types=1);

// Admin panel — ayrı tətbiq, eyni DB (Hissə 10.1). Sessiya mexanizmi əsas
// tətbiqin `sessions` cədvəlindən ayrıdır (admin_sessions, admin_users-a bağlı).

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';

const ADMIN_SESSION_COOKIE = 'ybb_admin_session';

// Uğursuz cəhd limiti (Hissə 10.2): 5 səhv cəhddən sonra 15 dəqiqə bloklama.
function admin_login(string $identity, string $password): ?array
{
    $db = db();
    $stmt = $db->prepare('SELECT * FROM admin_users WHERE username = :identity1 OR email = :identity2 LIMIT 1');
    $stmt->execute(['identity1' => $identity, 'identity2' => $identity]);
    $admin = $stmt->fetch();

    if ($admin === false) {
        return null;
    }

    if ($admin['locked_until'] !== null && strtotime($admin['locked_until']) > time()) {
        return null;
    }

    if (!password_verify($password, $admin['password_hash'])) {
        $attempts = (int) $admin['failed_attempts'] + 1;
        $lockedUntil = $attempts >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
        $db->prepare('UPDATE admin_users SET failed_attempts = :a, locked_until = :l WHERE id = :id')
            ->execute(['a' => $attempts, 'l' => $lockedUntil, 'id' => $admin['id']]);
        return null;
    }

    $db->prepare('UPDATE admin_users SET failed_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id')
        ->execute(['id' => $admin['id']]);

    return $admin;
}

function admin_session_create(int $adminId, bool $rememberMe): void
{
    $days = $rememberMe ? 30 : 1;
    $token = bin2hex(random_bytes(32));
    $csrfToken = bin2hex(random_bytes(32));

    db()->prepare(
        'INSERT INTO admin_sessions (admin_id, token_hash, csrf_token, device, ip, expires_at)
         VALUES (:admin_id, :token_hash, :csrf_token, :device, :ip, DATE_ADD(NOW(), INTERVAL :days DAY))'
    )->execute([
        'admin_id' => $adminId,
        'token_hash' => hash('sha256', $token),
        'csrf_token' => $csrfToken,
        'device' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 120),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'days' => $days,
    ]);

    setcookie(ADMIN_SESSION_COOKIE, $token, [
        'expires' => time() + $days * 86400,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function admin_session_current(): ?array
{
    $token = $_COOKIE[ADMIN_SESSION_COOKIE] ?? '';
    if ($token === '') {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT s.id AS session_id, s.admin_id, s.csrf_token, a.username, a.email
         FROM admin_sessions s
         JOIN admin_users a ON a.id = s.admin_id
         WHERE s.token_hash = :hash AND s.expires_at > NOW() LIMIT 1'
    );
    $stmt->execute(['hash' => hash('sha256', $token)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function require_admin(): array
{
    $session = admin_session_current();
    if ($session === null) {
        header('Location: /login.php');
        exit;
    }
    return $session;
}

function admin_logout(): void
{
    $token = $_COOKIE[ADMIN_SESSION_COOKIE] ?? '';
    if ($token !== '') {
        db()->prepare('DELETE FROM admin_sessions WHERE token_hash = :hash')->execute(['hash' => hash('sha256', $token)]);
    }
    setcookie(ADMIN_SESSION_COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
}

// Synchronizer-token CSRF (admin panel fetch/XHR istifadə etmir, sadə form POST-ları
// üçün double-submit cookie əvəzinə sessiyaya bağlı token kifayətdir).
function admin_csrf_field(array $session): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($session['csrf_token'], ENT_QUOTES) . '">';
}

function admin_csrf_validate(array $session): void
{
    $token = $_POST['csrf_token'] ?? '';
    if ($token === '' || !hash_equals($session['csrf_token'], $token)) {
        http_response_code(403);
        die('CSRF token yanlışdır, səhifəni yeniləyin.');
    }
}

// Hissə 10.18 — bütün vacib əməliyyatlar qeyd olunur: kim, nə, nə vaxt, IP.
function audit_log(int $adminId, string $action, string $entity, ?int $entityId = null, array $meta = []): void
{
    db()->prepare(
        'INSERT INTO audit_logs (admin_id, action, entity, entity_id, meta, ip)
         VALUES (:admin_id, :action, :entity, :entity_id, :meta, :ip)'
    )->execute([
        'admin_id' => $adminId,
        'action' => $action,
        'entity' => $entity,
        'entity_id' => $entityId,
        'meta' => $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    ]);
}
