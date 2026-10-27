<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
use App\Db;
use App\Api;
use App\Modules;
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$method=$_SERVER['REQUEST_METHOD'];
try {
 if($path==='/' && $method==='GET'){header('Content-Type: text/html; charset=utf-8');readfile(__DIR__.'/site.html');exit;}
 if($path==='/api/health' && $method==='GET')Api::send(['status'=>'ok']);
 if($path==='/api/products' && $method==='GET')Api::send(Modules::products());
 if($path==='/api/reservations/slots' && $method==='GET')Api::send(Modules::slots((string)($_GET['date']??'')));
 if($path==='/api/delivery/quote' && $method==='POST')Api::send(Modules::delivery(Api::input()));
 if($path==='/api/orders' && $method==='POST')Api::send(Modules::order(Api::input()),201);
 if($path==='/api/reservations' && $method==='POST')Api::send(Modules::reservation(Api::input()),201);
 if($path==='/api/payments/checkout' && $method==='POST')Api::send(Modules::checkout(Api::input()));
 Api::send(['error'=>'Rota não encontrada'],404);
} catch(DomainException $e){Api::send(['error'=>$e->getMessage()],422);}
catch(Throwable $e){error_log((string)$e);Api::send(['error'=>'Erro interno'],500);}
