<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/Models/SettingsModel.php';
require dirname(__DIR__) . '/app/Controllers/SettingsController.php';
require dirname(__DIR__) . '/app/language.php';
session_start();
$_SESSION['token'] ??= bin2hex(random_bytes(32));
$model = new SettingsModel();
$settings = $model->get();
$language = $_COOKIE['it_language'] ?? ($settings['language'] ?? 'de');
if (isset($_GET['language']) && in_array($_GET['language'], ['de','en'], true)) {
    $language = $_GET['language'];
    setcookie('it_language', $language, ['expires'=>time()+31536000,'path'=>appUrl(),'httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
}
$language = in_array($language, ['de','en'], true) ? $language : 'de';
ob_start();
if (!$settings || isset($_GET['settings']) || isset($_GET['setup'])) {
    (new SettingsController())->handle($model, $settings);
} else {
    (new DashboardController())->handle();
}
$html = ob_get_clean();
echo $html;
