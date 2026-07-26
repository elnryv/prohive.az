<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';

require_method('POST');

$id = (int) ($_GET['id'] ?? 0);
db()->prepare('UPDATE banners SET click_count = click_count + 1 WHERE id = :id')->execute(['id' => $id]);

json_ok();
