# 12 Pilares DevSecOps — implementação v0.2

| Pilar | Implementação no ZIP | Estado |
|---|---|---|
| 1. PRD | `docs/architecture/PRD.md` | Implementado |
| 2. UML/Fluxos | Mermaid + DATA_FLOW | Implementado |
| 3. RBAC | `Rbac.php`, `RBAC_MATRIX.md`, gate de rotas | Implementado |
| 4. Multi-tenancy | tenant_id + allowlist + scanner | Implementado/reforçado |
| 5. RLS/DB defense | FKs compostas same-tenant; limitação MySQL documentada | Implementado como defesa equivalente, não RLS nativo |
| 6. Secrets | `.env`, EnvValidator, scanner de segredos | Implementado |
| 7. Modular/Flags | FeatureFlags + `tenant_features` | Implementado inicial |
| 8. Error reporting | request_id + logs JSON + client beacon | Implementado; screenshot automática deliberadamente desativada por privacidade |
| 9. Testes | unit/integration/E2E skeleton + runner | Implementado inicial |
| 10. Security gate | `scripts/security-audit.sh` + CI | Implementado |
| 11. WAF/rate limit | RateLimiter local + guia Cloudflare | Implementado local; borda depende da conta DNS/WAF |
| 12. TLS/headers | redirect HTTPS, HSTS, CSP, headers | Implementado no app/Apache |
