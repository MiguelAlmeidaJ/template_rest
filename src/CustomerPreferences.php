<?php
declare(strict_types=1);

namespace App;

use DomainException;

final class CustomerPreferences
{
    public static function addresses(int $customerId): array
    {
        $query = Db::pdo()->prepare(
            'SELECT id,label,recipient,phone,address,neighborhood,reference_point,is_default
             FROM customer_delivery_addresses WHERE customer_id=? ORDER BY is_default DESC,id DESC'
        );
        $query->execute([$customerId]);
        return $query->fetchAll();
    }

    public static function favorites(int $customerId): array
    {
        $query = Db::pdo()->prepare(
            'SELECT id,name,boxes_json FROM customer_lunch_favorites WHERE customer_id=? ORDER BY id DESC'
        );
        $query->execute([$customerId]);
        return $query->fetchAll();
    }

    public static function saveAddress(int $customerId, array $data): void
    {
        $fields = ['label'=>70,'recipient'=>150,'phone'=>24,'address'=>350,'neighborhood'=>120,'reference_point'=>350];
        $values = [];
        foreach ($fields as $key=>$limit) {
            $value=trim((string)($data[$key]??''));
            if (!mb_check_encoding($value,'UTF-8') || mb_strlen($value)>$limit) {
                throw new DomainException('Campo inválido: '.$key);
            }
            $values[$key]=$value;
        }
        if (mb_strlen($values['address'])<8 || mb_strlen($values['neighborhood'])<2
            || mb_strlen($values['recipient'])<2 || strlen(preg_replace('/\D/','',$values['phone']))<10) {
            throw new DomainException('Preencha nome, telefone, endereço e bairro.');
        }
        $values['label']=$values['label']?:'Meu endereço';
        $id=filter_var($data['id']??0,FILTER_VALIDATE_INT)?:0;
        $db=Db::pdo();
        $db->beginTransaction();
        try {
            $count=$db->prepare('SELECT COUNT(*) FROM customer_delivery_addresses WHERE customer_id=?');
            $count->execute([$customerId]);
            if (!$id && (int)$count->fetchColumn()>=10) {
                throw new DomainException('Você pode cadastrar até 10 endereços.');
            }
            if ($id) {
                $query=$db->prepare(
                    'UPDATE customer_delivery_addresses SET label=?,recipient=?,phone=?,address=?,neighborhood=?,reference_point=? WHERE id=? AND customer_id=?'
                );
                $query->execute([...array_values($values),$id,$customerId]);
                if (!$query->rowCount()) {
                    $check=$db->prepare('SELECT id FROM customer_delivery_addresses WHERE id=? AND customer_id=?');
                    $check->execute([$id,$customerId]);
                    if (!$check->fetch()) throw new DomainException('Endereço não encontrado.');
                }
            } else {
                $query=$db->prepare(
                    'INSERT INTO customer_delivery_addresses(customer_id,label,recipient,phone,address,neighborhood,reference_point)
                     VALUES(?,?,?,?,?,?,?)'
                );
                $query->execute([$customerId,...array_values($values)]);
                $id=(int)$db->lastInsertId();
            }
            $default=($data['is_default']??'')==='1';
            $count->execute([$customerId]);
            if ($default || (int)$count->fetchColumn()===1) {
                $db->prepare('UPDATE customer_delivery_addresses SET is_default=(id=?) WHERE customer_id=?')->execute([$id,$customerId]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function deleteAddress(int $customerId, int $id): void
    {
        $db=Db::pdo();
        $query=$db->prepare('DELETE FROM customer_delivery_addresses WHERE id=? AND customer_id=?');
        $query->execute([$id,$customerId]);
    }

    public static function saveFavorite(int $customerId, string $name, array $boxes): void
    {
        $name=trim($name);
        if (mb_strlen($name)<2 || mb_strlen($name)>100 || count($boxes)<1 || count($boxes)>20) {
            throw new DomainException('Informe um nome e uma montagem válida.');
        }
        $menu=Lunch::items();
        $allowed=array_fill_keys(array_map(fn($item)=>(int)$item['id'],$menu),true);
        foreach ($boxes as $box) {
            if (!is_array($box)||!isset($box['items'])||!is_array($box['items'])||!count($box['items'])) {
                throw new DomainException('Montagem inválida.');
            }
            foreach ($box['items'] as $itemId) {
                if (!isset($allowed[(int)$itemId])) throw new DomainException('Um prato não está disponível hoje.');
            }
        }
        $db=Db::pdo();
        $count=$db->prepare('SELECT COUNT(*) FROM customer_lunch_favorites WHERE customer_id=?');
        $count->execute([$customerId]);
        if ((int)$count->fetchColumn()>=20) throw new DomainException('Limite de 20 pedidos favoritos.');
        $query=$db->prepare('INSERT INTO customer_lunch_favorites(customer_id,name,boxes_json) VALUES(?,?,?)');
        $query->execute([$customerId,$name,json_encode($boxes,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
    }

    public static function deleteFavorite(int $customerId,int $id): void
    {
        $query=Db::pdo()->prepare('DELETE FROM customer_lunch_favorites WHERE id=? AND customer_id=?');
        $query->execute([$id,$customerId]);
    }
}
