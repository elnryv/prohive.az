<?php
declare(strict_types=1);

// Gündə 1 dəfə işləyir (crontab). 24 saatdan köhnə events sətirlərini silir — bax Hissə 7.3.

require_once __DIR__ . '/../db.php';

$stmt = db()->prepare("DELETE FROM events WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();

echo $stmt->rowCount() . " köhnə event silindi.\n";
