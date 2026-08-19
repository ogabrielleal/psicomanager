<?php
declare(strict_types=1);
$root=dirname(__DIR__);$dirs=['agenda','patients','clinical','documents','ai','finance','knowledge','team','security','billing','settings'];$errors=[];
foreach($dirs as $dir){foreach(glob($root.'/'.$dir.'/*.php')?:[] as $file){$code=file_get_contents($file)?:'';$rel=$dir.'/'.basename($file);if(!preg_match('/require_permission\(|require_auth\(|Auth::requireUser\(/',$code))$errors[]=$rel;}}
if($errors){fwrite(STDERR,"[FAIL] Rotas sensíveis sem gate de autenticação/permissão explícito:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "[OK] Rotas sensíveis possuem gate explícito.\n";
