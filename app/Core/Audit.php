<?php
declare(strict_types=1);

namespace App\Core;

final class Audit
{
    public static function record(string $action,string $entityType,?int $entityId,array $meta): void
    {
        try{
            $user=Auth::user();
            $tenantId=(int)($user['tenant_id']??($_SESSION['auth_tenant_id']??0));
            if($tenantId<1)return;
            $st=db()->prepare("SELECT event_hash FROM audit_logs WHERE tenant_id=:tenant ORDER BY id DESC LIMIT 1");
            $st->execute(['tenant'=>$tenantId]);
            $previous=(string)($st->fetchColumn()?:str_repeat('0',64));
            $payload=json_encode([
                'tenant_id'=>$tenantId,'user_id'=>$user['id']??null,'action'=>$action,'entity_type'=>$entityType,
                'entity_id'=>$entityId,'meta'=>$meta,'ip'=>client_ip(),'ua'=>substr($_SERVER['HTTP_USER_AGENT']??'',0,255),
                'ts'=>gmdate('c')
            ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $hash=hash('sha256',$previous.$payload);
            $ins=db()->prepare("INSERT INTO audit_logs(tenant_id,user_id,action,entity_type,entity_id,metadata_json,ip_address,user_agent,previous_hash,event_hash,created_at)
                VALUES(:tenant,:user,:action,:type,:entity,:meta,:ip,:ua,:previous,:hash,NOW())");
            $ins->execute([
                'tenant'=>$tenantId,'user'=>$user['id']??null,'action'=>$action,'type'=>$entityType,'entity'=>$entityId,
                'meta'=>json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'ip'=>client_ip(),
                'ua'=>substr($_SERVER['HTTP_USER_AGENT']??'',0,255),'previous'=>$previous,'hash'=>$hash
            ]);
        }catch(\Throwable $e){
            error_log('PsicoManager audit failure: '.$e->getMessage());
        }
    }
}
