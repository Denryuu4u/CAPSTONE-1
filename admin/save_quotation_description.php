<?php
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/quotation_description.php';
require_page('quotations',true);
header('Content-Type: application/json; charset=utf-8');
require_project_csrf();
ensure_quotation_description();
$id=(int)($_POST['quotation_id']??0);
$st=db()->prepare('SELECT project_id FROM quotations WHERE id=?');$st->execute([$id]);$quote=$st->fetch();
if (!$quote) project_control_fail('Quotation not found.',404);
require_project_writable((int)$quote['project_id']);
$fields=[];
foreach(quotation_description_defaults() as $key=>$default) {
    $value=$_POST[$key]??null;
    if (!is_string($value) || mb_strlen($value)>2000) project_control_fail('Enter text of up to 2,000 characters in each description field.',400);
    $fields[$key]=trim($value);
}
if ($fields['scope']==='') project_control_fail('Enter the supply and installation description.',400);
try {
    db()->prepare('UPDATE quotations SET description_json=? WHERE id=?')->execute([json_encode($fields,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$id]);
    log_audit('Quotations','Updated quotation description #'.$id);
    echo json_encode(['ok'=>true]);
} catch(Throwable $e) { project_control_fail(app_error_message($e,'The description could not be saved. Please try again.'),500); }
