<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

require_method('POST');

$session = admin_session_current();
if ($session !== null) {
    admin_csrf_validate($session);
    audit_log((int) $session['admin_id'], 'admin_logout', 'admin_users', (int) $session['admin_id']);
}

admin_logout();
header('Location: login.php');
