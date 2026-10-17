<?php
/** Real HTTP failures in a temporary server. Never stops or modifies MySQL. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
function error_check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ', $message, PHP_EOL;
}
function error_http(string $url, string $accept = 'text/html'): array {
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>['Accept: '.$accept]]);
    $body=curl_exec($ch); if($body===false)throw new RuntimeException(curl_error($ch));
    $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); $type=curl_getinfo($ch,CURLINFO_CONTENT_TYPE); curl_close($ch);
    return [$code,$body,$type];
}
$root=dirname(__DIR__); $temp=sys_get_temp_dir().'/vast-errors-'.bin2hex(random_bytes(6)); mkdir($temp);
$bootstrap=var_export($root.'/includes/error_handling.php',true);
$fatal=var_export($temp.'/fatal.php',true);
file_put_contents($temp.'/fatal.php','<?php class ErrorTestDuplicate { public function method() {} public function method() {} }');
$router=<<<'PHP'
<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(strpos($path,'/_error-test/')!==0)return false;
require BOOTSTRAP_PATH;
switch(basename($path)){
case 'healthy': echo 'Healthy response'; break;
case 'validation': http_response_code(400); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Enter a valid email address.']); break;
case 'caught': http_response_code(500); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>app_error_message(new RuntimeException('PRIVATE_TEST_MARKER caught database credentials'))]); break;
case 'json': header('Content-Type: application/json'); echo '{"partial":"PRIVATE_TEST_MARKER'; throw new PDOException('SQLSTATE PRIVATE_TEST_MARKER database query failed');
case 'early-json': $_SERVER['SCRIPT_NAME']='/admin/save_user.php'; throw new PDOException('SQLSTATE PRIVATE_TEST_MARKER early database failure');
case 'warning': trigger_error('PRIVATE_TEST_MARKER internal warning',E_USER_WARNING); break;
case 'fatal': require FATAL_PATH; break;
default: echo '<h1>PRIVATE_TEST_MARKER unfinished page</h1>'; throw new RuntimeException('PRIVATE_TEST_MARKER internal stack details');
}
PHP;
file_put_contents($temp.'/router.php',str_replace(['BOOTSTRAP_PATH','FATAL_PATH'],[$bootstrap,$fatal],$router));
$server=null;
try {
    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr);$addr=stream_socket_get_name($socket,false);fclose($socket);
    // Reserve a separate unused port for simulated DB connection failures.
    $dbSocket=stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr);$dbAddress=stream_socket_get_name($dbSocket,false);fclose($dbSocket);
    $env=getenv();$env['DEV_MODE']='false';$env['BASE_URL']='/';$env['DB_HOST']='127.0.0.1';$env['DB_PORT']=explode(':',$dbAddress)[1];
    $server=proc_open([PHP_BINARY,'-d','display_errors=1','-d','log_errors=1','-d','error_log='.$temp.'/php.log','-S',$addr,'-t',$root,$temp.'/router.php'],
        [0=>['pipe','r'],1=>['file',$temp.'/server.log','a'],2=>['file',$temp.'/server.log','a']],$pipes,$root,$env);
    if(!is_resource($server))throw new RuntimeException('Cannot start the temporary test server.');
    for($i=0;$i<30;$i++){$s=@fsockopen(explode(':',$addr)[0],(int)explode(':',$addr)[1]);if($s){fclose($s);break;}usleep(100000);}
    $base='http://'.$addr;
    foreach(['exception','warning','fatal','json','early-json','caught'] as $case){
        [$code,$body,$type]=error_http($base.'/_error-test/'.$case);
        error_check($code===500,$case.' returns HTTP 500');
        error_check(strpos($body,'PRIVATE_TEST_MARKER')===false && strpos($body,'SQLSTATE')===false && strpos($body,'Stack trace')===false,$case.' hides technical and partial output');
        if(in_array($case,['json','early-json','caught'],true)){
            $data=json_decode($body,true);
            error_check(strpos($type,'application/json')===0 && is_array($data) && $data['ok']===false && !empty($data['error']),$case.' returns valid friendly JSON');
        }else error_check(strpos($body,'Something went wrong')!==false && strpos($body,'Go to home page')!==false,$case.' displays a friendly error page');
    }
    [$code,$body]=error_http($base.'/download_quote.php?id=1');
    error_check($code===500 && strpos($body,'Something went wrong')!==false && strpos($body,'SQLSTATE')===false,'Actual app page handles a DB connection failure');
    [$code,$body,$type]=error_http($base.'/admin/save_user.php','application/json');
    // DEV_MODE false produces a normal authorization response without a database.
    error_check(in_array($code,[401,403],true),'Normal authorization remains enforced');
    [$code,$body]=error_http($base.'/error.php');
    error_check($code===200 && strpos($body,'Something went wrong')!==false,'Error page works while the database is unavailable');
    [$code,$body]=error_http($base.'/_error-test/healthy');
    error_check($code===200 && $body==='Healthy response','Successful responses are preserved');
    [$code,$body]=error_http($base.'/_error-test/validation','application/json');
    error_check($code===400 && json_decode($body,true)['error']==='Enter a valid email address.','Specific validation messages remain readable');
    $log=file_get_contents($temp.'/php.log');
    error_check(strpos($log,'PRIVATE_TEST_MARKER')!==false && strpos($log,'ERR-')!==false,'Server logs retain diagnostics and reference codes');
    if(in_array('--serve',$argv,true)){echo 'READY FOR ERROR PREVIEW: ',$base,'/_error-test/exception',PHP_EOL;fflush(STDOUT);fgets(STDIN);}
}finally{
    if(is_resource($server)){proc_terminate($server);foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);proc_close($server);}
    // Keep only private temporary logs for diagnosis; no testing routes are installed in the app.
    @unlink($temp.'/router.php');@unlink($temp.'/fatal.php');
    echo 'Temporary error simulation stopped. Business data was not changed.',PHP_EOL;
}
