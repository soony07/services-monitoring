<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require __DIR__ . '/app/Services/CheckRunner.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$db = db();
$dir = getenv('PULSE_DATA_DIR') ?: __DIR__ . '/data';
$lock = fopen($dir . '/worker.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Could not acquire the worker lock. Check whether a worker is already running.\n");
    exit(1);
}

$once = in_array('--once', $argv, true);
$heartbeat = $db->prepare('INSERT INTO worker_state (id, seen_at) VALUES (1, ?)
    ON CONFLICT(id) DO UPDATE SET seen_at = excluded.seen_at');

do {
    $heartbeat->execute([time()]);
    runChecks($db, 'probe');
    if ($once) {
        break;
    }
    sleep(2);
} while (true);



