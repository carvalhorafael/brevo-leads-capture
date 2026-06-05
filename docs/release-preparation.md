# Preparacao de release e empacotamento

Este documento inicia a fase de release do `brevo-leads-capture` para instalacao
em producao.

Status atual: pre-release `0.1.0`. O plugin ja possui core Brevo, captura de
materiais gratuitos, configuracoes globais, adaptador Elementor, update checker
via GitHub Releases, workflow de release e suites de testes. Ainda falta
validacao operacional dos formularios reais antes de marcar uma versao como
pronta para producao.

## Criterios para uma release instalavel

Uma release so deve ser empacotada para producao quando estes itens estiverem
concluidos:

- `composer validate --strict` passa.
- `composer test:unit` passa.
- `composer test` passa em ambiente com a suite oficial do WordPress instalada.
- O cabecalho do plugin tem versao, requisitos, licenca, text domain e domain
  path coerentes.
- `BREVO_LEADS_CAPTURE_VERSION` bate com o header `Version`.
- O asset da GitHub Release segue `brevo-leads-capture-<versao>.zip`.
- `readme.md` descreve instalacao, configuracao e operacao.
- As strings visiveis novas usam text domain `brevo-leads-capture`.
- O pacote nao contem `.env`, `.npmrc`, logs, dumps, caches, `vendor/`, suites
  de teste ou instalacoes WordPress locais.
- A migracao Elementor real seguiu `docs/elementor-real-forms-migration.md`.
- O rollback para Elementor foi testado ou documentado como excecao.

## Verificacao de seguranca antes do pacote

Rode uma revisao local antes de gerar o ZIP:

```bash
git status --short --branch
git diff --check
rg -n "xkeysib-[A-Za-z0-9_-]{16,}|password|passwd|secret|token|Authorization|Bearer|cookie|set-cookie|\\.env" .
```

Se qualquer segredo real aparecer, trate a credencial como comprometida e rode
rotacao antes de continuar.

## Comandos de validacao

Validacao rapida:

```bash
composer validate --strict
composer test:unit
```

Validacao completa:

```bash
composer install:wp-tests
composer test
```

## Geracao do pacote

Use:

```bash
composer package
```

O comando gera:

```text
dist/brevo-leads-capture-<versao>.zip
```

O ZIP contem uma pasta raiz `brevo-leads-capture/`, formato esperado para
instalacao manual pelo admin do WordPress ou por upload no servidor.

Arquivos de desenvolvimento ficam fora do ZIP:

- `.git/`
- `.github/`
- `.gitignore`
- `bin/`
- `tests/`
- `vendor/`
- `node_modules/`
- `dist/`
- `build/`
- arquivos `phpunit*.xml*`
- `.env*`
- `.npmrc`
- `AGENTS.md`
- caches e logs

## Conferencia do ZIP

Depois de empacotar:

```bash
unzip -l dist/brevo-leads-capture-0.1.0.zip
```

Confirme:

- existe `brevo-leads-capture/brevo-leads-capture.php`;
- existem `brevo-leads-capture/includes/`;
- existem `brevo-leads-capture/docs/`;
- nao existem `tests/`, `.github/`, `.env`, `.npmrc`, `vendor/` ou logs.

## Smoke test de instalacao

Em um WordPress de staging:

1. Instale o ZIP pelo admin ou extraia em `wp-content/plugins/`.
2. Ative `Brevo Leads Capture`.
3. Abra `Configurações > Brevo Leads Capture`.
4. Configure API key e lista padrao com dados de teste.
5. Execute um envio de material gratuito, se o tema estiver integrado.
6. Execute um envio Elementor piloto.
7. Confirme contato, lista e atributos no Brevo.
8. Confirme que falhas exibem mensagens genericas ao usuario.
9. Confirme logs sem API key, email, telefone ou payload sensivel.

## Checklist de versao

Antes de publicar uma tag:

- Atualizar `Version` em `brevo-leads-capture.php`.
- Atualizar `BREVO_LEADS_CAPTURE_VERSION`.
- Atualizar `CHANGELOG.md`.
- Rodar validacoes completas.
- Fazer merge da preparacao de release na branch base.
- Criar tag anotada, por exemplo:

```bash
git tag -a v0.1.0 -m "Release v0.1.0"
git push origin v0.1.0
```

Depois do push da tag, o workflow `.github/workflows/release.yml` valida,
empacota e publica o ZIP na GitHub Release. O fluxo completo de update pelo
admin do WordPress esta em `docs/github-release-updates.md`.

## Bloqueadores conhecidos para producao

- Formularios Elementor reais ainda precisam passar pelo inventario e pelas
  ondas de migracao controlada.
- Ainda nao ha arquivo `uninstall.php`; antes de uma release publica ampla,
  decidir se as options do plugin devem ser preservadas ou removidas no
  uninstall.
- Ainda nao ha catalogo `.pot` em `languages/`; antes de distribuicao publica
  ampla, gerar catalogo de traducao para as strings visiveis.
