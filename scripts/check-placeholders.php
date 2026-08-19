<?php
declare(strict_types=1);
$root=dirname(__DIR__);$errors=[];
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
foreach($it as $f){if($f->getExtension()!=='php'||str_contains($f->getPathname(),DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR))continue;$code=file_get_contents($f->getPathname());foreach(token_get_all($code?:'') as $tok){if(!is_array($tok)||!in_array($tok[0],[T_CONSTANT_ENCAPSED_STRING,T_ENCAPSED_AND_WHITESPACE],true))continue;$str=$tok[1];if(!preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b/i',$str))continue;preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/',$str,$m);$counts=array_count_values($m[1]??[]);foreach($counts as $name=>$count){if($count>1)$errors[]=str_replace($root.'/','',$f->getPathname()).": placeholder :{$name} repetido {$count}x em SQL literal";}}}
if($errors){fwrite(STDERR,"[FAIL] Placeholders PDO duplicados:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "[OK] Nenhum placeholder PDO nomeado duplicado em SQL literal.\n";
