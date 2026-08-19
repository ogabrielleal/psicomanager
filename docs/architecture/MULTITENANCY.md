# Estratégia Multi-tenant

## Regra central
Nenhum ID vindo da URL, formulário ou JavaScript prova propriedade. A autorização depende do `tenant_id` da sessão e, para conteúdo clínico, da relação profissional/paciente.

## Padrão obrigatório
```php
$stmt = db()->prepare('SELECT * FROM patients WHERE id=:id AND tenant_id=:tenant');
$stmt->execute(['id'=>$id,'tenant'=>$user['tenant_id']]);
```

## Defesa no banco
A migration `20260819_001_devsecops_hardening.sql` adiciona chaves compostas `(tenant_id,id)` e FKs compostas. Exemplo: um `appointments.patient_id` só é válido se o paciente pertencer ao mesmo tenant.

## Consultas globais permitidas
Existem poucas operações sem tenant conhecido antes da consulta: login, worker global, confirmação por token e validação pública por token. Cada exceção é registrada em `docs/security/tenant-query-allowlist.json` com justificativa e auditada pelo security gate.

## Anti-IDOR
- Leitura: `id + tenant_id`.
- Escrita: `id + tenant_id` novamente no endpoint POST; não confiar na listagem anterior.
- Clínico: além de tenant, `Rbac::canViewPatientClinical()`.
- Público: token aleatório de alta entropia e mínimo de dados retornados.
