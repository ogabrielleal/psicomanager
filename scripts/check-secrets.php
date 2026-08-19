<?php
declare(strict_types=1);
$root=dirname(__DIR__);$errors=[];$patterns=[
 '/AIza[0-9A-Za-z_\-]{25,}/'=>'Google API key',
 '/\bsk-[A-Za-z0-9]{20,}\b/'=>'API secret',
 '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/'=>'Private key',
 '/DB_PASSWORD\s*=\s*["\']?[^"\'\s][^\r\n]*/'=>'DB password literal'
];
$targets=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));foreach($it as $f){$rel=str_replace('\\','/',str_replace($root.DIRECTORY_SEPARATOR,'',$f->getPathname()));if(str_starts_with($rel,'docs/')||str_starts_with($rel,'tests/')||str_starts_with($rel,'scripts/'))continue;if(!in_array($f->getExtension(),['php','js','json','env'],true)&&$f->getFilename()!=='.env.example')continue;$targets[]=$f->getPathname();}
foreach($targets as $file){$content=file_get_contents($file)?:'';foreach($patterns as $re=>$label){if(preg_match($re,$content)){if(str_ends_with($file,'.env.example')&&str_contains($label,'DB password'))continue;$errors[]=str_replace($root.'/','',$file).": $label";}}}
if($errors){fwrite(STDERR,"[FAIL] Possíveis segredos no código:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "[OK] Nenhum segredo conhecido detectado no código executável.\n";
