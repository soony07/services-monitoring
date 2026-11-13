<?php
declare(strict_types=1);
$dir = sys_get_temp_dir() . '/it-services-test-' . bin2hex(random_bytes(6));
mkdir($dir);
putenv('PULSE_DATA_DIR=' . $dir);
require dirname(__DIR__) . '/common.php';
require dirname(__DIR__) . '/app/Models/SettingsModel.php';
require dirname(__DIR__) . '/app/Models/MonitorModel.php';
require dirname(__DIR__) . '/app/Services/CheckRunner.php';
$count = 0;
function check(bool $condition, string $label): void {
    global $count;
    if (!$condition) throw new RuntimeException($label);
    $count++;
    echo "PASS $label
";
}
function request(array $data = []): string {
    global $dir;
    $process = proc_open([PHP_BINARY, __DIR__ . '/request.php', $dir, json_encode($data)], [1=>['pipe','w'],2=>['pipe','w']], $pipes);
    $html = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException($error . $html);
    return $html;
}
$model = new SettingsModel();
check(str_contains(request(), 'Ersteinrichtung'), 'First visit shows setup');
request(['post'=>['token'=>'test-token','name'=>'','language'=>'xx','accent'=>'red']]);
check($model->get() === null, 'Invalid setup writes nothing');
request(['post'=>['token'=>'bad','name'=>'Invalid','language'=>'de','accent'=>'#000000']]);
check($model->get() === null, 'CSRF rejects setup');
request(['post'=>['token'=>'test-token','name'=>'Office','language'=>'en','accent'=>'#336b81']]);
check($model->get()['name'] === 'Office', 'Setup persists instance name');
check(str_contains(request(), 'Service overview'), 'Default language renders English');
check(str_contains(request(['cookies'=>['it_language'=>'de']]), 'Dienste im Überblick'), 'Cookie language preference persists');
check(str_contains(request(['get'=>['language'=>'de']]), 'Dienste im Überblick'), 'Visible switch changes language');
request(['get'=>['setup'=>''], 'post'=>['token'=>'test-token','name'=>'Overwrite','language'=>'de','accent'=>'#ffffff']]);
check($model->get()['name'] === 'Office', 'Completed setup cannot overwrite settings');
request(['get'=>['settings'=>''], 'post'=>['token'=>'test-token','name'=>'New Office','language'=>'en','accent'=>'#123456']]);
check($model->get()['accent'] === '#123456' && str_contains(request(), '#123456'), 'Settings color persists and renders');
$monitors = new MonitorModel();
$monitors->create(['Healthy','http://fake.invalid',10,'http','',1]);
$monitors->create(['Broken','http://fake.invalid',10,'http','',1]);
$monitors->create(['Private service','http://fake.invalid',10,'http','',0]);
$monitors->toggle([3]);
ob_start();
runChecks(db(), static fn(array $monitor): array => $monitor['name'] === 'Healthy' ? [true,200,''] : [false,503,'HTTP 503']);
ob_end_clean();
$rows = $monitors->all(false);
check((int)$rows[1]['up'] === 1 && (int)$rows[2]['up'] === 0, 'Successful and failed checks stored');
check($rows[3]['up'] === null, 'Paused monitor is not checked');
check(str_contains(request(['get'=>['id'=>1]]), 'badge up') && str_contains(request(['get'=>['id'=>2]]), 'badge down'), 'Actual results rendered');
$status = request(['get'=>['status'=>'']]);
check(str_contains($status,'Healthy') && !str_contains($status,'Private service'), 'Status page respects visibility');
$monitors->delete([2]);
check(count($monitors->history(2,500)) === 0, 'Delete removes history');
check(!$model->save(['name'=>'Replace','language'=>'de','accent'=>'#000000'], true), 'Repeated setup insert is atomic');
check(count($monitors->all(false)) === 2, 'Settings preserve monitors');
foreach ([['name'=>''], ['language'=>'fr'], ['accent'=>'red'], ['accent'=>'#123456; color:red']] as $invalid) {
    $before = $model->get();
    request(['get'=>['settings'=>''], 'post'=>array_merge(['token'=>'test-token','name'=>'Office','language'=>'en','accent'=>'#336b81'], $invalid)]);
    check($model->get() === $before, 'Invalid setting rejected: ' . array_key_first($invalid));
}
$invalidMonitor = request(['post'=>['token'=>'test-token','action'=>'save','name'=>'Bad','url'=>'file:///etc/passwd','interval'=>10,'kind'=>'http']]);
check(count($monitors->all(false)) === 2 && str_contains($invalidMonitor, 'Please check'), 'Invalid monitor rejected with translated error');
$db = db();
$db->exec('DROP TABLE settings');
$model = new SettingsModel();
check(count($monitors->all(false)) === 2 && $model->get() === null, 'Existing installation enters setup with monitors intact');
$model->save(['name'=>'IT Services','language'=>'de','accent'=>'#336b81'], true);
check(count($monitors->history(1,500)) === 1, 'Setup preserves existing check history');
unset($db);
echo "$count tests passed
";
// The directory is exclusively owned by this test run.
unset($model, $monitors);
foreach (glob($dir . '/*') as $file) unlink($file);
rmdir($dir);

