<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Auth
{
    private static ?array $cached=null;

    /**
     * Autentica somente com usuário (e-mail) e senha.
     * O tenant é resolvido automaticamente a partir da conta encontrada.
     */
    public static function attempt(string $email,string $password): array
    {
        $email=mb_strtolower(trim($email));
        if($email==='' || $password==='')return ['ok'=>false,'error'=>'Informe usuário e senha.'];

        $st=db()->prepare("SELECT u.*,r.slug role_slug,r.name role_name,t.slug tenant_slug,t.name tenant_name
            FROM users u JOIN roles r ON r.id=u.role_id JOIN tenants t ON t.id=u.tenant_id
            WHERE u.email=:email AND u.active=1 AND t.status='active'
            ORDER BY u.id ASC");
        $st->execute(['email'=>$email]);
        $rows=$st->fetchAll(PDO::FETCH_ASSOC);

        $matches=[];
        foreach($rows as $row){
            if(password_verify($password,(string)$row['password_hash']))$matches[]=$row;
        }

        if(count($matches)!==1){
            usleep(250000);
            if(count($matches)>1){
                return ['ok'=>false,'error'=>'Este usuário está associado a mais de um perfil com a mesma senha. Solicite ao administrador a redefinição de uma das credenciais.'];
            }
            return ['ok'=>false,'error'=>'Credenciais inválidas.'];
        }

        $user=$matches[0];
        if(Password::needsRehash((string)$user['password_hash'])){
            $upd=db()->prepare("UPDATE users SET password_hash=:h WHERE id=:id AND tenant_id=:tenant");
            $upd->execute(['h'=>Password::hash($password),'id'=>$user['id'],'tenant'=>$user['tenant_id']]);
        }

        // 2FA/TOTP foi removido do fluxo de autenticação. Limpamos apenas a sessão antiga.
        unset($_SESSION['mfa_setup_required']);
        self::start($user);
        return ['ok'=>true];
    }

    private static function start(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['auth_user_id']=(int)$user['id'];
        $_SESSION['auth_tenant_id']=(int)$user['tenant_id'];
        $_SESSION['last_activity']=time();
        unset($_SESSION['locked'],$_SESSION['mfa_setup_required']);

        $token=bin2hex(random_bytes(32));
        $_SESSION['session_token']=$token;
        $st=db()->prepare("INSERT INTO user_sessions(tenant_id,user_id,token_hash,ip_address,user_agent,last_seen_at,created_at)
            VALUES(:tenant,:user,:token,:ip,:ua,NOW(),NOW())");
        $st->execute([
            'tenant'=>$user['tenant_id'],'user'=>$user['id'],'token'=>hash('sha256',$token),
            'ip'=>client_ip(),'ua'=>substr($_SERVER['HTTP_USER_AGENT']??'Unknown',0,255)
        ]);
        db()->prepare("UPDATE users SET last_login_at=NOW() WHERE id=:id AND tenant_id=:t")->execute(['id'=>$user['id'],'t'=>$user['tenant_id']]);
        self::$cached=null;
        Audit::record('auth.login','user',(int)$user['id'],[]);
    }

    public static function user(): ?array
    {
        if(self::$cached!==null)return self::$cached;
        $id=(int)($_SESSION['auth_user_id']??0);$tenant=(int)($_SESSION['auth_tenant_id']??0);
        if($id<1||$tenant<1)return null;

        $idle=(int)env('SESSION_IDLE_MINUTES',15)*60;
        $last=(int)($_SESSION['last_activity']??time());
        if(time()-$last>$idle)$_SESSION['locked']=true;
        $_SESSION['last_activity']=time();

        if(!empty($_SESSION['session_token'])){
            try{db()->prepare("UPDATE user_sessions SET last_seen_at=NOW() WHERE tenant_id=:t AND user_id=:u AND token_hash=:h AND revoked_at IS NULL")->execute(['t'=>$tenant,'u'=>$id,'h'=>hash('sha256',(string)$_SESSION['session_token'])]);}catch(\Throwable){}
            $ss=db()->prepare("SELECT revoked_at FROM user_sessions WHERE tenant_id=:t AND user_id=:u AND token_hash=:h ORDER BY id DESC LIMIT 1");
            $ss->execute(['t'=>$tenant,'u'=>$id,'h'=>hash('sha256',(string)$_SESSION['session_token'])]);
            $row=$ss->fetch();
            if($row && !empty($row['revoked_at'])){self::logout(false);return null;}
        }

        $st=db()->prepare("SELECT u.id,u.tenant_id,u.name,u.email,u.professional_crp,u.supervisor_id,
                r.slug role_slug,r.name role_name,t.name tenant_name,t.slug tenant_slug
            FROM users u JOIN roles r ON r.id=u.role_id JOIN tenants t ON t.id=u.tenant_id
            WHERE u.id=:id AND u.tenant_id=:tenant AND u.active=1 AND t.status='active' LIMIT 1");
        $st->execute(['id'=>$id,'tenant'=>$tenant]);$user=$st->fetch();
        if(!$user){self::logout(false);return null;}
        self::$cached=$user;
        return $user;
    }

    public static function requireUser(): array
    {
        $u=self::user();
        if(!$u)redirect('login.php');
        $script=$_SERVER['SCRIPT_NAME']??'';
        if(!empty($_SESSION['locked']) && basename($script)!=='lock.php') redirect('lock.php');
        return $u;
    }

    public static function logout(bool $redirectAfter=true): void
    {
        $u=self::$cached;
        if($u===null){
            $id=(int)($_SESSION['auth_user_id']??0);$tenant=(int)($_SESSION['auth_tenant_id']??0);
            if($id>0&&$tenant>0)$u=['id'=>$id,'tenant_id'=>$tenant];
        }
        if($u&&!empty($_SESSION['session_token'])){
            try{
                $st=db()->prepare("UPDATE user_sessions SET revoked_at=NOW() WHERE tenant_id=:tenant AND user_id=:user AND token_hash=:token AND revoked_at IS NULL");
                $st->execute(['tenant'=>$u['tenant_id'],'user'=>$u['id'],'token'=>hash('sha256',(string)$_SESSION['session_token'])]);
                Audit::record('auth.logout','user',(int)$u['id'],[]);
            }catch(\Throwable){}
        }
        $_SESSION=[];
        if(session_status()===PHP_SESSION_ACTIVE)session_destroy();
        self::$cached=null;
        if($redirectAfter)redirect('login.php');
    }

    public static function unlock(string $password): bool
    {
        $u=self::user();if(!$u)return false;
        $st=db()->prepare("SELECT password_hash FROM users WHERE id=:id AND tenant_id=:tenant");
        $st->execute(['id'=>$u['id'],'tenant'=>$u['tenant_id']]);$row=$st->fetch();
        if(!$row||!password_verify($password,(string)$row['password_hash']))return false;
        unset($_SESSION['locked']);$_SESSION['last_activity']=time();
        return true;
    }
}
