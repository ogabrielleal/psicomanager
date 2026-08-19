# Threat Model

## Ativos críticos
1. Prontuários e anamneses.
2. Documentos psicológicos.
3. Identidade e contatos de pacientes.
4. Chave APP_KEY.
5. Credenciais MySQL e IA.
6. Sessões autenticadas.
7. Trilha de auditoria.

## Ameaças prioritárias
| Ameaça | Impacto | Mitigação atual | Próximo passo |
|---|---|---|---|
| IDOR cross-tenant | Crítico | tenant_id + RBAC + FKs compostas | testes de integração em DB descartável |
| SQL injection | Crítico | prepared statements nativos | gate de queries + revisão contínua |
| Roubo de sessão | Alto | HttpOnly/Secure/Strict + rotação no login | WAF e monitoramento de anomalia |
| Brute force | Alto | rate limit local | Cloudflare rate limiting |
| XSS | Alto | escaping `e()` + CSP | eliminar `unsafe-inline` de estilos no futuro |
| Vazamento de APP_KEY | Crítico | `.env` fora do código + Apache deny | secret rotation procedure |
| Exposição em logs | Alto | logs estruturados mínimos | redaction contínua e retenção curta |
| IA com PII | Alto | sanitização local | DLP/NER mais robusto e contrato com provedor |
| Supply chain | Alto | sem dependências runtime no core atual | SCA quando Composer/NPM entrarem |

## Screenshot em erro
Não é capturada automaticamente. Em um EHR psicológico, screenshots podem conter prontuários e criar uma cópia adicional de dado sensível. Qualquer futura captura deve ser manual, consentida, redigida e com retenção definida.
