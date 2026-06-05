# Instrucoes para agentes

## Contexto do projeto

Este repositorio contem o plugin WordPress `brevo-leads-capture`.

O objetivo do plugin e centralizar capturas de leads para o Brevo CRM em uma
camada reutilizavel, com adaptadores separados para cada origem de captura.
Consulte `docs/implementation-plan.md` para escopo de produto, plano de
implementacao, decisoes pendentes e contexto historico de migracao.

## Regras de trabalho no repositorio

- Leia o codigo e a documentacao existente antes de propor ou executar mudancas.
- Mantenha `AGENTS.md` focado em regras permanentes para agentes.
- Mantenha contexto historico, decisoes de produto, migracoes e detalhes de
  escopo em `docs/`.
- Preserve compatibilidade com integracoes existentes quando a especificacao
  exigir, mas documente detalhes de migracao em `docs/` ou no PR.
- Adicione testes na proporcao do risco: client Brevo, montagem de payload,
  sanitizacao, endpoint de captura e adaptadores.
- Ao alterar strings visiveis, atualize arquivos de traducao se o projeto ja
  tiver pipeline de i18n.

## Boas praticas para plugin WordPress

- Nao colocar funcionalidades de negocio em temas quando elas precisam
  sobreviver a troca de tema.
- Usar prefixos consistentes para funcoes, classes, constantes, hooks, options e
  metadados.
- Evitar estado global desnecessario. Preferir classes pequenas e funcoes de
  bootstrap claras.
- Validar capabilities e nonces em qualquer acao administrativa ou submissao de
  formulario.
- Sanitizar toda entrada e escapar toda saida.
- Usar APIs nativas do WordPress: `wp_remote_post`, `wp_safe_redirect`,
  `register_setting`, `add_settings_section`, `wp_nonce_field`,
  `check_admin_referer`, `check_ajax_referer`, `rest_ensure_response`.
- Tratar falhas externas sem expor detalhes sensiveis ao usuario final.
- Logar erros tecnicos de forma util para desenvolvimento, sem registrar API
  keys, tokens, dados pessoais desnecessarios ou payloads sensiveis.
- Internacionalizar textos visiveis com text domain `brevo-leads-capture`.
- Manter compatibilidade com WordPress moderno e PHP suportado pelo ambiente
  alvo antes de usar sintaxe nova.
- Separar integracoes por adaptador. Um adaptador nao deve conhecer detalhes
  internos de outro adaptador.

## Seguranca e repositorio publico

Este projeto deve estar preparado para ser publicado no GitHub.

- Nunca versionar credenciais, tokens, chaves de API, senhas, cookies, dumps de
  banco, logs com dados pessoais, arquivos `.env` reais, `.npmrc` real ou
  configuracoes locais sensiveis.
- Preferir constantes de ambiente para segredos, por exemplo
  `BREVO_LEADS_CAPTURE_API_KEY`, ou settings administrativos com cuidado
  explicito de seguranca.
- Manter exemplos de configuracao sem valores reais, usando apenas arquivos como
  `.env.example` ou placeholders evidentes.
- Antes de commitar, revisar `git status --short` e o diff para confirmar que
  nenhum segredo ou arquivo gerado desnecessario entrou no versionamento.
- Se algum segredo for exposto por acidente, nao apenas remover do arquivo:
  tratar a chave como comprometida e orientar rotacao imediata.

## Arquivos e diretorios que nao devem entrar no Git

- `.env`, `.env.*` reais e arquivos locais de segredo.
- `.npmrc` real, arquivos de credenciais de package managers e caches locais.
- `node_modules/`, `vendor/` quando gerado por Composer, caches e artefatos de
  build.
- Logs, dumps de banco, backups, exports com dados pessoais e arquivos temporarios
  do sistema/editor.
- Instalacoes completas de WordPress, uploads e conteudo runtime.

## Fluxo de branches

Regra padrao:

- nao desenvolver diretamente em `main`;
- usar `develop` como branch auxiliar de integracao se o repositorio adotar esse
  fluxo;
- antes de criar branch de trabalho, buscar `origin` e sincronizar a base quando
  houver remote configurado;
- toda branch de trabalho deve partir da base atualizada;
- usar prefixo `codex/` para branches criadas por agentes;
- fazer commits pequenos e intencionais;
- fazer push da branch para `origin` quando o remote existir;
- abrir PRs pequenos para a branch de integracao definida.

Antes de comecar uma nova tarefa, sempre verificar:

```bash
git status --short --branch
git branch -vv
```

Se o checkout estiver em `main`, criar uma branch de trabalho antes de alterar
codigo, salvo se a tarefa for explicitamente preparacao de release.
