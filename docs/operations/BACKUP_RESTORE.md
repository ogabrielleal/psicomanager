# Backup e Restore

## Política mínima
- Banco: backup diário criptografado + retenção rotativa.
- Uploads/documentos: backup diário se houver arquivos persistentes.
- `.env`: cópia segura separada, nunca no diretório público.
- Teste de restauração: mensal em ambiente não produtivo.

## RPO/RTO inicial sugerido
- RPO: 24h no mínimo; ideal 1–4h quando houver volume clínico relevante.
- RTO: 4–8h no estágio inicial.

## Restore
1. Isolar ambiente.
2. Restaurar banco.
3. Restaurar APP_KEY correspondente ao snapshot.
4. Executar `scripts/security-audit.sh` no código.
5. Executar smoke test.
6. Validar amostras cifradas e auditoria.
