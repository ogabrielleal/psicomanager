<?php
declare(strict_types=1);
namespace App\Core;
final class Tenant{
 public static function currentSubscription(int $tenantId): ?array{
  $st=db()->prepare("SELECT s.id,s.tenant_id,s.plan_id,s.status,s.billing_mode,s.current_period_start,s.current_period_end,s.created_at,s.updated_at,p.name plan_name,p.slug plan_slug,p.price_monthly,p.patient_limit,p.user_limit,p.ai_enabled,p.features_json FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.tenant_id=:tenant ORDER BY s.id DESC LIMIT 1");$st->execute(['tenant'=>$tenantId]);return $st->fetch()?:null;
 }
 public static function enforceActive(int $tenantId): void{$sub=self::currentSubscription($tenantId);if(!$sub||in_array($sub['status'],['suspended','cancelled'],true)){http_response_code(402);require APP_ROOT.'/app/views/subscription_blocked.php';exit;}}
 public static function canAddPatient(int $tenantId): bool{$sub=self::currentSubscription($tenantId);if(!$sub)return false;$limit=$sub['patient_limit'];if($limit===null)return true;$st=db()->prepare("SELECT COUNT(*) FROM patients WHERE tenant_id=:t AND status='active'");$st->execute(['t'=>$tenantId]);return (int)$st->fetchColumn()<(int)$limit;}
 public static function canAddUser(int $tenantId): bool{$sub=self::currentSubscription($tenantId);if(!$sub)return false;$limit=$sub['user_limit'];if($limit===null)return true;$st=db()->prepare("SELECT COUNT(*) FROM users WHERE tenant_id=:t AND active=1");$st->execute(['t'=>$tenantId]);return (int)$st->fetchColumn()<(int)$limit;}
 public static function aiEnabled(int $tenantId): bool{return FeatureFlags::enabled('ai',$tenantId);}
 public static function featureEnabled(int $tenantId,string $feature): bool{return FeatureFlags::enabled($feature,$tenantId);}
}
