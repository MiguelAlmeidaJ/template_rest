<?php
declare(strict_types=1);
namespace App\Payments;
use App\Db;
use DomainException;
final class Settings {
 public static function get():array{
 $s=Db::pdo()->query('SELECT online_enabled,provider FROM payment_settings WHERE id=1')->fetch();
 if(!$s)throw new DomainException('Execute a migração de pagamentos');
 return ['online_enabled'=>(bool)$s['online_enabled'],'provider'=>$s['provider']];
 }
 public static function save(bool $enabled,string $provider):void{
 if(!in_array($provider,['mercadopago','pagbank'],true))throw new DomainException('Gateway inválido');
 if($enabled)throw new DomainException('Pagamento real bloqueado até implementação e validação de API e webhooks');
 $q=Db::pdo()->prepare('UPDATE payment_settings SET online_enabled=0,provider=? WHERE id=1');$q->execute([$provider]);
 }
}
