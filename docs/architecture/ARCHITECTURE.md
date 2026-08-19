# Arquitetura do Sistema

## Estilo
Aplicação modular em PHP 8.5, renderização server-side, JavaScript progressivo, PDO/MySQL remoto e implantação Apache/Hostoo. O código mantém baixo acoplamento de infraestrutura para permitir futura extração de módulos ou API.

## Camadas
```text
HTTP/Browser
   ↓
Apache + TLS/WAF
   ↓
Bootstrap / SecurityHeaders / RequestContext / ErrorHandler
   ↓
Auth / RBAC / Tenant / FeatureFlags / CSRF / RateLimiter
   ↓
Módulos: Agenda | Patients | Clinical | Documents | Finance | AI | Portal | Team
   ↓
PDO prepared statements
   ↓
MySQL remoto
```

## Componentes transversais
- `Auth`: sessão e credenciais.
- `Rbac`: autorização granular.
- `Tenant`: assinatura/limites do tenant.
- `FeatureFlags`: recurso por plano + override opcional por tenant.
- `Crypto`: conteúdo clínico em AES-256-GCM.
- `PiiSanitizer`: redução de PII antes de IA externa.
- `Audit`: trilha encadeada por hash.
- `RateLimiter`: proteção local por IP+identificador.
- `RequestContext`: request ID por requisição.
- `ErrorHandler`: log estruturado e erro 500 sem stack trace em produção.

## Decisão de multi-tenancy
Shared database/shared schema com `tenant_id`. O isolamento é validado em aplicação e reforçado com FKs compostas de tenant em tabelas relacionadas.

## Limite do MySQL
O MySQL não oferece RLS nativo equivalente a `CREATE POLICY` do PostgreSQL. O projeto chama sua abordagem de **defesa multi-tenant em profundidade**, não de RLS nativo.
