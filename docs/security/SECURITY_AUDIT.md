# Security Audit / Gate de Deploy

## Gates bloqueantes
1. PHP lint.
2. Placeholder PDO nomeado duplicado.
3. Query tenant-owned sem escopo ou justificativa.
4. Segredo conhecido no código.
5. Rota sensível sem autenticação/permissão explícita.
6. Headers essenciais/CSP.
7. Testes unitários de segurança.

Executar:
```bash
bash scripts/security-audit.sh
```

## Severidade
- CRITICAL: vazamento cross-tenant, segredo, bypass auth, SQL injection. Bloqueia release.
- HIGH: sessão, XSS, CSRF, dados clínicos sem cifra. Bloqueia release.
- MEDIUM: hardening, rate limit parcial, observabilidade incompleta. Deve ter plano antes de release.
- LOW/INFO: qualidade e recomendações.

## Limitações de análise estática
Os scripts são gates defensivos, não substituem pentest, DAST ou revisão manual. SQL montado dinamicamente precisa de revisão adicional.
