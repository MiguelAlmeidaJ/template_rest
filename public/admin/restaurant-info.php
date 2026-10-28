<?php
declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\Cms;use App\Db;
$fields=['name'=>'Nome do restaurante','city'=>'Cidade e estado','address'=>'Rua, número e complemento','district'=>'Bairro','hours'=>'Horário de atendimento','phone'=>'Telefone / WhatsApp','whatsapp'=>'WhatsApp (somente números com DDI)','instagram'=>'Instagram (URL completa)','maps'=>'Link para localização no Google Maps'];
$defaults=['name'=>'Restaurante da Bia','city'=>'Além Paraíba, MG','address'=>'Rua Dr. Sobral Pinto, 425','district'=>'Vila Laroca','hours'=>'Todos os dias · 10h30 às 15h','phone'=>'(32) 98711-2340','whatsapp'=>'5532987112340','instagram'=>'','maps'=>''];
if($_SERVER['REQUEST_METHOD']==='POST'){cms_csrf();try{
 $saved=[];
 foreach($fields as $key=>$label){$value=trim((string)($_POST[$key]??''));if(!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>300)throw new DomainException('Valor inválido: '.$label);
 if($key==='whatsapp'&&$value!==''&&!preg_match('/^[0-9]{10,15}$/',$value))throw new DomainException('WhatsApp deve conter apenas números, incluindo código do país.');
 if(in_array($key,['instagram','maps'],true)&&$value!==''&&(!filter_var($value,FILTER_VALIDATE_URL)||!str_starts_with($value,'https://')))throw new DomainException('Informe uma URL HTTPS válida: '.$label);
 $saved['restaurant_'.$key]=$value;
 }
 $db=Db::pdo();$db->beginTransaction();try{$q=$db->prepare('INSERT INTO cms_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');foreach($saved as $key=>$value)$q->execute([$key,$value]);$db->commit();}catch(Throwable $e){$db->rollBack();throw $e;}cms_notice('Informações do restaurante atualizadas.');
 }catch(Throwable $e){cms_notice($e instanceof DomainException?$e->getMessage():'Erro ao salvar informações.','danger');}}
adm_start('Informações do restaurante','settings');cms_feedback();?><div class="mb-4"><a href="/admin/settings.php" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Configurações</a><h1 class="page-title">Informações do restaurante</h1><p class="page-subtitle">Atualize informações de contato, endereço e funcionamento exibidas no site público.</p></div>
<section class="content-card p-4" style="max-width:850px"><form method="post"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><div class="row g-3"><?php foreach($fields as $key=>$label):?><div class="col-12 <?=$key==='name'||$key==='address'?'':'col-md-6'?>"><label for="field-<?=adm_h($key)?>" class="form-label"><?=adm_h($label)?></label><input class="form-control" id="field-<?=adm_h($key)?>" name="<?=adm_h($key)?>" value="<?=adm_h(Cms::setting('restaurant_'.$key)?:$defaults[$key])?>" maxlength="300" placeholder="<?=adm_h($defaults[$key])?>"></div><?php endforeach;?></div><div class="mt-4"><button class="btn btn-bia"><i class="bi bi-check2 me-2"></i>Salvar informações</button></div></form></section><?php adm_end();