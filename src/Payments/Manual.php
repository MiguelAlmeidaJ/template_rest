<?php
declare(strict_types=1);
namespace App\Payments;
use App\Db;
use DomainException;
final class Manual{
 public static function confirm(int $id,int $admin):void{
 $db=Db::pdo();$db->beginTransaction();
 try{
 $q=$db->prepare('SELECT payment_mode,payment_status,status,manual_payment_method FROM orders WHERE id=? FOR UPDATE');$q->execute([$id]);$o=$q->fetch();
 if(!$o||$o['payment_mode']!=='manual'||$o['payment_status']!=='pending'||$o['status']==='cancelled'||!in_array($o['manual_payment_method'],['card','pix','cash'],true))throw new DomainException('Pedido não pode ter baixa manual');
 $q=$db->prepare("UPDATE orders SET payment_status='paid',paid_at=NOW() WHERE id=?");$q->execute([$id]);
 $q=$db->prepare("INSERT INTO payment_audit(order_id,admin_id,action) VALUES(?,?,'manual_paid')");$q->execute([$id,$admin]);$db->commit();
 }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
 }
}
