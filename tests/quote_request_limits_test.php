<?php
/** Disposable database + HTTP tests; no business records are copied or changed. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/db.php';
function quota_check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ', $message, PHP_EOL;
}
function quota_http($curl, string $url, ?array $post = null): array {
    curl_setopt_array($curl, [CURLOPT_URL=>$url, CURLOPT_POST=>$post !== null, CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>20, CURLOPT_HEADER=>true]);
    if ($post !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    $response = curl_exec($curl);
    if ($response === false) throw new RuntimeException(curl_error($curl));
    return [curl_getinfo($curl,CURLINFO_HTTP_CODE), $response];
}
$pdo = db(); $source = $pdo->query('SELECT DATABASE()')->fetchColumn();
$test = 'vast_quota_test_' . bin2hex(random_bytes(6));
$root = dirname(__DIR__); $created = false; $server = null;
$temp = sys_get_temp_dir() . '/vast-quota-' . bin2hex(random_bytes(6)); mkdir($temp);
try {
    $pdo->exec("CREATE DATABASE `$test` CHARACTER SET utf8mb4"); $created = true;
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $pdo->exec("CREATE TABLE `$test`.`$table` LIKE `$source`.`$table`");
    }
    $pdo->exec("USE `$test`");
    $insert = $pdo->prepare('INSERT INTO users (id,full_name,email,role,status,password_hash,email_verified,phone,location) VALUES (?,?,?,?,?,?,1,?,?)');
    foreach ([[1,'Super Admin','admin@vastsolutions.com'],[2,'Admin','admin2@vastsolutions.com'],[4,'Client','client@demo.test']] as [$id,$role,$email]) {
        $insert->execute([$id,'Quota Test '.$role,$email,$role,'Active',password_hash('fixture-password',PASSWORD_BCRYPT),'09171234567','Fixture address']);
    }
    $pdo->exec("INSERT INTO customers (id,user_id,name,email,address) VALUES (1,4,'Quota Test Customer','client@demo.test','Fixture address'),(2,1,'Other Customer','other@example.test','Other fixture address')");
    // Exercise migration against an old schema, inside this temporary database only.
    $columns = $pdo->query('SHOW COLUMNS FROM project_requests')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('unit_quantity',$columns,true)) $pdo->exec('ALTER TABLE project_requests DROP COLUMN unit_quantity');
    require_once __DIR__ . '/../includes/helpers.php';
    ensure_request_quantity(); ensure_request_quantity();
    quota_check(in_array('unit_quantity',$pdo->query('SHOW COLUMNS FROM project_requests')->fetchAll(PDO::FETCH_COLUMN),true),'Quantity migration is repeatable');

    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr);
    $addr=stream_socket_get_name($socket,false); fclose($socket);
    $env=getenv(); $env['DB_NAME']=$test; $env['DEV_MODE']='true'; $env['BASE_URL']='/';
    $server=proc_open([PHP_BINARY,'-S',$addr,'-t',$root],[0=>['pipe','r'],1=>['file',$temp.'/server.log','a'],2=>['file',$temp.'/server.log','a']],$pipes,$root,$env);
    if (!is_resource($server)) throw new RuntimeException('Cannot start fixture server.');
    $base='http://'.$addr;
    for($i=0;$i<30;$i++){ $s=@fsockopen(explode(':',$addr)[0],(int)explode(':',$addr)[1]);if($s){fclose($s);break;}usleep(100000); }
    $client=curl_init(); curl_setopt($client,CURLOPT_COOKIEFILE,'');
    quota_http($client,$base.'/user-dashboard/submit_quote.php?dev_user=client');
    $post=['project_name'=>'Batch Cabinets','category'=>'Kitchen Cabinets','material_type'=>'Plywood','budget'=>'10000','unit_quantity'=>'10'];
    foreach (['0','11','1.5','invalid'] as $bad) {
        [$code,$body]=quota_http($client,$base.'/user-dashboard/submit_quote.php',array_merge($post,['unit_quantity'=>$bad]));
        quota_check($code===302 && strpos($body,'error=quantity')!==false,'Server rejects quantity '.$bad);
    }
    quota_check((int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn()===0,'Invalid quantities create no projects');
    [$code,$body]=quota_http($client,$base.'/user-dashboard/submit_quote.php',$post);
    quota_check($code===302 && strpos($body,'submitted=1')!==false,'Ten-unit batch submits successfully');
    $request=$pdo->query('SELECT * FROM project_requests LIMIT 1')->fetch();
    quota_check((int)$request['unit_quantity']===10 && (float)$request['budget']===10000.0,'Batch stores quantity and whole-batch budget');
    quota_check((int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn()===1,'Ten units create exactly one project');
    quota_check(customer_request_counts(4)===['pending'=>1,'ongoing'=>0],'Batch uses one pending slot');
    // Four more requests fill the allowance; omitting quantity defaults to one.
    unset($post['unit_quantity']);
    for($i=0;$i<4;$i++) {
        [$code,$body]=quota_http($client,$base.'/user-dashboard/submit_quote.php',$post);
        quota_check($code===302 && strpos($body,'submitted=1')!==false,'Pending request '.($i+2).' accepted');
    }
    quota_check((int)$pdo->query('SELECT unit_quantity FROM project_requests ORDER BY id DESC LIMIT 1')->fetchColumn()===1,'Single-unit default is preserved');
    [$code,$body]=quota_http($client,$base.'/user-dashboard/submit_quote.php',$post);
    quota_check($code===302 && strpos($body,'error=pendinglimit')!==false,'Sixth pending request is blocked');
    quota_check((int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn()===5,'Blocked submission creates no extra project');
    $pdo->exec("UPDATE projects SET status='approved'");
    $pdo->exec("UPDATE projects SET status='on_hold' WHERE id=(SELECT MIN(id) FROM (SELECT id FROM projects) p)");
    quota_check(customer_request_counts(4)===['pending'=>0,'ongoing'=>5],'Paused projects count toward the ongoing limit');
    [$code,$body]=quota_http($client,$base.'/user-dashboard/submit_quote.php',$post);
    quota_check($code===302 && strpos($body,'error=ongoinglimit')!==false,'Five ongoing projects block a new request');
    $pdo->exec("INSERT INTO projects (id,project_code,customer_id,project_name,status) VALUES (100,'PRJ-QUOTA-100',1,'Pending Approval','quote_submitted'),(101,'PRJ-QUOTA-101',2,'Other Customer Project','approved')");
    $pdo->exec("INSERT INTO quotations (id,quote_code,customer_id,project_id,project_name,date_created,status,total_amount) VALUES (100,'QT-QUOTA-100',1,100,'Pending Approval',CURDATE(),'Accepted',10000)");
    $admin=curl_init(); curl_setopt($admin,CURLOPT_COOKIEFILE,'');
    quota_http($admin,$base.'/admin/monitoring.php?dev_user=admin');
    [$code,$body]=quota_http($admin,$base.'/admin/update_quotation.php',['id'=>100,'action'=>'approve']);
    quota_check($code===409 && strpos($body,'5 ongoing projects')!==false,'Approval cannot create a sixth ongoing project');
    quota_check($pdo->query('SELECT status FROM quotations WHERE id=100')->fetchColumn()==='Accepted','Blocked approval leaves quotation unchanged');
    [$code,$body]=quota_http($admin,$base.'/admin/update_project_status.php',['project_id'=>100,'status'=>'production']);
    quota_check($code===409,'Monitoring cannot bypass ongoing capacity');
    $pdo->exec("UPDATE projects SET status='completed' WHERE id=(SELECT MIN(id) FROM (SELECT id FROM projects) p)");
    [$code,$body]=quota_http($admin,$base.'/admin/update_quotation.php',['id'=>100,'action'=>'approve']);
    quota_check($code===200 && strpos($body,'"ok":true')!==false,'Completing a project frees an ongoing slot for approval');
    quota_check(customer_request_counts(4)===['pending'=>0,'ongoing'=>5],'Other customers and completed projects do not consume slots');
    // Free capacity and prove rejected requests no longer consume pending slots.
    $pdo->exec("UPDATE projects SET status='rejected' WHERE customer_id=1");
    quota_check(customer_request_counts(4)===['pending'=>0,'ongoing'=>0],'Rejected projects free their slots');
    [$code,$body]=quota_http($client,$base.'/user-dashboard/submit_quote.php',$post);
    quota_check($code===302 && strpos($body,'submitted=1')!==false,'Customer can request again once capacity is available');
    [$code,$body]=quota_http($admin,$base.'/admin/project-requests.php');
    quota_check($code===200 && strpos($body,'Batch: 10 identical units')!==false && strpos($body,'data-unit-quantity="10"')!==false,'Admin receives the requested batch quantity');
    [$code,$body]=quota_http($client,$base.'/user-dashboard/request_quote.php');
    quota_check($code===200 && strpos($body,'Project Quantity')!==false && strpos($body,'Pending requests:')!==false,'Customer request form shows quantity and allowance');
    // Check cross-connection serialization only after HTTP tests (lock lives to shutdown).
    lock_customer_requests(4);
    $key=$pdo->query("SELECT IS_USED_LOCK('vast-requests-".substr(hash('sha256',$test),0,12)."-u4')")->fetchColumn();
    quota_check((int)$key===(int)$pdo->query('SELECT CONNECTION_ID()')->fetchColumn(),'Capacity lock is shared by customer account');
    $pdo->query('SELECT RELEASE_ALL_LOCKS()');
    if(in_array('--serve',$argv,true)){echo 'READY FOR UI: ',$base,PHP_EOL;fflush(STDOUT);fgets(STDIN);}
} finally {
    if(is_resource($server)){proc_terminate($server);foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);proc_close($server);}
    if($created){$pdo->exec("USE `$source`");if(preg_match('/^vast_quota_test_[a-f0-9]{12}$/D',$test))$pdo->exec("DROP DATABASE `$test`");}
    echo 'Temporary quota database removed.',PHP_EOL;
}
