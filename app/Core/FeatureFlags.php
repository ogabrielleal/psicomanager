<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

final class FeatureFlags
{
    private static array $cache = [];

    public static function enabled(string $key, int $tenantId): bool
    {
        $cacheKey = $tenantId . ':' . $key;
        if (array_key_exists($cacheKey, self::$cache)) return self::$cache[$cacheKey];

        try {
            $st = db()->prepare('SELECT enabled FROM tenant_features WHERE tenant_id=:tenant AND feature_key=:feature LIMIT 1');
            $st->execute(['tenant'=>$tenantId,'feature'=>$key]);
            $value = $st->fetchColumn();
            if ($value !== false) return self::$cache[$cacheKey] = (bool)$value;
        } catch (Throwable) {
            // Compatibilidade com instalações anteriores à migration 20260819_001.
        }

        $sub = Tenant::currentSubscription($tenantId);
        if (!$sub || $sub['status'] !== 'active') return self::$cache[$cacheKey] = false;
        if ($key === 'ai') return self::$cache[$cacheKey] = !empty($sub['ai_enabled']);

        $defaults = [
            'agenda'=>true,'patients'=>true,'clinical'=>true,'finance'=>true,'portal'=>true,
            'documents'=>in_array($sub['plan_slug'], ['profissional','clinicas'], true),
            'rag'=>in_array($sub['plan_slug'], ['profissional','clinicas'], true),
            'team'=>$sub['plan_slug']==='clinicas',
        ];
        return self::$cache[$cacheKey] = (bool)($defaults[$key] ?? false);
    }
}
