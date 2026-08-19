# TLS e Headers

## Obrigatórios em produção
- HTTPS.
- HSTS 1 ano.
- `X-Content-Type-Options: nosniff`.
- `X-Frame-Options: DENY`.
- `Referrer-Policy: strict-origin-when-cross-origin`.
- `Permissions-Policy` restritiva.
- CSP.

## CSP v0.2
Scripts: somente `'self'`; scripts inline foram removidos. Estilos ainda permitem `'unsafe-inline'` porque o frontend atual contém `style=""` em templates. Remover estilos inline é backlog de hardening para permitir CSP de estilo por nonce/hash.

## TLS do MySQL
Se a Hostoo/provedor disponibilizar CA, configure `DB_SSL_CA`; o PDO ativa verificação do certificado quando suportado.
