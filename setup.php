<?php
declare(strict_types=1);
define('APP_ROOT', __DIR__);
require_once APP_ROOT.'/app/helpers.php';
require_once APP_ROOT.'/app/Core/Env.php';
require_once APP_ROOT.'/app/Core/Password.php';
if(session_status()!==PHP_SESSION_ACTIVE){session_name('psicomanager_setup');session_start();}
$_SESSION['setup_csrf']=$_SESSION['setup_csrf']??bin2hex(random_bytes(32));

if(is_file(APP_ROOT.'/storage/installed.lock')){
    http_response_code(403);
    exit('O PsicoManager AI já está instalado. Remova setup.php após homologar a instalação.');
}
$error='';$ok=false;

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    $setupToken=(string)($_POST['_setup_csrf']??'');
    $host=trim($_POST['db_host']??'');$port=(int)($_POST['db_port']??3306);$dbName=trim($_POST['db_name']??'');
    $dbUser=trim($_POST['db_user']??'');$dbPass=(string)($_POST['db_pass']??'');$appUrl=rtrim(trim($_POST['app_url']??''),'/');
    $adminName=trim($_POST['admin_name']??'');$rawAdminEmail=trim($_POST['admin_email']??'');$adminEmail=function_exists('mb_strtolower')?mb_strtolower($rawAdminEmail):strtolower($rawAdminEmail);$adminPass=(string)($_POST['admin_pass']??'');
    $sslCa=trim($_POST['db_ssl_ca']??'');

    $runtimeIssues=[];
    if(PHP_VERSION_ID<80500)$runtimeIssues[]='PHP 8.5 ou superior';
    foreach(['pdo_mysql','mbstring','openssl','curl','json'] as $ext)if(!extension_loaded($ext))$runtimeIssues[]='extensão '.$ext;
    if(!is_writable(APP_ROOT))$runtimeIssues[]='permissão de escrita temporária na raiz para gerar .env';

    if(!hash_equals((string)$_SESSION['setup_csrf'],$setupToken))$error='Sessão de instalação expirada. Recarregue a página.';
    elseif($runtimeIssues)$error='Requisitos ausentes: '.implode(', ',$runtimeIssues).'.';
    elseif(!$host||!$dbName||!$dbUser||!$appUrl||!$adminName||!filter_var($adminEmail,FILTER_VALIDATE_EMAIL)||strlen($adminPass)<10){
        $error='Preencha todos os campos obrigatórios. A senha deve ter pelo menos 10 caracteres.';
    }elseif(!str_starts_with($appUrl,'https://')&&!str_starts_with($appUrl,'http://localhost')){
        $error='Em produção, informe uma URL HTTPS.';
    }else{
        try{
            $opts=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false];
            if($sslCa!==''&&defined('PDO::MYSQL_ATTR_SSL_CA')){$opts[PDO::MYSQL_ATTR_SSL_CA]=$sslCa;if(defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT'))$opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]=true;}
            $pdo=new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",$dbUser,$dbPass,$opts);
            $schema=file_get_contents(APP_ROOT.'/database/schema.sql');
            foreach(preg_split('/;\s*(?:\r?\n|$)/',$schema?:'')?:[] as $statement){
                $statement=trim($statement);
                if($statement!=='')$pdo->exec($statement);
            }

            $now=date('Y-m-d H:i:s');
            $plans=[
                ['Essencial','essencial','49.00',20,1,0,['Até 20 pacientes','Agenda','Finanças','Prontuário básico']],
                ['Profissional','profissional','99.00',null,1,1,['Pacientes ilimitados','Copiloto IA','Documentos CFP','RAG científico']],
                ['Clínicas / Equipes','clinicas','199.00',null,null,1,['Multiusuários','RBAC','Relatórios consolidados','Gestão de equipe']],
            ];
            $st=$pdo->prepare("INSERT INTO plans(name,slug,price_monthly,patient_limit,user_limit,ai_enabled,features_json,active,created_at,updated_at)
                VALUES(?,?,?,?,?,?,?,1,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),price_monthly=VALUES(price_monthly),updated_at=VALUES(updated_at)");
            foreach($plans as $p)$st->execute([$p[0],$p[1],$p[2],$p[3],$p[4],$p[5],json_encode($p[6],JSON_UNESCAPED_UNICODE),$now,$now]);

            $roles=['clinic_admin'=>'Gestor / Administrador da Clínica','psychologist'=>'Psicólogo(a) Titular','supervised'=>'Psicólogo Supervisionado / Estagiário','secretary'=>'Secretária / Recepcionista','finance'=>'Financeiro / Contador'];
            $st=$pdo->prepare("INSERT INTO roles(name,slug,created_at) VALUES(?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
            foreach($roles as $slug=>$name)$st->execute([$name,$slug,$now]);

            $permissions=[
                'dashboard.view'=>'Visualizar dashboard','agenda.view'=>'Visualizar agenda','agenda.manage'=>'Gerenciar agenda',
                'patients.view'=>'Visualizar pacientes','patients.manage'=>'Gerenciar pacientes','clinical.view'=>'Visualizar prontuário',
                'clinical.write'=>'Escrever prontuário','clinical.approve'=>'Aprovar prontuário supervisionado',
                'documents.view'=>'Visualizar documentos','documents.write'=>'Criar documentos','ai.use'=>'Usar copiloto IA',
                'finance.view'=>'Visualizar financeiro','finance.manage'=>'Gerenciar financeiro','finance.export'=>'Exportar fiscal',
                'team.manage'=>'Gerenciar equipe','knowledge.manage'=>'Gerenciar base científica','security.view'=>'Visualizar segurança',
                'security.manage'=>'Gerenciar sessões','settings.manage'=>'Gerenciar configurações'
            ];
            $st=$pdo->prepare("INSERT INTO permissions(name,slug,created_at) VALUES(?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
            foreach($permissions as $slug=>$name)$st->execute([$name,$slug,$now]);

            $grant=[
                'clinic_admin'=>['dashboard.view','agenda.view','agenda.manage','patients.view','patients.manage','finance.view','finance.manage','finance.export','team.manage','knowledge.manage','security.view','security.manage','settings.manage'],
                'psychologist'=>['dashboard.view','agenda.view','agenda.manage','patients.view','patients.manage','clinical.view','clinical.write','clinical.approve','documents.view','documents.write','ai.use','finance.view','finance.manage','finance.export','knowledge.manage','security.view'],
                'supervised'=>['dashboard.view','agenda.view','agenda.manage','patients.view','clinical.view','clinical.write','documents.view','documents.write','ai.use','finance.view','security.view'],
                'secretary'=>['dashboard.view','agenda.view','agenda.manage','patients.view','patients.manage','finance.view','finance.manage'],
                'finance'=>['dashboard.view','finance.view','finance.manage','finance.export']
            ];
            $pdo->exec("DELETE FROM role_permissions");
            $rp=$pdo->prepare("INSERT INTO role_permissions(role_id,permission_id) SELECT r.id,p.id FROM roles r JOIN permissions p WHERE r.slug=? AND p.slug=?");
            foreach($grant as $role=>$perms)foreach($perms as $perm)$rp->execute([$role,$perm]);

            $exists=$pdo->prepare("SELECT COUNT(*) FROM saas_admins WHERE email=?");$exists->execute([$adminEmail]);
            if(!(int)$exists->fetchColumn()){
                $pdo->prepare("INSERT INTO saas_admins(name,email,password_hash,active,created_at,updated_at) VALUES(?,?,?,1,?,?)")
                    ->execute([$adminName,$adminEmail,\App\Core\Password::hash($adminPass),$now,$now]);
            }

            $appKey='base64:'.base64_encode(random_bytes(32));
            $env=[
                'APP_NAME'=>'PsicoManager AI','APP_ENV'=>'production','APP_URL'=>$appUrl,'APP_TIMEZONE'=>'America/Bahia','APP_KEY'=>$appKey,
                'APP_DEBUG'=>'false','COOKIE_SECURE'=>'true','SESSION_IDLE_MINUTES'=>'15','TRUST_PROXY_HEADERS'=>'false','RATE_LIMIT_LOGIN_ATTEMPTS'=>'5','RATE_LIMIT_LOGIN_WINDOW'=>'300','RATE_LIMIT_AI_ATTEMPTS'=>'60','RATE_LIMIT_AI_WINDOW'=>'3600','DB_HOST'=>$host,'DB_PORT'=>(string)$port,
                'DB_DATABASE'=>$dbName,'DB_USERNAME'=>$dbUser,'DB_PASSWORD'=>$dbPass,'DB_CHARSET'=>'utf8mb4','DB_SSL_CA'=>$sslCa,
                'AI_ENABLED'=>'false','AI_PROVIDER'=>'gemini','AI_API_KEY'=>'','AI_MODEL'=>'gemini-3.6-flash','AI_TIMEOUT'=>'45',
                'WHATSAPP_WEBHOOK_URL'=>'','WHATSAPP_WEBHOOK_TOKEN'=>'','MAIL_FROM'=>'','BILLING_MODE'=>'manual'
            ];
            $lines=[];foreach($env as $k=>$v){$safeEnv=str_replace(["\\","\r","\n","\t",'"'],["\\\\","\\r","\\n","\\t",'\\"'],(string)$v);$lines[]=$k.'="'.$safeEnv.'"';}
            file_put_contents(APP_ROOT.'/.env',implode(PHP_EOL,$lines).PHP_EOL,LOCK_EX);@chmod(APP_ROOT.'/.env',0600);
            if(!is_dir(APP_ROOT.'/storage'))mkdir(APP_ROOT.'/storage',0750,true);
            file_put_contents(APP_ROOT.'/storage/installed.lock',date(DATE_ATOM).PHP_EOL,LOCK_EX);@chmod(APP_ROOT.'/storage/installed.lock',0600);
            file_put_contents(APP_ROOT.'/storage/.htaccess',"Require all denied\n",LOCK_EX);
            $ok=true;
        }catch(Throwable $e){$error='Falha na instalação: '.$e->getMessage();}
    }
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar PsicoManager AI</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<style>body{font-family:"Plus Jakarta Sans",sans-serif;background:#f5f7fb;margin:0;color:#182234}.wrap{max-width:760px;margin:50px auto;padding:20px}.card{background:#fff;border:1px solid #e4e9f0;border-radius:24px;padding:32px;box-shadow:0 20px 60px rgba(20,40,80,.08)}h1{letter-spacing:-.04em}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{display:grid;gap:6px;margin-bottom:14px}.field.full{grid-column:1/-1}label{font-size:12px;font-weight:700}input{padding:12px;border:1px solid #d8e0ea;border-radius:12px;font:inherit}button{border:0;border-radius:13px;padding:13px 18px;background:#0b57d0;color:#fff;font-weight:700}.error{background:#fff0ef;color:#a61b1b;padding:12px;border-radius:12px}.ok{background:#eaf8f2;color:#087750;padding:18px;border-radius:14px}@media(max-width:650px){.grid{grid-template-columns:1fr}.wrap{margin:15px auto}.card{padding:22px}}</style></head><body><div class="wrap"><div class="card">
<h1>Instalação do PsicoManager AI</h1><p>Compatível com PHP 8.5 e MySQL remoto. O instalador cria o esquema, planos, RBAC, chave criptográfica e administrador SaaS.</p>
<?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif?>
<?php if($ok):?><div class="ok"><strong>Instalação concluída.</strong><br>Acesse <code>/saas/login.php</code>. Por segurança, remova ou renomeie <code>setup.php</code> após validar.</div>
<?php else:?><form method="post"><input type="hidden" name="_setup_csrf" value="<?=htmlspecialchars((string)$_SESSION['setup_csrf'],ENT_QUOTES)?>"><div class="grid">
<div class="field"><label>Host MySQL</label><input name="db_host" required placeholder="mysql.seuhost.com"></div>
<div class="field"><label>Porta</label><input name="db_port" value="3306" required></div>
<div class="field"><label>Banco</label><input name="db_name" required></div>
<div class="field"><label>Usuário</label><input name="db_user" required></div>
<div class="field full"><label>Senha do MySQL</label><input type="password" name="db_pass"></div>
<div class="field full"><label>CA SSL do MySQL (opcional, caminho absoluto)</label><input name="db_ssl_ca" placeholder="/home/usuario/certs/ca.pem"></div>
<div class="field full"><label>URL do sistema (HTTPS)</label><input type="url" name="app_url" required placeholder="https://psicomanager.seudominio.com.br"></div>
<div class="field"><label>Administrador SaaS</label><input name="admin_name" required></div>
<div class="field"><label>E-mail</label><input type="email" name="admin_email" required></div>
<div class="field full"><label>Senha SaaS (mínimo 10 caracteres)</label><input type="password" name="admin_pass" minlength="10" required></div>
</div><button>Instalar sistema</button></form><?php endif?>
</div></div></body></html>