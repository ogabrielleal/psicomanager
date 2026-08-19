<?php
declare(strict_types=1);

namespace App\Core;

final class TenantGuard
{
    public static function tenantId(): int
    {
        $id = (int)($_SESSION['auth_tenant_id'] ?? 0);
        if ($id < 1) throw new \RuntimeException('Tenant não resolvido na sessão autenticada.');
        return $id;
    }

    public static function assertRow(array $row, int $tenantId): void
    {
        if ((int)($row['tenant_id'] ?? 0) !== $tenantId) {
            Audit::record('security.cross_tenant_denied', 'security', null, ['row_tenant_id'=>(int)($row['tenant_id'] ?? 0)]);
            http_response_code(404);
            exit('Recurso não localizado.');
        }
    }
}
