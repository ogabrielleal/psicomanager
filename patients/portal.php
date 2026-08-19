<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$user=require_permission('patients.manage');
\App\Core\Tenant::enforceActive((int)$user['tenant_id']);
$id=(int)($_GET['id']??$_POST['id']??0);
$st=db()->prepare("SELECT * FROM patients WHERE id=:id AND tenant_id=:t");
$st->execute(['id'=>$id,'t'=>$user['tenant_id']]);
$p=$st->fetch();
if(!$p){http_response_code(404);exit('Paciente não encontrado.');}
$credentials=null;
$st=db()->prepare("SELECT id,email,active FROM patient_portal_users WHERE tenant_id=:t AND patient_id=:p");
$st->execute(['t'=>$user['tenant_id'],'p'=>$id]);
$portal=$st->fetch();

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $email=mb_strtolower(trim($_POST['email']??$p['email']??''));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        flash('error','Informe um e-mail válido.');
        redirect('patients/portal.php?id='.$id);
    }
    $dup=db()->prepare("SELECT /* SECURITY_SCOPE:GLOBAL_UNIQUE_EMAIL */ id FROM patient_portal_users WHERE email=:email AND id<>:id LIMIT 1");
    $dup->execute(['email'=>$email,'id'=>(int)($portal['id']??0)]);
    if($dup->fetchColumn()){
        flash('error','Este e-mail já está associado a outro acesso do Portal do Paciente.');
        redirect('patients/portal.php?id='.$id);
    }
    $password=bin2hex(random_bytes(5)).'A!';
    if($portal){
        db()->prepare("UPDATE patient_portal_users SET email=:e,password_hash=:h,active=1,updated_at=NOW() WHERE id=:id AND tenant_id=:t")->execute(['e'=>$email,'h'=>\App\Core\Password::hash($password),'id'=>$portal['id'],'t'=>$user['tenant_id']]);
    }else{
        db()->prepare("INSERT INTO patient_portal_users(tenant_id,patient_id,email,password_hash,active,created_at,updated_at)VALUES(:t,:p,:e,:h,1,NOW(),NOW())")->execute(['t'=>$user['tenant_id'],'p'=>$id,'e'=>$email,'h'=>\App\Core\Password::hash($password)]);
    }
    audit('portal.credentials_reset','patient',$id,[]);
    $credentials=['email'=>$email,'password'=>$password];
    $portal=['email'=>$email,'active'=>1];
}
layout('Portal do paciente',function()use($p,$portal,$credentials){?><div class="page-head"><div><span class="eyebrow">Portal do paciente</span><h1 data-private><?=e($p['name'])?></h1><p>Crie ou redefina as credenciais. O acesso já ficará associado automaticamente a este paciente e à clínica.</p></div></div><div class="grid-2"><form method="post" class="card form-grid"><?=csrf_field()?><input type="hidden" name="id" value="<?=$p['id']?>"><div class="field"><label>Usuário (e-mail)</label><input type="email" name="email" value="<?=e($portal['email']??$p['email']??'')?>" required></div><button class="button"><?= $portal?'Redefinir senha':'Criar acesso'?></button></form><div class="card"><h2>Acesso</h2><p><code><?=e(url('portal/login.php'))?></code></p><p class="muted">O paciente não precisa informar nome ou identificador da clínica.</p><?php if($credentials):?><div class="flash success" style="margin:0"><strong>Credenciais temporárias</strong><br>Usuário: <?=e($credentials['email'])?><br>Senha: <code><?=e($credentials['password'])?></code></div><?php else:?><p class="muted">Nenhuma senha é recuperável. Gere uma nova quando necessário.</p><?php endif?></div></div><?php },['active'=>'patients']);
