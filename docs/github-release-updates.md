# Atualizacoes do plugin via GitHub Releases

O plugin suporta instalacao inicial por ZIP e atualizacoes futuras pelo painel
do WordPress a partir de GitHub Releases.

## Como o WordPress descobre a atualizacao

O arquivo principal do plugin declara:

```text
Update URI: https://github.com/carvalhorafael/brevo-leads-capture
```

Esse header evita colisao com plugins do diretório wordpress.org.

Para buscar updates, o plugin registra um update checker proprio usando os
hooks nativos do WordPress:

- `pre_set_site_transient_update_plugins`
- `plugins_api`

O checker consulta:

```text
https://api.github.com/repos/carvalhorafael/brevo-leads-capture/releases/latest
```

Se a ultima release publica tiver uma tag maior que a versao instalada e tiver
um asset com o nome esperado, o WordPress passa a exibir a atualizacao no admin.

Nome esperado do asset:

```text
brevo-leads-capture-<versao>.zip
```

Exemplo:

```text
brevo-leads-capture-0.2.0.zip
```

O ZIP precisa conter uma pasta raiz:

```text
brevo-leads-capture/
```

## Fluxo de release automatizado

1. Atualizar a versao em `brevo-leads-capture.php`:
   - header `Version`;
   - constante `BREVO_LEADS_CAPTURE_VERSION`.
2. Atualizar `CHANGELOG.md`.
3. Fazer merge da preparacao de release na branch base.
4. Criar e enviar uma tag:

```bash
git tag -a v0.2.0 -m "Release v0.2.0"
git push origin v0.2.0
```

5. O workflow `.github/workflows/release.yml` roda automaticamente.
6. O workflow valida Composer, roda testes, gera o ZIP e cria ou atualiza a
   GitHub Release.
7. O WordPress detecta a release em uma checagem normal de updates.

## Workflow manual

Tambem e possivel rodar o workflow `Release` manualmente pelo GitHub Actions
informando uma tag existente, por exemplo:

```text
v0.2.0
```

O workflow faz checkout dessa tag e publica o asset correspondente.

## Requisitos importantes

- O repositorio precisa estar publico para o update checker anonimo funcionar.
- A release nao pode ser draft.
- A release nao pode ser pre-release.
- A tag precisa seguir `vX.Y.Z`.
- O asset da release precisa seguir `brevo-leads-capture-X.Y.Z.zip`.
- A versao do header e a constante precisam bater com a tag sem o prefixo `v`.

## Tempo ate aparecer no WordPress

O WordPress usa cache de update em transients. O checker do plugin tambem guarda
a resposta do GitHub por 6 horas.

Em uma instalacao de teste, da para forcar nova checagem pela tela de updates do
WordPress ou limpando o transient:

```bash
wp transient delete --network brevo_leads_capture_github_release
wp transient delete --network update_plugins
```

Use esses comandos apenas em ambiente onde WP-CLI esteja disponivel.

## Limitacoes

O checker usa a API publica do GitHub sem token. Isso e adequado para repositorio
publico e evita armazenar credenciais no WordPress. Se o repositorio ficar
privado, o WordPress nao conseguira baixar a release sem adicionar um mecanismo
autenticado de update, o que deve ser tratado como uma decisao de seguranca
separada.
