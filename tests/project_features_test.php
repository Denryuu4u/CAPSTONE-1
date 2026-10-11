<?php
/** Isolated HTTP regression tests. No business rows are copied or changed.
 * php tests/project_features_test.php [--serve] keeps the fixture server open for UI checks.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/db.php';
function feature_check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ', $message, PHP_EOL;
}
function feature_http($curl, string $url, ?array $post = null): array {
    curl_setopt_array($curl, [CURLOPT_URL => $url, CURLOPT_POST => $post !== null, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20]);
    if ($post !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, count(array_filter($post, fn($v) => $v instanceof CURLFile)) ? $post : http_build_query($post));
    $body = curl_exec($curl);
    if ($body === false) throw new RuntimeException(curl_error($curl));
    return [curl_getinfo($curl, CURLINFO_HTTP_CODE), $body];
}
function feature_actor(string $base, string $role): array {
    $ch = curl_init(); curl_setopt($ch, CURLOPT_COOKIEFILE, '');
    [$status, $html] = feature_http($ch, $base . '/admin/monitoring.php?dev_user=' . $role);
    feature_check($status === 200, $role . ' opens monitoring');
    preg_match('/csrf:(["\'])([a-f0-9]{64})\1/', $html, $m);
    feature_check(!empty($m[2]), $role . ' receives a project CSRF token');
    return [$ch, $m[2]];
}
$pdo = db(); $source = $pdo->query('SELECT DATABASE()')->fetchColumn();
$test = 'vast_features_test_' . bin2hex(random_bytes(6));
$root = dirname(__DIR__); $server = null; $created = false; $savedFiles = [];
$temp = sys_get_temp_dir() . '/vast-features-' . bin2hex(random_bytes(6)); mkdir($temp);
try {
    $pdo->exec('CREATE DATABASE `' . $test . '` CHARACTER SET utf8mb4'); $created = true;
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $pdo->exec("CREATE TABLE `$test`.`$table` LIKE `$source`.`$table`");
    }
    $pdo->exec('USE `' . $test . '`');
    $hash = password_hash('fixture-password', PASSWORD_BCRYPT);
    $insert = $pdo->prepare('INSERT INTO users (id,full_name,email,role,status,password_hash,email_verified) VALUES (?,?,?,?,?,?,1)');
    foreach ([[1,'Super Admin','admin@vastsolutions.com'],[2,'Admin','admin2@vastsolutions.com'],[3,'Staff','staff@vastsolutions.com'],[4,'Client','client@demo.test']] as [$id,$role,$email]) {
        $insert->execute([$id,'Test ' . $role,$email,$role,'Active',$hash]);
    }
    $pdo->exec("INSERT INTO customers (id,user_id,name,email) VALUES (1,4,'Demo Customer','client@demo.test'),(2,1,'Other Customer','other@example.test')");
    $pdo->exec("INSERT INTO projects (id,project_code,customer_id,project_name,status,progress) VALUES (1,'PRJ-TEST-001',1,'Kitchen Design','production',30),(2,'PRJ-TEST-002',1,'New Quotation','quote_submitted',0),(3,'PRJ-TEST-003',2,'Other Project','approved',10)");
    $pdo->exec("INSERT INTO quotations (id,quote_code,customer_id,project_id,project_name,date_created,status,material_total,labor_cost,total_amount) VALUES (1,'QT-TEST-001',1,1,'Kitchen Design',CURDATE(),'Approved',1000,500,1830),(2,'QT-TEST-002',1,2,'New Quotation',CURDATE(),'Sent',1000,500,1830),(3,'QT-TEST-003',2,3,'Other Project',CURDATE(),'Approved',1000,500,1830)");
    $pdo->exec("INSERT INTO project_requests (id,request_code,customer_id,submitted_by,project_name,target_completion) VALUES (1,'REQ-TEST-001',1,4,'Requested Cabinet','2027-01-01')");
    $pdo->exec("INSERT INTO projects (id,project_code,customer_id,request_id,project_name,status,progress) VALUES (4,'PRJ-TEST-004',1,1,'Requested Cabinet','quote_submitted',0)");
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    $addr = stream_socket_get_name($socket, false); fclose($socket);
    $env = getenv(); $env['DB_NAME'] = $test; $env['DEV_MODE'] = 'true'; $env['BASE_URL'] = '/';
    $server = proc_open([PHP_BINARY, '-d', 'upload_max_filesize=9M', '-d', 'post_max_size=10M', '-S', $addr, '-t', $root],
        [0 => ['pipe','r'],1 => ['file',$temp.'/server.log','a'],2 => ['file',$temp.'/server.log','a']], $pipes, $root, $env);
    if (!is_resource($server)) throw new RuntimeException('Cannot start fixture server.');
    $base = 'http://' . $addr;
    for ($i=0;$i<30;$i++) { $s=@fsockopen(explode(':',$addr)[0],(int)explode(':',$addr)[1]); if($s){fclose($s);break;} usleep(100000); }
    [$admin,$token] = feature_actor($base,'admin');
    [$staff,$staffToken] = feature_actor($base,'staff');
    [$super,$superToken] = feature_actor($base,'superadmin');
    $state = fn($ch,$csrf,$value) => feature_http($ch,$base.'/admin/set_project_state.php',['project_id'=>1,'state'=>$value,'csrf'=>$csrf]);
    feature_check($state($staff,$staffToken,'paused')[0]===403,'Staff cannot pause');
    feature_check($state($admin,'invalid','paused')[0]===403,'Pause rejects invalid CSRF');
    feature_check($state($admin,$token,'paused')[0]===200,'Admin pauses project');
    $p = $pdo->query('SELECT * FROM projects WHERE id=1')->fetch();
    feature_check($p['status']==='on_hold' && $p['paused_status']==='production' && (int)$p['progress']===30,'Pause preserves phase and progress');
    $before = (int)$pdo->query('SELECT COUNT(*) FROM project_updates')->fetchColumn();
    foreach ([
        ['update_project_status.php',['project_id'=>1,'status'=>'completed']],
        ['revert_project_status.php',['project_id'=>1]],
        ['post_update.php',['project_id'=>1,'text'=>'Must not be saved']],
        ['save_tracking.php',['project_id'=>1,'project_amount'=>999]],
    ] as [$route,$body]) feature_check(feature_http($admin,$base.'/admin/'.$route,$body)[0]===409,'Paused project blocks '.$route);
    feature_check((int)$pdo->query('SELECT COUNT(*) FROM project_updates')->fetchColumn()===$before,'Blocked requests do not add updates');
    feature_check($state($staff,$staffToken,'active')[0]===403,'Staff cannot resume');
    feature_check($state($super,$superToken,'active')[0]===200,'Super Admin resumes project');
    feature_check($pdo->query('SELECT status FROM projects WHERE id=1')->fetchColumn()==='production','Resume restores original phase');
    feature_check(feature_http($staff,$base.'/admin/update_project_status.php',['project_id'=>1,'status'=>'on_hold'])[0]===403,'Generic status endpoint cannot bypass admin pause');
    feature_check(feature_http($staff,$base.'/admin/update_project_status.php',['project_id'=>1,'status'=>'mockup'])[0]===200,'Normal phase updates work after resume');
    foreach ([['save_customer.php',['id'=>1,'name'=>'Demo Customer','email'=>'missing-at.example.com']],['save_user.php',['id'=>4,'full_name'=>'Test Client','email'=>'missing-at.example.com','role'=>'Client']]] as [$route,$body]) {
        feature_check(feature_http($super,$base.'/admin/'.$route,$body)[0]===400,'Invalid email rejected by '.$route);
    }
    feature_check($pdo->query('SELECT email FROM customers WHERE id=1')->fetchColumn()==='client@demo.test','Invalid edit preserves customer email');
    feature_check($pdo->query('SELECT email FROM users WHERE id=4')->fetchColumn()==='client@demo.test','Invalid edit preserves account email');
    $image=$temp.'/design.png'; file_put_contents($image,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    // A visible cabinet-shaped raster fixture, generated without external assets.
    $raw=''; for($y=0;$y<240;$y++){ $raw.="\0"; for($x=0;$x<400;$x++){
        $inside=$x>50 && $x<350 && $y>30 && $y<210;
        $edge=$inside && ($x<56 || $x>344 || $y<36 || $y>204 || abs($x-200)<3 || abs($y-120)<3);
        $raw.=$edge ? "\x24\x3c\x4d" : ($inside ? "\x9c\xd4\xc4" : "\xf5\xf7\xf8");
    }}
    $chunk=fn($kind,$bytes)=>pack('N',strlen($bytes)).$kind.$bytes.pack('N',crc32($kind.$bytes));
    file_put_contents($image,"\x89PNG\r\n\x1a\n".$chunk('IHDR',pack('NNCCCCC',400,240,8,2,0,0,0)).$chunk('IDAT',gzcompress($raw)).$chunk('IEND',''));
    $bad=$temp.'/fake.png';file_put_contents($bad,'<?php echo "not an image";');
    $send = fn($ch,$csrf,$file,$qid=1) => feature_http($ch,$base.'/admin/send_project_design.php',['quotation_id'=>$qid,'csrf'=>$csrf,'design_image'=>new CURLFile($file,'image/png','design.png')]);
    feature_check($send($staff,$staffToken,$image)[0]===403,'Staff cannot backfill designs');
    feature_check($send($admin,$token,$bad)[0]===400,'Disguised script image rejected');
    feature_check($send($admin,$token,$image,2)[0]===409,'Backfill requires an approved quotation');
    feature_check($send($admin,$token,$image)[0]===200,'Admin sends design for approved quotation');
    $savedFiles[]=$pdo->query('SELECT design_path FROM quotations WHERE id=1')->fetchColumn();
    feature_check($send($super,$superToken,$image,3)[0]===200,'Super Admin sends another customer design');
    $savedFiles[]=$pdo->query('SELECT design_path FROM quotations WHERE id=3')->fetchColumn();
    feature_check($send($admin,$token,$image)[0]===409,'Existing design cannot be silently overwritten');
    feature_check($state($admin,$token,'paused')[0]===200,'Pause still works after a design is attached');
    feature_check($send($admin,$token,$image)[0]===409,'Paused projects block design changes');
    feature_check($state($admin,$token,'active')[0]===200,'Resume remains available with a design');
    $create=['project_id'=>2,'customer_id'=>2,'target_completion'=>'2027-01-01','items'=>json_encode([['description'=>'Cabinet','qty'=>1,'unit_cost'=>1000]]),'csrf'=>$token,'design_image'=>new CURLFile($image,'image/png','design.png')];
    $badCreate=$create; $badCreate['design_image']=new CURLFile($bad,'image/png','fake.png');
    $quoteCount=(int)$pdo->query('SELECT COUNT(*) FROM quotations')->fetchColumn();
    feature_check(feature_http($admin,$base.'/admin/create_quotation.php',$badCreate)[0]===400,'Invalid design rejects the entire quotation');
    feature_check((int)$pdo->query('SELECT COUNT(*) FROM quotations')->fetchColumn()===$quoteCount,'Rejected upload leaves no partial quotation');
    [$status,$json]=feature_http($admin,$base.'/admin/create_quotation.php',$create);$result=json_decode($json,true);
    feature_check($status===200 && !empty($result['ok']),'Quotation and image are sent together');
    $q=$pdo->query('SELECT * FROM quotations ORDER BY id DESC LIMIT 1')->fetch();$savedFiles[]=$q['design_path'];
    feature_check((int)$q['customer_id']===1 && !empty($q['design_path']),'Quotation derives ownership from the project');
    feature_check(feature_http($admin,$base.'/design_image.php?quotation_id='.$q['id'])[0]===200,'Back office can view design');
    $client=curl_init();curl_setopt($client,CURLOPT_COOKIEFILE,'');
    feature_http($client,$base.'/user-dashboard/my_projects.php?dev_user=client');
    [$status,$bytes]=feature_http($client,$base.'/design_image.php?quotation_id='.$q['id']);
    feature_check($status===200 && $bytes===file_get_contents($image),'Owning client receives exact design image');
    feature_check(feature_http($client,$base.'/design_image.php?quotation_id=3')[0]===404,'Client cannot view another customer design');
    [$status,$html]=feature_http($client,$base.'/user-dashboard/my_projects.php');
    feature_check($status===200 && strpos($html,'View design')!==false && strpos($html,'uploads/project-designs/')===false,'Client sees design button without private storage paths');
    [$status,$html]=feature_http($admin,$base.'/admin/reports.php');
    feature_check($status===200 && strpos($html,'Labor &amp; Additional Charges')!==false && strpos($html,'PRJ-TEST-001')!==false && strpos($html,'Demo Customer')!==false,'Costing report contains identifiers and accurate label');
    // A fresh migration is idempotent and the new design folder is included in backups.
    feature_check(feature_http($admin,$base.'/admin/monitoring.php')[0]===200,'Repeated schema initialization is safe');
    require_once __DIR__ . '/../includes/backup.php';
    $backupDir=$temp.'/backups';mkdir($backupDir);
    mkdir($temp.'/uploads'); mkdir($temp.'/uploads/project-designs');
    foreach($savedFiles as $file) copy($root.'/'.$file,$temp.'/uploads/project-designs/'.basename($file));
    $service=new SystemBackup($pdo,$temp.'/uploads',$backupDir);
    $copy=$service->create('Feature test');
    $zip=new ZipArchive();$zip->open($service->path($copy));
    $manifest=json_decode($zip->getFromName('manifest.json'),true);
    feature_check(isset($manifest['files'][substr($savedFiles[0],8)]),'Backup includes design image');$zip->close();
    unlink($service->path($copy));
    $pdo->exec("INSERT INTO projects (id,project_code,customer_id,project_name,status,progress) VALUES (5,'PRJ-TEST-005',1,'Legacy Approved Cabinet','approved',10)");
    $pdo->exec("INSERT INTO quotations (quote_code,customer_id,project_id,project_name,date_created,status,total_amount) VALUES ('QT-TEST-005',1,5,'Legacy Approved Cabinet',CURDATE(),'Approved',1830)");
    if (in_array('--serve',$argv,true)) { echo 'READY FOR UI: ',$base,PHP_EOL; fflush(STDOUT); fgets(STDIN); }
} finally {
    if (is_resource($server)) { proc_terminate($server); foreach($pipes as $pipe) if(is_resource($pipe))fclose($pipe);proc_close($server); }
    if ($created) {
        // Collect only fixture paths before dropping this randomly named fixture database.
        try { foreach($pdo->query("SELECT design_path FROM quotations WHERE design_path IS NOT NULL") as $q)$savedFiles[]=$q['design_path']; } catch(Throwable $e){}
        $pdo->exec('USE `' . $source . '`');
        if (preg_match('/^vast_features_test_[a-f0-9]{12}$/D',$test)) $pdo->exec('DROP DATABASE `' . $test . '`');
    }
    foreach(array_unique($savedFiles) as $path) if(preg_match('~^uploads/project-designs/[a-f0-9]{40}\.(png|jpg|webp)$~D',(string)$path)) @unlink($root.'/'.$path);
    echo 'Fixture database and uploaded test images removed.',PHP_EOL;
}
