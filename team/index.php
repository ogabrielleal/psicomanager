<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$user=require_permission('team.manage');
\App\Core\Tenant::enforceActive((int)$user['tenant_id']);
$t=(int)$user['tenant_id'];
$sub=\App\Core\Tenant::currentSubscription($t);

$loadMembers=function()use($t):array{
    $st=db()->prepare("SELECT u.id,u.name,u.email,u.professional_crp,u.active,u.last_login_at,r.slug role_slug,r.name role_name,s.name supervisor_name FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN users s ON s.id=u.supervisor_id AND s.tenant_id=u.tenant_id WHERE u.tenant_id=:t ORDER BY u.active DESC,u.name");
    $st->execute(['t'=>$t]);
    return $st->fetchAll();
};
$members=$loadMembers();
$roles=db()->query("SELECT id,slug,name FROM roles ORDER BY id")->fetchAll();
$st=db()->prepare("SELECT u.id,u.name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.tenant_id=:t AND u.active=1 AND (r.slug='psychologist' OR (r.slug='clinic_admin' AND u.professional_crp IS NOT NULL AND u.professional_crp<>'')) ORDER BY u.name");
$st->execute(['t'=>$t]);
$supervisors=$st->fetchAll();
$temporary=null;$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=$_POST['action']??'create';
    if($action==='toggle'){
        $id=(int)($_POST['id']??0);
        if($id!==$user['id']){
            $ck=db()->prepare("SELECT active FROM users WHERE id=:id AND tenant_id=:t LIMIT 1");
            $ck->execute(['id'=>$id,'t'=>$t]);
            $current=$ck->fetchColumn();
            if($current!==false){
                if((int)$current===0&&!\App\Core\Tenant::canAddUser($t)){
                    flash('error','O limite de usuários ativos do plano foi atingido.');
                    redirect('team/index.php');
                }
                db()->prepare("UPDATE users SET active=IF(active=1,0,1),updated_at=NOW() WHERE id=:id AND tenant_id=:t")->execute(['id'=>$id,'t'=>$t]);
                audit('team.toggle','user',$id,[]);
            }
        }
        redirect('team/index.php');
    }

    $name=trim($_POST['name']??'');
    $email=mb_strtolower(trim($_POST['email']??''));
    $roleId=(int)($_POST['role_id']??0);
    $role=null;
    foreach($roles as $r)if((int)$r['id']===$roleId)$role=$r;

    if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$role){
        $error='Revise nome, e-mail e perfil.';
    }elseif(!\App\Core\Tenant::canAddUser($t)){
        $error='O limite de usuários do plano atual foi atingido.';
    }else{
        $dup=db()->prepare("SELECT /* SECURITY_SCOPE:GLOBAL_UNIQUE_EMAIL */ id FROM users WHERE email=:email LIMIT 1");
        $dup->execute(['email'=>$email]);
        if($dup->fetchColumn()){
            $error='Este e-mail já está associado a um perfil do sistema.';
        }else try{
            $password=bin2hex(random_bytes(5)).'A!';
            $sup=$role['slug']==='supervised'?(int)($_POST['supervisor_id']??0):null;
            if($role['slug']==='supervised'){
                if($sup<1||!in_array($sup,array_map(fn($x)=>(int)$x['id'],$supervisors),true))throw new RuntimeException('Escolha um supervisor válido desta clínica.');
            }
            $st=db()->prepare("INSERT INTO users(tenant_id,role_id,supervisor_id,name,email,password_hash,professional_crp,phone,active,created_at,updated_at)VALUES(:t,:r,:s,:n,:e,:p,:crp,:ph,1,NOW(),NOW())");
            $st->execute(['t'=>$t,'r'=>$roleId,'s'=>$sup?:null,'n'=>$name,'e'=>$email,'p'=>\App\Core\Password::hash($password),'crp'=>trim($_POST['crp']??'')?:null,'ph'=>trim($_POST['phone']??'')?:null]);
            $id=(int)db()->lastInsertId();
            audit('team.create','user',$id,['role'=>$role['slug']]);
            $temporary=['email'=>$email,'password'=>$password];
            $members=$loadMembers();
        }catch(Throwable $e){
            $error=str_contains($e->getMessage(),'Duplicate')?'Este e-mail já está associado a um perfil do sistema.':$e->getMessage();
        }
    }
}
layout('Equipe',function()use($members,$roles,$supervisors,$temporary,$error,$sub,$user){?><div class="page-head"><div><span class="eyebrow">Equipe e RBAC</span><h1>Membros</h1><p>Plano: <?=e($sub['plan_name']??'—')?> · limite: <?=e($sub['user_limit']??'sem limite')?></p></div></div><?php if($temporary):?><div class="flash success"><strong>Credencial temporária — copie agora:</strong> <?=e($temporary['email'])?> · <code><?=e($temporary['password'])?></code></div><?php endif?><div class="grid-2"><section class="card"><h2>Equipe atual</h2><div class="table-wrap"><table class="table"><thead><tr><th>Nome</th><th>Perfil</th><th>CRP</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($members as $m):?><tr><td><?=e($m['name'])?><br><small class="muted"><?=e($m['email'])?></small></td><td><?=e($m['role_name'])?><?php if($m['supervisor_name']):?><br><small>Sup.: <?=e($m['supervisor_name'])?></small><?php endif?></td><td><?=e($m['professional_crp']?:'—')?></td><td><span class="status <?=$m['active']?'active':'suspended'?>"><?=$m['active']?'ativo':'inativo'?></span></td><td><?php if((int)$m['id']!==(int)$user['id']):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$m['id']?>"><button class="button ghost small">Alternar</button></form><?php endif?></td></tr><?php endforeach?></tbody></table></div></section><form method="post" class="card form-grid"><?=csrf_field()?><h2>Novo membro</h2><?php if($error):?><div class="error-box"><?=e($error)?></div><?php endif?><div class="field"><label>Nome</label><input name="name" required></div><div class="field"><label>E-mail / usuário</label><input type="email" name="email" required></div><div class="form-row"><div class="field"><label>Perfil</label><select name="role_id" required><?php foreach($roles as $r):?><option value="<?=$r['id']?>"><?=e($r['name'])?></option><?php endforeach?></select></div><div class="field"><label>CRP (se aplicável)</label><input name="crp"></div></div><div class="field"><label>Supervisor (para supervisionado)</label><select name="supervisor_id"><option value="">Selecione</option><?php foreach($supervisors as $s):?><option value="<?=$s['id']?>"><?=e($s['name'])?></option><?php endforeach?></select></div><div class="field"><label>Telefone</label><input name="phone"></div><button class="button">Criar usuário</button><p class="help">O perfil já fica vinculado automaticamente à clínica atual. No acesso, o membro informa somente usuário e senha.</p></form></div><?php },['active'=>'team']);
