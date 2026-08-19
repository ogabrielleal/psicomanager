<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require __DIR__.'/_auth.php';
if(portal_user())redirect('portal/index.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $email=mb_strtolower(trim($_POST['email']??''));
    $pass=(string)($_POST['password']??'');
    $rateKey='portal-login:'.client_ip().':'.$email;
    $limit=\App\Core\RateLimiter::hit($rateKey,(int)env('RATE_LIMIT_LOGIN_ATTEMPTS',5),(int)env('RATE_LIMIT_LOGIN_WINDOW',300));
    if(!$limit['allowed']){
        $error='Muitas tentativas de acesso. Aguarde alguns minutos e tente novamente.';
    }else{
        $st=db()->prepare("SELECT pu.*,t.id resolved_tenant_id,t.status tenant_status FROM patient_portal_users pu JOIN tenants t ON t.id=pu.tenant_id WHERE pu.email=:e AND pu.active=1 AND t.status='active' ORDER BY pu.id");
        $st->execute(['e'=>$email]);
        $matches=[];
        foreach($st->fetchAll() as $r){if(password_verify($pass,(string)$r['password_hash']))$matches[]=$r;}
        if(count($matches)===1){
            $r=$matches[0];
            session_regenerate_id(true);
            $_SESSION['portal_patient_id']=$r['patient_id'];
            $_SESSION['portal_tenant_id']=$r['resolved_tenant_id'];
            db()->prepare("UPDATE patient_portal_users SET last_login_at=NOW() WHERE id=:id AND tenant_id=:tenant")->execute(['id'=>$r['id'],'tenant'=>$r['resolved_tenant_id']]);
            \App\Core\RateLimiter::clear($rateKey);
            redirect('portal/index.php');
        }
        $error=count($matches)>1?'Este usuário está duplicado em mais de um perfil com a mesma senha. Solicite a redefinição de uma das credenciais.':'Credenciais inválidas.';
    }
}
layout('Portal do paciente',function()use($error){?><div class="auth-shell"><div class="auth-art"><div><span class="eyebrow">Seu espaço</span><h2>Consultas, tarefas e registros entre sessões em um só lugar.</h2></div></div><div class="auth-form-wrap"><form method="post" class="auth-form"><a class="brand" href="<?=e(url())?>"><span class="brand-mark">P</span><span>PsicoManager <b>AI</b></span></a><h1>Portal do paciente</h1><p>Seu perfil já identifica automaticamente a clínica. Entre apenas com usuário e senha.</p><?php if($error):?><div class="error-box"><?=e($error)?></div><?php endif?><?=csrf_field()?><div class="form-grid"><div class="field"><label>Usuário</label><input type="email" name="email" required autocomplete="username" placeholder="seuemail@exemplo.com"></div><div class="field"><label>Senha</label><input type="password" name="password" required autocomplete="current-password"></div><button class="button">Entrar</button></div></form></div></div><?php },['public'=>true]);
