<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
$u=current_user();if(!$u)redirect('login.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();if(\App\Core\Auth::unlock((string)($_POST['password']??'')))redirect('dashboard/index.php');$error='Senha inválida.';}
layout('Sessão bloqueada',function()use($u,$error){?><div class="auth-form-wrap" style="min-height:100vh"><form method="post" class="auth-form card">
<a class="brand"><span class="brand-mark">P</span><span>PsicoManager <b>AI</b></span></a><h1>Desbloquear</h1><p>Olá, <?=e($u['name'])?>. Digite sua senha para continuar.</p>
<?php if($error):?><div class="error-box"><?=e($error)?></div><?php endif?><?=csrf_field()?><div class="form-grid"><div class="field"><label>Senha</label><input type="password" name="password" required autofocus autocomplete="current-password"></div><button class="button">Desbloquear</button><a class="button ghost" href="<?=e(url('logout.php'))?>">Sair</a></div>
</form></div><?php },['public'=>true]);
