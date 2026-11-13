<?php
declare(strict_types=1);
class DashboardController
{
    public function handle(): void
    {
        $model = new MonitorModel();
        $workerSeen = $model->workerSeen();
        $workerOnline = $workerSeen && time() - $workerSeen <= 90;
        $public = isset($_GET['status']);
        $id = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
        $mode = $_GET['view'] ?? '';
        $kinds = ['http' => 'HTTP(s)', 'keyword' => 'HTTP(S) mit Suchtext', 'tcp' => 'TCP-Port'];
        
        
        
        if (!$public) {
            if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
            $_SESSION['token'] ??= bin2hex(random_bytes(32));
            $token = $_SESSION['token'];
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($public || !hash_equals($token, (string) ($_POST['token'] ?? ''))) {
                http_response_code(403);
                exit(h(t('Ungültiges Formular. Bitte Seite neu laden.')));
            }
        
            $action = $_POST['action'] ?? '';
            if ($action === 'save') {
                $kind = (string) ($_POST['kind'] ?? 'http');
                $name = trim((string) ($_POST['name'] ?? ''));
                $url = trim((string) ($_POST['url'] ?? ''));
                $keyword = (string) ($_POST['keyword'] ?? '');
                $interval = filter_var($_POST['interval'] ?? '', FILTER_VALIDATE_INT);
                $listed = (int) isset($_POST['listed']);
                if (!isset($kinds[$kind]) || $name === '' || strlen($name) > 120 || strlen($url) > 2048
                    || !($kind === 'tcp' ? validTcp($url) : validUrl($url))
                    || ($kind === 'keyword' && ($keyword === '' || strlen($keyword) > 256))
                    || $interval === false || $interval < 10 || $interval > 86400) {
                    $error = 'Bitte Bezeichnung, Ziel, Suchtext und Prüfintervall überprüfen.';
                    $mode = $id ? 'edit' : 'new';
                    $draft = compact('kind', 'name', 'url', 'keyword', 'listed') + ['interval_seconds' => $_POST['interval'] ?? ''];
                } else {
                    $values = [$name, $url, $interval, $kind, $kind === 'keyword' ? $keyword : '', $listed];
                    if ($id) {
                        $model->update([...$values, $id]);
                    } else {
                        $model->create($values);
                        $id = $model->lastId();
                    }
                }
            } elseif ($action === 'toggle') {
                $model->toggle([$id]);
            } elseif ($action === 'check') {
                $model->queueCheck([$id]);
                $_SESSION['notice'] = 'Prüfung vorgemerkt. Sie wird beim nächsten Durchlauf ausgeführt.';
            } elseif ($action === 'delete') {
                $model->delete([$id]);
                $id = 0;
            }
            if (!isset($error)) {
                header('Location: ' . appUrl() . ($id ? "?id=$id" : ''), true, 303);
                exit;
            }
        }
        
        $notice = $public ? '' : ($_SESSION['notice'] ?? '');
        if (!$public) {
            unset($_SESSION['notice']);
            session_write_close();
        }
        $monitors = $model->all($public);
        $counts = ['up' => 0, 'down' => 0, 'pending' => 0, 'paused' => 0];
        foreach ($monitors as &$item) {
            $item['state'] = $item['paused'] ? 'paused' : ($item['up'] === null ? 'pending' : ($item['up'] ? 'up' : 'down'));
            $counts[$item['state']]++;
        }
        unset($item);
        $monitor = $monitors[$id] ?? null;
        
        
        
        
        
        
        
        $editing = !$public && ($mode === 'new' || ($mode === 'edit' && $monitor));
        
        $histories = [];
        foreach ($monitors as $monitorId => $item) {
            $histories[$monitorId] = $model->history($monitorId, 500);
        }
        require __DIR__ . '/../Views/dashboard.php';
        
    }
}

