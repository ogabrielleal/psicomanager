<?php
declare(strict_types=1);

namespace App\Core;

final class Rbac
{
    private static array $cache=[];

    public static function allows(int $userId,string $permission): bool
    {
        if(isset(self::$cache[$userId][$permission]))return self::$cache[$userId][$permission];
        $u=Auth::user();
        if(!$u||(int)$u['id']!==$userId)return false;
        // O gestor administrativo não recebe sigilo clínico por padrão. Se também for psicólogo (CRP informado),
        // pode usar recursos clínicos, mas canViewPatientClinical() ainda restringe aos pacientes sob sua responsabilidade/supervisão.
        if(($u['role_slug']??'')==='clinic_admin' && !empty($u['professional_crp'])
            && ($permission==='ai.use' || str_starts_with($permission,'clinical.') || str_starts_with($permission,'documents.'))){
            return self::$cache[$userId][$permission]=true;
        }
        $st=db()->prepare("SELECT COUNT(*) FROM users u
            JOIN role_permissions rp ON rp.role_id=u.role_id
            JOIN permissions p ON p.id=rp.permission_id
            WHERE u.id=:uid AND u.tenant_id=:tenant AND p.slug=:permission");
        $st->execute(['uid'=>$userId,'tenant'=>$u['tenant_id'],'permission'=>$permission]);
        return self::$cache[$userId][$permission]=((int)$st->fetchColumn()>0);
    }

    public static function canViewPatientClinical(array $user,array $patient): bool
    {
        $role=$user['role_slug'];
        if(!in_array($role,['psychologist','supervised','clinic_admin'],true))return false;
        if($role==='clinic_admin'&&empty($user['professional_crp']))return false;
        if((int)$patient['primary_professional_id']===(int)$user['id'])return true;
        if($role==='psychologist'||($role==='clinic_admin'&&!empty($user['professional_crp']))){
            $st=db()->prepare("SELECT COUNT(*) FROM users
                WHERE id=:professional AND tenant_id=:tenant AND supervisor_id=:supervisor AND active=1");
            $st->execute(['professional'=>$patient['primary_professional_id'],'tenant'=>$user['tenant_id'],'supervisor'=>$user['id']]);
            return (int)$st->fetchColumn()>0;
        }
        return false;
    }
}
