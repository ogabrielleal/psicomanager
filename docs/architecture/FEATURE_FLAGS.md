# Feature Flags

Tabela `tenant_features`:
- `tenant_id`
- `feature_key`
- `enabled`
- `updated_by`
- `updated_at`

Precedência:
1. override explícito do tenant;
2. capacidades do plano;
3. default seguro `false` para recurso desconhecido.

Uso:
```php
if (!feature_enabled('ai')) {
    http_response_code(403);
    exit;
}
```

Chaves iniciais: `agenda`, `patients`, `clinical`, `documents`, `finance`, `portal`, `ai`, `rag`, `team`.
