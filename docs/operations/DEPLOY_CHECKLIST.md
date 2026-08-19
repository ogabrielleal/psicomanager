# Checklist de Deploy Hostoo

- [ ] PHP 8.5 selecionado.
- [ ] `pdo_mysql`, `mbstring`, `openssl`, `curl`, `json` ativos.
- [ ] HTTPS válido antes de liberar usuários.
- [ ] `.env` com `APP_DEBUG=false`, `COOKIE_SECURE=true`.
- [ ] Banco com usuário exclusivo e senha forte.
- [ ] Backup realizado.
- [ ] Preflight `20260819_cross_tenant_checks.sql` retornou zero violações.
- [ ] Migration `20260819_001_devsecops_hardening.sql` aplicada em atualização v0.1.x → v0.2.0.
- [ ] `storage/` não acessível pela web.
- [ ] `setup.php` removido/renomeado após instalação nova.
- [ ] Gate local/CI em PASS.
- [ ] Smoke test pós-deploy.
- [ ] Cloudflare/WAF configurado quando domínio estiver atrás dele.
- [ ] Backup e restauração testados.
- [ ] Logs sem PII clínica.
