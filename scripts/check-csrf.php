<?php
declare(strict_types=1);
$root=dirname(__DIR__);$allow=['setup.php'=>'CSRF próprio _setup_csrf','errors/client.php'=>'endpoint append-only de telemetria, sem mutação de negócio e com rate limit'];$errors=[];
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
foreach($it as $f){if($f->getExtension()!=='php')continue;$rel=str_replace('\\','/',str_replace($root.DIRECTORY_SEPARATOR,'',$f->getPathname()));if(str_starts_with($rel,'scripts/')||str_starts_with($rel,'tests/')||isset($allow[$rel]))continue;$code=file_get_contents($f->getPathname())?:'';if(!str_contains($code,'REQUEST_METHOD')||!str_contains($code,"POST"))continue;if(!str_contains($code,'verify_csrf()'))$errors[]=$rel;}
if($errors){fwrite(STDERR,"[FAIL] Rotas POST sem verificação CSRF explícita:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "[OK] Rotas POST mutáveis possuem CSRF explícito.\n";
