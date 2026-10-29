<?php
/** Customer-facing scope text, separate from internal costing and justification. */
function ensure_quotation_description(): void {
    static $ready=false;
    if ($ready) return;
    $columns=array_column(db()->query('SHOW COLUMNS FROM quotations')->fetchAll(),'Field');
    if (!in_array('description_json',$columns,true)) {
        try { db()->exec('ALTER TABLE quotations ADD COLUMN description_json TEXT NULL'); }
        catch (PDOException $e) { if (($e->errorInfo[1]??0)!==1060) throw $e; }
    }
    $ready=true;
}
function quotation_description_defaults(): array {
    return ['scope'=>'Supply and Installation of Main Kitchen Cabinets', 'finishes'=>'Finishes: ',
        'carcass'=>'Carcass: ', 'doors'=>'Doors: ', 'inclusions'=>'Inclusions: ', 'accessories'=>'Accessories: '];
}
function quotation_description_fields(?string $json): array {
    $saved=json_decode($json??'',true);
    $fields=quotation_description_defaults();
    if (is_array($saved)) foreach($fields as $key=>$value) if(is_string($saved[$key]??null))$fields[$key]=$saved[$key];
    return $fields;
}
function quotation_description_text(?string $json): string {
    return implode("\n",quotation_description_fields($json));
}
