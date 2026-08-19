# Resposta a Incidentes

1. **Detectar:** request_id, logs de aplicação, auditoria e alertas do WAF.
2. **Conter:** suspender tenant/conta, revogar sessões, rotacionar credencial comprometida, bloquear IP/regra na borda.
3. **Preservar evidência:** copiar logs com hash, horário UTC e acesso restrito. Não ampliar coleta de prontuários.
4. **Erradicar:** corrigir falha, executar gate e testes de regressão.
5. **Recuperar:** restaurar serviço, validar banco e sessões, monitorar recorrência.
6. **Pós-incidente:** RCA, impacto, usuários/tenants afetados, ações corretivas e prazos.

Não apagar logs/auditoria envolvidos antes da avaliação do incidente e das obrigações aplicáveis.
