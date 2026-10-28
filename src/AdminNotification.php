<?php
declare(strict_types=1);
namespace App;
final class AdminNotification {
 public static function create(string $type,?int $referenceId,string $title,string $message,string $link):void{
  $types=['order','reservation','chat','payment','system'];if(!in_array($type,$types,true))throw new \DomainException('Tipo de notificação inválido');
  if(!preg_match('#^/admin/[a-z0-9/_?.=&-]+$#',$link))throw new \DomainException('Destino inválido');
  $q=Db::pdo()->prepare('INSERT INTO admin_notifications(type,reference_id,title,message,link) VALUES(?,?,?,?,?)');
  $q->execute([$type,$referenceId,mb_substr($title,0,190),mb_substr($message,0,255),$link]);
 }
 public static function unreadCount():int{return (int)Db::pdo()->query('SELECT COUNT(*) FROM admin_notifications WHERE is_read=0')->fetchColumn();}
 public static function latest(int $limit=100):array{$q=Db::pdo()->prepare('SELECT * FROM admin_notifications ORDER BY is_read ASC,created_at DESC,id DESC LIMIT ?');$q->bindValue(1,min(150,max(1,$limit)),\PDO::PARAM_INT);$q->execute();return $q->fetchAll();}
 public static function markRead(int $id):void{$q=Db::pdo()->prepare('UPDATE admin_notifications SET is_read=1,read_at=COALESCE(read_at,NOW()) WHERE id=?');$q->execute([$id]);}
}
