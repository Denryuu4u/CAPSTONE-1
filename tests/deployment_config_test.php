<?php
/** Real Apache routing/session checks in a private temporary document root.
 * Uses the installed XAMPP binary without changing or restarting its main server.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$apache = 'C:/xampp/apache/bin/httpd.exe';
if (!is_file($apache)) { echo "Requires local XAMPP Apache on Windows.\n"; exit(1); }
function deploy_check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ', $message, PHP_EOL;
}
function deploy_http($ch, string $url): array {
    curl_setopt_array($ch,[CURLOPT_URL=>$url,CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15]);
    $body=curl_exec($ch);if($body===false)throw new RuntimeException(curl_error($ch));
    return [curl_getinfo($ch,CURLINFO_HTTP_CODE),$body];
}
$root=str_replace('\\','/',dirname(__DIR__));
$temp=str_replace('\\','/',sys_get_temp_dir()).'/vast-deploy-'.bin2hex(random_bytes(6));
mkdir($temp);mkdir($temp.'/site');mkdir($temp.'/site/includes');
copy($root.'/error.php',$temp.'/site/error.php');copy($root.'/includes/error_handling.php',$temp.'/site/includes/error_handling.php');
mkdir($temp.'/site/assets');
foreach (['assets/connection.js','connection-worker.js','connection-check.php'] as $asset) copy($root.'/'.$asset,$temp.'/site/'.$asset);
file_put_contents($temp.'/site/.htaccess',"SetEnv DEV_MODE true\nErrorDocument 404 \"LOCAL OVERRIDE MUST NOT APPLY\"\n");
$auth=var_export($root.'/includes/auth.php',true);
file_put_contents($temp.'/site/probe.php',"<?php session_name('VAST_DEPLOY_TEST'); require $auth; header('Content-Type: application/json'); echo json_encode(['dev'=>DEV_MODE,'user'=>current_user()]);");
file_put_contents($temp.'/site/seed.php',"<?php session_name('VAST_DEPLOY_TEST'); session_start(); \$_SESSION=['user_id'=>1,'role'=>'Super Admin','full_name'=>'Disposable dev session','dev_active'=>'superadmin']; echo 'Test session seeded';");
file_put_contents($temp.'/site/gate.php',"<?php session_name('VAST_DEPLOY_TEST'); require $auth; require_login(); echo 'PROTECTED';");
mkdir($temp.'/site/uploads');mkdir($temp.'/site/uploads/project-designs');mkdir($temp.'/site/uploads/.system-backups');
file_put_contents($temp.'/site/uploads/project-designs/protected.txt','PRIVATE');file_put_contents($temp.'/site/uploads/.system-backups/protected.txt','PRIVATE');file_put_contents($temp.'/site/uploads/otp_log.txt','PRIVATE');
$socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr);$addr=stream_socket_get_name($socket,false);fclose($socket);
$site=$temp.'/site';
$productionConfig=str_replace('/var/www/html',$site,file_get_contents($root.'/apache-errors.conf'));
file_put_contents($temp.'/production.conf',$productionConfig);
$conf=<<<CONF
ServerRoot "C:/xampp/apache"
Listen $addr
ServerName 127.0.0.1
PidFile "$temp/apache.pid"
ErrorLog "$temp/apache.log"
LogLevel warn
LoadModule alias_module modules/mod_alias.so
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule dir_module modules/mod_dir.so
LoadModule env_module modules/mod_env.so
LoadModule mime_module modules/mod_mime.so
TypesConfig "C:/xampp/apache/conf/mime.types"
LoadFile "C:/xampp/php/php8ts.dll"
LoadFile "C:/xampp/php/libpq.dll"
LoadFile "C:/xampp/php/libsqlite3.dll"
LoadModule php_module "C:/xampp/php/php8apache2_4.dll"
PHPINIDir "C:/xampp/php"
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>
SetEnv BASE_URL /
DocumentRoot "$site"
<Directory />
    AllowOverride None
    Require all denied
</Directory>
<Directory "$site">
    Require all granted
</Directory>
Include "$temp/production.conf"
Alias /CAPSTONE-1 "$root"
<Directory "$root">
    AllowOverride All
    Require all granted
    SetEnv BASE_URL /CAPSTONE-1
</Directory>
CONF;
file_put_contents($temp.'/httpd.conf',$conf);
$server=null;$ch=curl_init();curl_setopt($ch,CURLOPT_COOKIEFILE,'');
try {
    $env=getenv();unset($env['DEV_MODE']);
    $syntax=proc_open([$apache,'-t','-f',$temp.'/httpd.conf'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$syntaxPipes,$root,$env);
    fclose($syntaxPipes[0]);$output=stream_get_contents($syntaxPipes[1]).stream_get_contents($syntaxPipes[2]);fclose($syntaxPipes[1]);fclose($syntaxPipes[2]);
    deploy_check(proc_close($syntax)===0,'Production Apache configuration parses: '.trim($output));
    $server=proc_open([$apache,'-X','-f',$temp.'/httpd.conf'],[0=>['pipe','r'],1=>['file',$temp.'/stdout.log','a'],2=>['file',$temp.'/stderr.log','a']],$pipes,$root,$env);
    if(!is_resource($server))throw new RuntimeException('Cannot start test Apache.');
    for($i=0;$i<50;$i++){$s=@fsockopen(explode(':',$addr)[0],(int)explode(':',$addr)[1]);if($s){fclose($s);break;}usleep(100000);}
    $base='http://'.$addr;
    foreach (['','/CAPSTONE-1'] as $prefix) {
        [$status,$body]=deploy_http($ch,$base.$prefix.'/connection-check.php');
        deploy_check($status===204 && $body==='','Connection probe works without authentication at '.$prefix.'/');
        [$status,$body]=deploy_http($ch,$base.$prefix.'/assets/connection.js');
        deploy_check($status===200 && strpos($body,'You are offline.')!==false,'Connection script is served at '.$prefix.'/');
        [$status,$body]=deploy_http($ch,$base.$prefix.'/connection-worker.js');
        deploy_check($status===200 && strpos($body,'Connection problem')!==false,'Offline navigation worker is served at '.$prefix.'/');
    }
    [$status,$body]=deploy_http($ch,$base.'/admin1/project-requests.php');
    deploy_check($status===404 && strpos($body,'Page not found')!==false && strpos($body,'Additionally, a 404')===false,'Root-hosted missing route uses the custom 404 without recursion');
    deploy_check(strpos($body,'href="/index.php"')!==false,'Hosted error page links to the domain root');
    [$status,$body]=deploy_http($ch,$base.'/CAPSTONE-1/admin1/project-requests.php');
    deploy_check($status===404 && strpos($body,'Page not found')!==false,'Local /CAPSTONE-1 missing route uses the custom 404');
    deploy_check(strpos($body,'href="/CAPSTONE-1/index.php"')!==false,'Local error page retains its correct base path');
    [$status,$body]=deploy_http($ch,$base.'/probe.php');$data=json_decode($body,true);
    deploy_check($status===200 && is_array($data) && $data['dev']===false && $data['user']===null,'Missing DEV_MODE defaults off and ignores a local SetEnv true override');
    deploy_http($ch,$base.'/seed.php');
    [$status,$body]=deploy_http($ch,$base.'/probe.php');$data=json_decode($body,true);
    deploy_check($status===200 && is_array($data) && $data['user']===null,'Disabling DEV_MODE revokes old development sessions');
    deploy_check(deploy_http($ch,$base.'/gate.php')[0]===302,'An old dev session cannot open protected pages');
    foreach(['project-designs/protected.txt','.system-backups/protected.txt','otp_log.txt'] as $file){
        [$status,$body]=deploy_http($ch,$base.'/uploads/'.$file);
        deploy_check($status===403 && strpos($body,'PRIVATE')===false,'Production protects '.$file.' with overrides disabled');
    }
    $dockerIgnore=file_get_contents($root.'/.dockerignore');$docker=file_get_contents($root.'/Dockerfile');
    deploy_check(strpos($dockerIgnore,"/.htaccess")!==false,'Docker excludes the local .htaccess');
    deploy_check(strpos($docker,'ENV DEV_MODE=false BASE_URL=""')!==false,'Production image defines safe login and URL defaults');
    if(in_array('--serve',$argv,true)){echo 'READY FOR APACHE 404 PREVIEW: ',$base,'/admin1/project-requests.php',PHP_EOL;fflush(STDOUT);fgets(STDIN);}
}finally{
    curl_close($ch);
    if(is_resource($server)){proc_terminate($server);foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);proc_close($server);}
    echo 'Separate test Apache stopped. Main server and business records were not changed.',PHP_EOL;
}
