<?php
declare(strict_types=1);
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT.'/app/helpers.php';
spl_autoload_register(static function(string $class):void{$prefix='App\\';if(!str_starts_with($class,$prefix))return;$path=APP_ROOT.'/app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';if(is_file($path))require_once $path;});
putenv('APP_KEY=base64:'.base64_encode(str_repeat('T',32)));
$tests=[];$assert=function(bool $ok,string $name)use(&$tests){$tests[]=['name'=>$name,'ok'=>$ok];if(!$ok)fwrite(STDERR,"[FAIL] $name\n");else echo "[OK] $name\n";};
$san=App\Core\PiiSanitizer::sanitize('João Silva CPF 123.456.789-10 email joao@example.com telefone 75999998888 em 19/08/2026 CEP 44000-000',['João Silva']);
$assert(!str_contains($san,'João Silva')&&!str_contains($san,'123.456.789-10')&&!str_contains($san,'joao@example.com'),'PII sanitizer mascara identificadores principais');
$payload=['S'=>'relato','O'=>'observação','A'=>'avaliação','P'=>'plano'];$enc=App\Core\Crypto::encrypt($payload);$dec=App\Core\Crypto::decrypt($enc,true);$assert($dec===$payload,'AES-256-GCM round-trip preserva conteúdo');
$hash=App\Core\Password::hash('SenhaSegura#2026');$assert(password_verify('SenhaSegura#2026',$hash),'Password hashing verifica senha correta');
$rateKey='unit-rate-'.bin2hex(random_bytes(8));App\Core\RateLimiter::clear($rateKey);$r1=App\Core\RateLimiter::hit($rateKey,2,60);$r2=App\Core\RateLimiter::hit($rateKey,2,60);$r3=App\Core\RateLimiter::hit($rateKey,2,60);$assert($r1['allowed']&&$r2['allowed']&&!$r3['allowed'],'Rate limiter bloqueia após limite configurado');App\Core\RateLimiter::clear($rateKey);
exit(count(array_filter($tests,fn($x)=>!$x['ok']))>0?1:0);
