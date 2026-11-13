<?php
function runChecks(PDO $db, callable $probe): void
{
$due = $db->prepare('SELECT * FROM monitors WHERE paused = 0 AND next_check <= ? ORDER BY next_check, id');
$save = $db->prepare('INSERT INTO checks (monitor_id, checked_at, up, status_code, latency_ms, error)
    SELECT id, ?, ?, ?, ?, ? FROM monitors WHERE id = ? AND paused = 0 AND revision = ?');
$schedule = $db->prepare('UPDATE monitors SET next_check = ? WHERE id = ? AND paused = 0 AND revision = ?');
$prune = $db->prepare('DELETE FROM checks WHERE monitor_id = ? AND id NOT IN
    (SELECT id FROM checks WHERE monitor_id = ? ORDER BY id DESC LIMIT 500)');

    $due->execute([time()]);
    foreach ($due->fetchAll(PDO::FETCH_ASSOC) as $monitor) {

$db->prepare('INSERT INTO worker_state VALUES (1,?) ON CONFLICT(id) DO UPDATE SET seen_at=excluded.seen_at')->execute([time()]);
        $start = hrtime(true);
        [$up, $code, $error] = $probe($monitor);
        $latency = (int) round((hrtime(true) - $start) / 1e6);
        $now = time();
        $id = $monitor['id'];

        $db->beginTransaction();
        try {
            $save->execute([$now, (int) $up, $code, $latency, $error, $id, $monitor['revision']]);
            $schedule->execute([$now + $monitor['interval_seconds'], $id, $monitor['revision']]);
            $prune->execute([$id, $id]);
            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
        printf("[%s] #%d %s (%d ms)\n", gmdate('H:i:s', $now), $id, $up ? 'UP' : 'DOWN', $latency);
    }
}
