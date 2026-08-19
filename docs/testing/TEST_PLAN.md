# Plano de Testes

## Unitários
- PII Sanitizer.
- AES-256-GCM round-trip.
- password hash/verify.
- Rate limiter.
- Feature flag fallback.

## Integração
Banco descartável:
- Tenant A não lê Tenant B.
- FKs compostas rejeitam relacionamento cross-tenant.
- Usuário sem permission não executa alteração.
- Portal só opera `patient_id + tenant_id` próprio.

## E2E
`tests/E2E/smoke.sh` valida home e logins. Evoluir para Playwright/Cypress se o projeto adotar Node no pipeline; atualmente o pacote evita dependência de Node.

## Segurança
- alterar `id` na URL entre tenants;
- repetir CSRF ausente/inválido;
- payload XSS em nome/descrição;
- SQL metacharacters em busca;
- sessão revogada;
- brute force;
- token público inválido.
