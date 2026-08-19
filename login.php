<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
if(!is_installed())redirect('setup.php');
if(current_user())redirect('dashboard/index.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $email=mb_strtolower(trim($_POST['email']??''));
    $rateKey='login:'.client_ip().':'.$email;
    $limit=\App\Core\RateLimiter::hit($rateKey,(int)env('RATE_LIMIT_LOGIN_ATTEMPTS',5),(int)env('RATE_LIMIT_LOGIN_WINDOW',300));
    if(!$limit['allowed']){
        $error='Muitas tentativas de acesso. Aguarde alguns minutos e tente novamente.';
        audit('security.login_rate_limited','auth',null,['request_id'=>request_id()]);
    }else{
        $res=\App\Core\Auth::attempt($email,(string)($_POST['password']??''));
        if($res['ok']??false){\App\Core\RateLimiter::clear($rateKey);redirect('dashboard/index.php');}
        $error=$res['error']??'Não foi possível entrar.';
    }
}
layout('Entrar',function()use($error){?>
<div class="auth-shell"><div class="auth-art"><div><span class="eyebrow">Ambiente clínico protegido</span><h2>Uma rotina mais organizada, sem abrir mão do sigilo.</h2></div></div><div class="auth-form-wrap"><form class="auth-form" method="post">
<a class="brand" href="<?=e(url())?>"><span class="brand-mark">P</span><span>PsicoManager <b>AI</b></span></a><h1>Entrar</h1><p>Seu perfil já está associado à clínica. Informe apenas seu usuário e senha.</p>
<?php if($error):?><div class="error-box"><?=e($error)?></div><?php endif?><?=csrf_field()?><div class="form-grid">
<div class="field"><label>Usuário</label><input type="email" name="email" required autocomplete="username" placeholder="seuemail@exemplo.com"></div>
<div class="field"><label>Senha</label><input type="password" name="password" required autocomplete="current-password"></div>
<button class="button full">Entrar</button></div><p class="help">Ainda não possui conta? <a href="<?=e(url('cadastro.php'))?>" style="color:#0b57d0;font-weight:700">Criar conta</a>.</p>
</form></div></div><?php },['public'=>true]);
