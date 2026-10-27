<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__).'/src/bootstrap.php';
if($argc!==3||!filter_var($argv[1],FILTER_VALIDATE_EMAIL)||strlen($argv[2])<12){fwrite(STDERR,"Uso: php scripts/create_admin.php email@dominio.com 'senha-com-12+-caracteres'\n");exit(1);}
$q=\App\Db::pdo()->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)');
$q->execute([$argv[1],password_hash($argv[2],PASSWORD_DEFAULT)]);
echo "Administrador criado/atualizado.\n";
