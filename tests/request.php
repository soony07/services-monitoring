<?php
// Isolated request process for the front controller.
putenv('PULSE_DATA_DIR=' . $argv[1]);
session_save_path($argv[1]);
session_id('integration');
session_start();
$_SESSION['token'] = 'test-token';
session_write_close();
$request = json_decode($argv[2], true, 512, JSON_THROW_ON_ERROR);
$_GET = $request['get'] ?? [];
$_POST = $request['post'] ?? [];
$_COOKIE = $request['cookies'] ?? [];
$_SERVER['REQUEST_METHOD'] = $_POST ? 'POST' : 'GET';
$_SERVER['SCRIPT_NAME'] = '/pulse/public/index.php';
require dirname(__DIR__) . '/public/index.php';
