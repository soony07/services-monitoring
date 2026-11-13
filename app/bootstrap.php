<?php
declare(strict_types=1);
require dirname(__DIR__) . '/common.php';
require __DIR__ . '/Models/MonitorModel.php';
require __DIR__ . '/Controllers/DashboardController.php';
require __DIR__ . '/Views/helpers.php';
function appUrl(): string
{
    $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return rtrim($directory, '/') . '/';
}
