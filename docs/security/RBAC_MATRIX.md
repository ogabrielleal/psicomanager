# Matriz RBAC

| Permissão | Gestor | Psicólogo | Supervisionado | Secretária | Financeiro | Paciente |
|---|---:|---:|---:|---:|---:|---:|
| dashboard.view | ✓ | ✓ | ✓ | ✓ | ✓ | Portal |
| agenda.view/manage | ✓ | ✓ | ✓ | ✓ | — | solicitar/visualizar própria |
| patients.view | ✓ operacional | próprios/supervisionados | atribuídos | ✓ operacional | — | próprio |
| clinical.view/write | somente se profissional autorizado | ✓ | ✓ | — | — | — |
| clinical.approve | — | ✓ supervisor | — | — | — | — |
| documents.view/write | somente se profissional autorizado | ✓ | ✓/aprovação | — | — | próprios compartilhados futuramente |
| ai.use | somente se profissional autorizado | ✓ | ✓ | — | — | — |
| finance.view/manage | ✓ | próprios | limitado | básico | ✓ | próprios recibos futuramente |
| finance.export | ✓ | ✓ próprios | — | — | ✓ | — |
| team.manage | ✓ | — | — | — | — | — |
| security.manage | ✓ | próprias sessões | próprias sessões | — | — | — |

## Regras especiais
- `clinic_admin` não recebe conteúdo clínico por ser administrador. Se possuir CRP e relação clínica válida, o backend ainda exige paciente próprio/supervisionado.
- Supervisionado não aprova o próprio registro.
- Administrador SaaS não usa RBAC clínico e não possui rota de conteúdo cifrado.
