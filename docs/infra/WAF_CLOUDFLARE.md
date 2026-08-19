# WAF, Bots e Rate Limiting

## Borda recomendada
Colocar o domínio atrás de Cloudflare ou WAF equivalente.

## Regras sugeridas
1. `/login.php`, `/portal/login.php`, `/saas/login.php`: desafio/bloqueio após volume anormal.
2. `/setup.php`: bloquear após instalação ou restringir por IP temporariamente.
3. `/cron/*`, `/app/*`, `/database/*`, `/storage/*`, `/docs/*`, `/tests/*`, `/scripts/*`: negar na borda além do Apache.
4. Bot management para tráfego automatizado agressivo.
5. DDoS managed rules.

## Rate limits iniciais
- Login: 5 tentativas / 5 min por IP+usuário no app.
- IA: limitar por tenant/plano; adicionar rate limit de borda se endpoint API público surgir.
- Formulários públicos: 20/min/IP como ponto inicial, ajustado após métricas.

## Proxy IP
`TRUST_PROXY_HEADERS=false` por padrão. Só ativar quando a origem estiver realmente protegida contra acesso direto e o proxy confiável controlar os headers.
