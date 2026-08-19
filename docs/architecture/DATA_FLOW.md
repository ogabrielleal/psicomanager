# Fluxo de Dados

## Login
```mermaid
sequenceDiagram
    participant U as Usuário
    participant W as Web
    participant RL as RateLimiter
    participant A as Auth
    participant DB as MySQL
    U->>W: e-mail + senha
    W->>RL: IP + e-mail
    RL-->>W: permitido/bloqueado
    W->>A: credenciais
    A->>DB: busca conta global ativa
    DB-->>A: user + tenant + role
    A->>A: password_verify
    A->>DB: cria user_session
    A-->>U: dashboard do tenant resolvido
```

## Prontuário
```mermaid
sequenceDiagram
    participant P as Psicólogo
    participant RB as RBAC
    participant TG as Tenant Scope
    participant C as Crypto
    participant DB as MySQL
    participant AU as Audit
    P->>RB: clinical.write
    RB-->>P: autorizado
    P->>TG: paciente + tenant
    TG->>DB: SELECT id + tenant_id
    DB-->>TG: paciente permitido
    P->>C: SOAP
    C-->>P: content_enc AES-GCM
    P->>DB: INSERT com tenant_id
    P->>AU: clinical.record_create
```

## IA
```text
Nota clínica
  ↓
PII Sanitizer local
  ↓
RAG interno por tenant/global oficial
  ↓
API externa (somente quando AI_ENABLED)
  ↓
Resposta assistiva
  ↓
Revisão humana
  ↓
Persistência cifrada da resposta quando aplicável
```
