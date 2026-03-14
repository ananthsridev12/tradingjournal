<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fyers.php';

$pdo = db();
$result = fyers_sync_trades($pdo);

if (!$result['ok']) {
    $msg = urlencode($result['error']);
    header('Location: trades.php?sync=0&error=' . $msg);
    exit;
}

$qs = http_build_query([
    'sync' => 1,
    'inserted' => $result['inserted'],
    'updated' => $result['updated'],
    'skipped' => $result['skipped'],
]);
header('Location: trades.php?' . $qs);
exit;
