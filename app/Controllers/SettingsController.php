<?php
declare(strict_types=1);
class SettingsController
{
    public function handle(SettingsModel $model, ?array $settings): void
    {
        $setup = $settings === null;
        if (!$setup && isset($_GET['setup'])) {
            header('Location: ' . appUrl(), true, 303);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!hash_equals($_SESSION['token'], (string) ($_POST['token'] ?? ''))) {
                http_response_code(403);
                echo h(t('Ungültiges Formular. Bitte Seite neu laden.'));
                return;
            }
            try {
                $model->save($_POST, $setup);
                header('Location: ' . appUrl(), true, 303);
                return;
            } catch (InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            }
        }
        $values = $_POST ?: ($settings ?? ['name'=>'IT Services','language'=>'de','accent'=>'#336b81']);
        require __DIR__ . '/../Views/settings.php';
    }
}
