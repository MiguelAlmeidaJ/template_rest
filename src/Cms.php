<?php
declare(strict_types=1);
namespace App;
use DomainException;
final class Cms{
 public static function definitions():array{
 return [
 'inicio'=>['label'=>'Início','fields'=>['hero_eyebrow'=>['Chamada da capa','O sabor de se sentir em casa'],'hero_title'=>['Título da capa','Tempero de casa.'],'hero_subtitle'=>['Destaque da capa','Sabor de verdade.'],'hero_description'=>['Descrição da capa','Comida mineira, variedade todos os dias e aquele carinho que transforma o almoço em um momento especial.'],'featured_title'=>['Título dos sabores','Uma mesa cheia de sabores.'],'featured_description'=>['Texto dos sabores','Da tradição mineira às opções mais leves, aqui sempre tem algo gostoso esperando por você.'],'about_title'=>['Título do restaurante','Um lugar para se reunir e saborear.'],'about_description'=>['Texto do restaurante','Ambiente amplo, climatizado e acolhedor, com espaço para 144 pessoas. Sinta-se à vontade: a casa é sua.']],'images'=>['hero'=>'Imagem da capa','meat'=>'Comida mineira','salad'=>'Saladas','pasta'=>'Massas','interior'=>'Interior']],
 'cardapio'=>['label'=>'Cardápio','fields'=>['page_title'=>['Título','Tem sempre algo gostoso no cardápio.'],'page_description'=>['Introdução','Comida mineira, carnes variadas, massas, saladas e sobremesas. Nossa seleção muda ao longo dos dias — confirme as opções de hoje pelo WhatsApp.'],'section_title'=>['Título da seção','Nossos sabores.']],'images'=>['meat'=>'Comida mineira','salad'=>'Saladas','pasta'=>'Massas']],
 'o-restaurante'=>['label'=>'O Restaurante','fields'=>['page_title'=>['Título','Um restaurante feito para receber bem.'],'page_description'=>['Introdução','Comida saborosa, variedade e um ambiente em que todo mundo pode se sentir à vontade.'],'about_title'=>['Título da história','O sabor da vida está em quem tempera.'],'about_description'=>['História e estrutura','No Restaurante da Bia, o almoço vai além do prato. Nosso espaço é amplo, bem iluminado e climatizado, com 144 lugares para receber famílias, amigos e quem valoriza uma refeição feita com cuidado.']],'images'=>['interior'=>'Foto do restaurante']],
 'galeria'=>['label'=>'Galeria','fields'=>['page_title'=>['Título','Sabores, encontros e momentos.'],'page_description'=>['Descrição','Um espaço para conhecer o clima do restaurante. As imagens atuais são demonstrativas e serão substituídas por fotografias reais.']],'images'=>[]],
 'contato'=>['label'=>'Contato','fields'=>['page_title'=>['Título','Vamos conversar?'],'page_description'=>['Introdução','Confira onde estamos, nosso horário de funcionamento e os canais para falar com a equipe.']],'images'=>['table'=>'Foto da página']]
 ];
 }
 public static function field(string $page,string $key,string $default=''):string{
  static $cache=[];$id="$page.$key";if(array_key_exists($id,$cache))return $cache[$id];
  try{$q=Db::pdo()->prepare('SELECT field_value FROM cms_fields WHERE page_slug=? AND field_key=?');$q->execute([$page,$key]);$v=$q->fetchColumn();return $cache[$id]=$v===false?$default:(string)$v;}catch(\PDOException $e){error_log((string)$e);return $default;}
 }
 public static function setting(string $key):string{
  try{$q=Db::pdo()->prepare('SELECT setting_value FROM cms_settings WHERE setting_key=?');$q->execute([$key]);return (string)($q->fetchColumn()?:'');}catch(\PDOException $e){error_log((string)$e);return '';}
 }
 public static function image(string $page,string $slot):string{return self::field($page,'image_'.$slot,'');}
 public static function saveField(string $page,string $key,string $value):void{
  $q=Db::pdo()->prepare('INSERT INTO cms_fields(page_slug,field_key,field_value) VALUES(?,?,?) ON DUPLICATE KEY UPDATE field_value=VALUES(field_value)');$q->execute([$page,$key,$value]);
 }
 public static function upload(array $file,string $prefix):string{
  if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??''))throw new DomainException('Envio de imagem inválido');
  if(($file['size']??0)>5*1024*1024||($file['size']??0)<1)throw new DomainException('Imagem deve ter até 5 MB');
  $info=@getimagesize($file['tmp_name']);$mime=$info['mime']??'';
  $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if(!isset($types[$mime])||!isset($info[0],$info[1])||$info[0]>6000||$info[1]>6000)throw new DomainException('Use JPG, PNG ou WebP de até 6000 pixels');
  $finfo=new \finfo(FILEINFO_MIME_TYPE);
  if($finfo->file($file['tmp_name'])!==$mime)throw new DomainException('Tipo de imagem inconsistente');
  $dir=dirname(__DIR__).'/public/assets/images/bia/uploads';
  if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir))throw new DomainException('Não foi possível criar pasta de uploads');
  $name=$prefix.'-'.bin2hex(random_bytes(12)).'.'.$types[$mime];
  if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name))throw new DomainException('Pasta de imagens sem permissão de escrita');
  return '/assets/images/bia/uploads/'.$name;
 }
 public static function asset(string $url):string{
  if(!preg_match('#^/assets/images/bia/uploads/[a-zA-Z0-9_-]+\.(jpg|png|webp)$#',$url))throw new DomainException('Imagem inválida');return $url;
 }
 public static function gallery():array{return Db::pdo()->query('SELECT * FROM cms_gallery ORDER BY sort_order ASC,id DESC')->fetchAll();}
}
