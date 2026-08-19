# PRD — PsicoManager AI

## 1. Visão do produto
SaaS multi-tenant para psicólogos, terapeutas e clínicas, reunindo agenda, pacientes, prontuário clínico, documentos, financeiro, portal do paciente e IA assistiva. O produto deve reduzir trabalho administrativo sem ampliar acesso indevido a conteúdo clínico.

## 2. Personas
- Gestor/Administrador da clínica: tenant, equipe, assinatura, agenda operacional e financeiro. Não lê conteúdo clínico salvo se também for profissional autorizado.
- Psicólogo titular: pacientes próprios, agenda, prontuário, documentos, financeiro próprio e IA.
- Psicólogo supervisionado/estagiário: pacientes atribuídos e registros sujeitos à aprovação.
- Secretária/Recepcionista: agenda, cadastro operacional e financeiro básico; sem prontuário.
- Financeiro/Contador: financeiro e exportações; sem prontuário.
- Paciente: portal próprio, consultas, tarefas e diário.
- Administrador SaaS: planos/tenants; sem conteúdo clínico.

## 3. Escopo v0.2
1. Autenticação com usuário e senha, tenant associado automaticamente ao perfil.
2. RBAC no backend e UI.
3. Isolamento por tenant_id em entidades de negócio.
4. Agenda e recorrência.
5. Cadastro de pacientes.
6. Prontuário SOAP/narrativo/anamnese com AES-256-GCM em repouso no app.
7. Fluxo de supervisão.
8. Documentos clínicos e validação pública sem expor paciente/conteúdo.
9. Financeiro e recibos.
10. Portal do paciente.
11. Copiloto IA com sanitização local e RAG textual.
12. Auditoria, sessões, rate limiting, headers, logs estruturados e gate DevSecOps.

## 4. Fora do escopo v0.2
- Assinatura ICP-Brasil A1/A3.
- Diagnóstico automático ou decisão clínica autônoma.
- RLS nativo de banco (MySQL não oferece equivalente ao PostgreSQL).
- WhatsApp oficial sem provedor configurado.
- Sincronização OAuth bidirecional com Google Calendar.
- Captura automática de screenshot de páginas clínicas em erros: proibida por padrão para evitar copiar dados sensíveis para observabilidade.

## 5. Requisitos funcionais
### RF-001 Autenticação
O usuário informa apenas e-mail/usuário e senha. O tenant é obtido da conta autenticada. Não deve existir campo de clínica no login.

**Aceite:** uma credencial válida inicia sessão no tenant correto; uma credencial inválida não revela se o e-mail existe.

### RF-002 RBAC
Toda rota sensível deve possuir gate no backend. A UI é apenas reflexo da autorização.

**Aceite:** remover botão da UI não é considerado controle de segurança; requisição direta sem permissão retorna 403/404.

### RF-003 Multi-tenancy
Toda entidade tenant-owned deve possuir tenant_id e todas as consultas autenticadas devem restringir pelo tenant atual.

**Aceite:** usuário do tenant A não lê/edita/exclui registro do tenant B por alteração de ID.

### RF-004 Prontuário
Conteúdo clínico persistido em `content_enc`, protegido por AES-256-GCM usando APP_KEY externa ao banco.

**Aceite:** banco não contém SOAP em texto puro; descriptografia com APP_KEY correta preserva o payload.

### RF-005 Supervisão
Registros de supervisionado entram em `pending_approval` e só podem ser aprovados pelo supervisor autorizado.

### RF-006 Portal do paciente
Paciente só acessa registros explicitamente destinados ao próprio `patient_id` e `tenant_id`.

### RF-007 IA
Antes de API externa, nomes conhecidos e identificadores comuns são mascarados. A saída sempre requer revisão humana.

### RF-008 Auditoria
Acessos e mudanças sensíveis devem registrar tenant, usuário, ação, entidade, timestamp, IP, user-agent e encadeamento de hash.

## 6. Requisitos não funcionais
- PHP >= 8.5.
- MySQL 8+/MariaDB compatível onde testado pelo provedor.
- PDO com `ATTR_EMULATE_PREPARES=false`.
- HTTPS obrigatório em produção.
- `APP_DEBUG=false` em produção.
- Cookies `HttpOnly`, `Secure`, `SameSite=Strict`.
- CSP, HSTS, no-sniff e frame deny.
- Nenhum segredo commitado no pacote.
- Falha crítica no gate de segurança bloqueia release.

## 7. Critérios de aceite de release
- `bash scripts/security-audit.sh` = PASS.
- Nenhum placeholder PDO nomeado duplicado.
- Nenhuma rota sensível sem gate explícito.
- Nenhuma consulta tenant-owned não justificada sem escopo.
- PHP lint sem falhas.
- Testes unitários básicos de PII, criptografia e senhas passam.
- Migration de hardening revisada antes de produção.
