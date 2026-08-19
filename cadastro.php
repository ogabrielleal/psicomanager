<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
if(!is_installed())redirect('setup.php');

$planSlug=preg_replace('/[^a-z0-9_-]/','',$_POST['plan']??$_GET['plan']??'profissional')?:'profissional';
$error='';
$plans=db()->query("SELECT id,name,slug,price_monthly FROM plans WHERE active=1 ORDER BY price_monthly")->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $clinic=trim($_POST['clinic']??'');
    $name=trim($_POST['name']??'');
    $email=mb_strtolower(trim($_POST['email']??''));
    $password=(string)($_POST['password']??'');
    $crp=trim($_POST['crp']??'');
    $ascii=function_exists('iconv')?(iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$clinic)?:$clinic):$clinic;
    $slug=strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/','-',$ascii),'-'));

    if(strlen($clinic)<3||strlen($name)<3||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<10||strlen($slug)<3){
        $error='Revise os dados. Senha mínima de 10 caracteres.';
    }else{
        $ck=db()->prepare("SELECT /* SECURITY_SCOPE:GLOBAL_UNIQUE_EMAIL */ id FROM users WHERE email=:email LIMIT 1");
        $ck->execute(['email'=>$email]);
        if($ck->fetchColumn()){
            $error='Este e-mail já possui um perfil no sistema. Entre com usuário e senha ou utilize outro e-mail.';
        }else{
            $planSt=db()->prepare("SELECT * FROM plans WHERE slug=:slug AND active=1");
            $planSt->execute(['slug'=>$planSlug]);
            $plan=$planSt->fetch();
            if(!$plan){
                $error='Plano inválido.';
            }else try{
                db()->beginTransaction();
                $st=db()->prepare("INSERT INTO tenants(name,slug,email,status,created_at,updated_at)VALUES(:n,:s,:e,'active',NOW(),NOW())");
                $st->execute(['n'=>$clinic,'s'=>$slug,'e'=>$email]);
                $tenant=(int)db()->lastInsertId();

                $roleSt=db()->prepare("SELECT id FROM roles WHERE slug='clinic_admin'");
                $roleSt->execute();
                $role=(int)$roleSt->fetchColumn();

                $st=db()->prepare("INSERT INTO users(tenant_id,role_id,name,email,password_hash,professional_crp,active,created_at,updated_at)VALUES(:t,:r,:n,:e,:p,:crp,1,NOW(),NOW())");
                $st->execute(['t'=>$tenant,'r'=>$role,'n'=>$name,'e'=>$email,'p'=>\App\Core\Password::hash($password),'crp'=>$crp?:null]);

                $st=db()->prepare("INSERT INTO subscriptions(tenant_id,plan_id,status,billing_mode,current_period_start,current_period_end,created_at,updated_at)VALUES(:t,:p,'active',:mode,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 MONTH),NOW(),NOW())");
                $st->execute(['t'=>$tenant,'p'=>$plan['id'],'mode'=>env('BILLING_MODE','manual')]);
                db()->commit();
                flash('success','Conta criada. Seu perfil já está associado à clínica; entre usando somente e-mail e senha.');
                redirect('login.php');
            }catch(Throwable $e){
                if(db()->inTransaction())db()->rollBack();
                $error=str_contains($e->getMessage(),'Duplicate')?'Já existe uma clínica ou usuário com esses dados.':'Não foi possível criar o espaço.';
            }
        }
    }
}
layout('Criar conta',function()use($plans,$planSlug,$error){?>
<div class="auth-shell"><div class="auth-art"><div><span class="eyebrow">Novo espaço</span><h2>Comece com uma estrutura clínica que já nasce multiusuário.</h2></div></div><div class="auth-form-wrap"><form class="auth-form" method="post">
<a class="brand" href="<?=e(url())?>"><span class="brand-mark">P</span><span>PsicoManager <b>AI</b></span></a><h1>Criar seu espaço</h1><p>Cadastre a clínica e o primeiro gestor. Depois, o acesso será feito somente com usuário e senha.</p>
<?php if($error):?><div class="error-box"><?=e($error)?></div><?php endif?><?=csrf_field()?><div class="form-grid">
<div class="field"><label>Clínica / consultório</label><input name="clinic" required></div><div class="field"><label>Seu nome</label><input name="name" required></div>
<div class="field"><label>E-mail de acesso</label><input type="email" name="email" required autocomplete="email"></div><div class="field"><label>CRP, se você também for psicólogo responsável</label><input name="crp" placeholder="CRP 00/000000"></div>
<div class="field"><label>Plano</label><select name="plan"><?php foreach($plans as $p):?><option value="<?=e($p['slug'])?>" <?=$p['slug']===$planSlug?'selected':''?>><?=e($p['name'])?> — R$ <?=number_format((float)$p['price_monthly'],2,',','.')?></option><?php endforeach?></select></div>
<div class="field"><label>Senha (mín. 10 caracteres)</label><input type="password" name="password" minlength="10" required autocomplete="new-password"></div><button class="button full">Criar espaço</button></div></form></div></div>
<?php },['public'=>true]);
