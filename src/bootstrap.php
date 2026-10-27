<?php
declare(strict_types=1);
spl_autoload_register(function(string $name){if(str_starts_with($name,'App\\')){ $file=__DIR__.'/'.str_replace('\\','/',substr($name,4)).'.php';if(is_file($file))require $file;}});
$file=dirname(__DIR__).'/.env';
if(is_file($file))foreach(file($file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line){$line=trim($line);if(!$line||str_starts_with($line,'#')||!str_contains($line,'='))continue;[$key,$value]=explode('=',$line,2);$_ENV[trim($key)]=trim(trim($value),'"');}
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors',($_ENV['APP_DEBUG']??'false')==='true'?'1':'0');
