<?php
declare(strict_types=1);

function db(): PDO
{
    $dir = getenv('PULSE_DATA_DIR') ?: __DIR__ . '/data';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create the data directory.');
    }
    $db = new PDO('sqlite:' . $dir . '/pulse.sqlite');
    $db->exec('PRAGMA busy_timeout=5000; PRAGMA foreign_keys=ON');
    $db->exec('CREATE TABLE IF NOT EXISTS monitors (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, url TEXT NOT NULL,
        interval_seconds INTEGER NOT NULL, next_check INTEGER NOT NULL DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS checks (
        id INTEGER PRIMARY KEY, monitor_id INTEGER NOT NULL REFERENCES monitors(id) ON DELETE CASCADE,
        checked_at INTEGER NOT NULL, up INTEGER NOT NULL, status_code INTEGER NOT NULL,
        latency_ms INTEGER NOT NULL, error TEXT NOT NULL
    );
    CREATE INDEX IF NOT EXISTS checks_monitor_id ON checks(monitor_id, id);');
    $db->exec('BEGIN IMMEDIATE');
    try {
        $columns = array_column($db->query('PRAGMA table_info(monitors)')->fetchAll(), 'name');
        foreach ([
            'paused' => 'INTEGER NOT NULL DEFAULT 0',
            'listed' => 'INTEGER NOT NULL DEFAULT 0',
            'kind' => "TEXT NOT NULL DEFAULT 'http'",
            'keyword' => "TEXT NOT NULL DEFAULT ''",
            'revision' => 'INTEGER NOT NULL DEFAULT 0',
        ] as $name => $definition) {
            if (!in_array($name, $columns, true)) {
                $db->exec("ALTER TABLE monitors ADD COLUMN $name $definition");
            }
        }
        $db->exec('COMMIT');
    } catch (Throwable $error) {
        $db->exec('ROLLBACK');
        throw $error;
    }
    $db->exec('CREATE TABLE IF NOT EXISTS worker_state (
        id INTEGER PRIMARY KEY CHECK (id = 1), seen_at INTEGER NOT NULL
    )');
    return $db;
}

function validUrl(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $parts = parse_url($url);
    return $parts && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        && !isset($parts['user']) && !isset($parts['pass']);
}

function validTcp(string $target): bool
{
    return preg_match('/^(?:[a-z0-9][a-z0-9.-]*|\[[a-f0-9:]+\]):([0-9]{1,5})$/iD', $target, $match)
        && (int) $match[1] >= 1 && (int) $match[1] <= 65535;
}

function probe(array $monitor): array
{
    if ($monitor['kind'] === 'tcp') {
        $socket = @stream_socket_client('tcp://' . $monitor['url'], $number, $message, 8);
        if ($socket) {
            fclose($socket);
        }
        return [(bool) $socket, 0, $socket ? '' : ($message ?: 'TCP-Verbindung fehlgeschlagen')];
    }

    $keyword = $monitor['keyword'];
    $matched = $monitor['kind'] !== 'keyword';
    $tail = '';
    $curl = curl_init($monitor['url']);
    curl_setopt_array($curl, [
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'PulseMonitor/2.0',
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$matched, &$tail, $keyword): int {
            if (!$matched) {
                $window = $tail . $chunk;
                $matched = str_contains($window, $keyword);
                $tail = strlen($keyword) > 1 ? substr($window, 1 - strlen($keyword)) : '';
            }
            return strlen($chunk);
        },
    ]);
    curl_exec($curl);
    $code = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error === '' && ($code < 200 || $code >= 400)) {
        $error = "HTTP $code";
    }
    if ($error === '' && !$matched) {
        $error = 'Suchtext fehlt in der Antwort';
    }
    return [$error === '', $code, $error];
}


