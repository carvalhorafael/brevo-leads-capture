# Instrucoes para agentes

## Contexto do projeto

Este repositorio deve conter o plugin WordPress `brevo-leads-capture`.

O objetivo do plugin e centralizar capturas de leads para o Brevo CRM, reaproveitando a experiencia ja existente no plugin `elementor-form-brevo-action` do repositorio `carvalhorafael/site-rafaelcarvalho`, mas evoluindo a arquitetura para suportar mais de uma origem de captura.

Escopo inicial:

- manter compatibilidade com formularios Elementor Pro via uma action de formulario;
- expor uma integracao para capturas de materiais gratuitos usadas pelo tema `executive-signal-wordpress-theme`;
- centralizar chamada HTTP para a API da Brevo em uma camada reutilizavel;
- manter regra de negocio de CRM fora do tema WordPress.

## Boas praticas para plugin WordPress

- Nao colocar funcionalidades de negocio em temas quando elas precisam sobreviver a troca de tema.
- Usar prefixos consistentes para funcoes, classes, constantes, hooks, options e metadados.
- Evitar estado global desnecessario. Preferir classes pequenas e funcoes de bootstrap claras.
- Validar capabilities e nonces em qualquer acao administrativa ou submissao de formulario.
- Sanitizar toda entrada e escapar toda saida.
- Nao armazenar credenciais sensiveis em arquivos versionados.
- Preferir constantes de ambiente para segredos, por exemplo `BREVO_LEADS_CAPTURE_API_KEY`, ou uma tela de settings com cuidado explicito de seguranca.
- Usar APIs nativas do WordPress: `wp_remote_post`, `wp_safe_redirect`, `register_setting`, `add_settings_section`, `wp_nonce_field`, `check_admin_referer`, `check_ajax_referer`, `rest_ensure_response`.
- Tratar falhas externas sem expor detalhes sensiveis ao usuario final.
- Logar erros tecnicos de forma util para desenvolvimento, sem registrar API keys ou dados sensiveis desnecessarios.
- Internacionalizar textos visiveis com text domain `brevo-leads-capture`.
- Manter compatibilidade com WordPress moderno e PHP suportado pelo ambiente alvo antes de usar sintaxe nova.
- Separar integracoes por adaptador. Elementor nao deve conhecer detalhes da captura de materiais gratuitos, e o tema nao deve conhecer detalhes internos do Elementor.

## Estrutura esperada

Estrutura sugerida para desenvolvimento:

```text
brevo-leads-capture/
├── brevo-leads-capture.php
├── includes/
│   ├── class-brevo-client.php
│   ├── class-lead-payload.php
│   ├── class-settings.php
│   ├── class-free-material-capture.php
│   └── integrations/
│       └── class-elementor-form-action.php
├── tests/
├── languages/
├── docs/
└── readme.md
```

## Fluxo de branches

Regra padrao:

- nao desenvolver diretamente em `main`;
- usar `develop` como branch auxiliar de integracao se o repositorio adotar esse fluxo;
- antes de criar branch de trabalho, buscar `origin` e sincronizar a base;
- toda branch de trabalho deve partir da base atualizada;
- usar prefixo `codex/` para branches criadas por agentes;
- fazer commits pequenos e intencionais;
- fazer push da branch para `origin`;
- abrir PRs pequenos para a branch de integracao definida.

Antes de comecar uma nova tarefa, sempre verificar:

```bash
git status --short --branch
git branch -vv
```

Se o checkout estiver em `main`, criar uma branch de trabalho antes de alterar codigo, salvo se a tarefa for explicitamente preparacao de release.

## Modelo de trabalho

- Leia o codigo existente antes de propor mudancas.
- Preserve compatibilidade com integracoes ja existentes, especialmente formularios Elementor que usam o plugin antigo.
- Se migrar codigo do plugin `elementor-form-brevo-action`, registre no PR o que foi preservado, alterado e removido.
- Adicione testes na proporcao do risco: client Brevo, montagem de payload, sanitizacao, endpoint de captura e adaptador Elementor.
- Ao alterar strings visiveis, atualize arquivos de traducao se o projeto ja tiver pipeline de i18n.
- Nunca commitar credenciais, `.env`, `.npmrc` real, dumps de banco, logs com dados pessoais ou arquivos gerados desnecessarios.
