# Secrets Management

## Regras
- Segredos somente em `.env` ou secret store do provedor.
- `.env` nunca entra no ZIP de release com valores reais.
- `APP_KEY` precisa ser base64 de 32 bytes.
- Banco remoto deve usar usuário exclusivo da aplicação e menor privilégio possível.
- `AI_API_KEY` e token de webhook devem poder ser rotacionados sem alterar código.

## Bootstrap
`EnvValidator` bloqueia produção se faltarem APP_KEY/DB ou se `APP_DEBUG=true`, APP_URL não HTTPS ou cookie seguro estiver desligado.

## Rotação APP_KEY
A APP_KEY cifra conteúdo clínico. Troca direta torna dados antigos ilegíveis. Rotação exige processo de recriptografia: descriptografar com chave antiga e recriptografar com chave nova dentro de janela controlada e backup verificado.
