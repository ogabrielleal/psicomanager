# Observabilidade

## Servidor
`ErrorHandler` escreve JSON em `storage/logs/app-YYYY-MM-DD.log` com:
- request_id
- método/rota
- tenant/user IDs quando disponíveis
- tipo/mensagem do erro
- arquivo/linha
- hash do stack trace

A stack completa não é exibida ao usuário em produção.

## Browser
`assets/js/app.js` envia erros JavaScript para `/errors/client.php` via `sendBeacon`, contendo apenas mensagem, fonte e posição. O endpoint limita payload a 8 KB.

## PII
Não incluir corpo de prontuário, prompt clínico, senha, cookie ou payload de formulário nos logs.

## Evolução
Integração futura: Sentry/OpenTelemetry ou serviço equivalente, sempre com scrubbing de PII e região/contrato avaliados.
