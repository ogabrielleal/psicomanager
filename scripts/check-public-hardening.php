<?php
declare(strict_types=1);
$root=dirname(__DIR__);$ht=file_get_contents($root.'/.htaccess')?:'';$required=['app|config|database|storage|docs|cron|tests|scripts','(^\\.env|\\.sql$|\\.log$|\\.lock$|\\.sh$|\\.ya?ml$)'];$missing=[];foreach($required as $token)if(!str_contains($ht,$token))$missing[]=$token;if(!is_file($root.'/storage/.htaccess'))$missing[]='storage/.htaccess';if($missing){fwrite(STDERR,'[FAIL] Hardening público ausente: '.implode(', ',$missing).PHP_EOL);exit(1);}echo "[OK] Diretórios/arquivos sensíveis possuem bloqueio Apache esperado.\n";
