# Captura de materiais gratuitos

O plugin expõe um handler `admin-post.php` para formulários server-rendered de materiais gratuitos.

## Endpoint

O formulário deve postar para:

```php
<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>
```

Método:

```html
method="post"
```

Campo `action`:

```html
<input type="hidden" name="action" value="brevo_leads_capture_free_material">
```

## Campos esperados

- `action`: `brevo_leads_capture_free_material`
- `_wpnonce`: nonce para a action `brevo_leads_capture_free_material`
- `material_id`: ID do post/material
- `name`: nome do lead
- `email`: email do lead
- `whatsapp`: WhatsApp do lead
- `brevo_leads_capture_website`: honeypot, deve ficar vazio
- `utm_source`: opcional
- `utm_medium`: opcional
- `utm_campaign`: opcional
- `utm_term`: opcional
- `utm_content`: opcional

## Nonce

Use a action:

```php
brevo_leads_capture_free_material
```

Exemplo:

```php
wp_nonce_field( 'brevo_leads_capture_free_material' );
```

## Metadados do material

O plugin lê os seguintes metadados:

- `_brevo_leads_capture_list_id`: ID da lista Brevo.
- `_brevo_leads_capture_delivery_url`: URL de entrega após captura bem-sucedida. Pode ser uma URL externa quando o material deve levar para outro domínio.

Fallback temporário para compatibilidade com o tema:

- `_executive_signal_material_capture_url`: usado como URL de entrega quando `_brevo_leads_capture_delivery_url` não está preenchido.

## Fluxo

1. Valida nonce.
2. Rejeita honeypot preenchido.
3. Valida `material_id`.
4. Lê list ID e URL de entrega.
5. Normaliza nome, email, WhatsApp e UTMs.
6. Envia o contato ao Brevo com `updateEnabled: true`.
7. Redireciona para a URL de entrega em sucesso.
8. Redireciona de volta ao material com query args controlados em falha.

O redirect de sucesso usa a URL configurada no metadado do material. Quando essa
URL aponta para outro domínio, o plugin permite temporariamente apenas esse host
configurado para manter `wp_safe_redirect()` sem cair no fallback `/wp-admin/`.
Redirects de erro continuam restritos aos hosts seguros padrão do WordPress.

## Normalização de WhatsApp

Antes de enviar para a Brevo, o plugin remove formatação visual do WhatsApp. Se
o número estiver em formato nacional brasileiro com 10 ou 11 dígitos, o plugin
adiciona o DDI `+55`. Exemplos:

- `11999999999` vira `+5511999999999`;
- `55 11 99999-9999` vira `+5511999999999`;
- `+55 (11) 99999-9999` continua `+5511999999999`.

Números fora desses padrões são enviados apenas com dígitos, sem inferir outro
DDI automaticamente.

## Falhas

Em falha, o plugin não expõe resposta bruta da Brevo nem chaves de API. O redirecionamento adiciona:

```text
brevo_leads_capture=error
brevo_error=<codigo-controlado>
```

Códigos internos possíveis:

- `invalid_nonce`
- `spam`
- `invalid_material`
- `missing_list`
- `missing_delivery`
- `invalid_lead`
- `invalid_payload`
- `brevo_invalid_parameter`
- `brevo_missing_parameter`
- `brevo_duplicate_parameter`
- `brevo_document_not_found`
- `brevo_permission_error`
- `brevo_bad_request`
- `brevo_error`

## Diagnóstico local de erro Brevo

Quando `WP_DEBUG` está ativo, falhas da API Brevo são registradas com prefixo:

```text
[brevo-leads-capture] Free material Brevo request failed.
```

O log inclui `material_id`, `list_id`, `status_code`, um resumo do payload
sem dados pessoais (`attribute_keys`, `list_ids`, `update_enabled`) e um resumo
sanitizado da resposta Brevo (`code`, `message` e detalhes escalares quando
existirem). O plugin não registra API key, email, telefone, payload completo ou
corpo bruto da resposta.

Em `@wordpress/env`, prefira habilitar `WP_DEBUG_LOG` no ambiente local para
persistir `error_log()` em `wp-content/debug.log`. Depois da submissão, consulte:

```bash
npx wp-env run cli -- wp eval 'echo WP_DEBUG_LOG ? WP_CONTENT_DIR . "/debug.log" : "WP_DEBUG_LOG disabled";'
npx wp-env run cli -- tail -n 80 wp-content/debug.log
```

Se `WP_DEBUG_LOG` não estiver ativo, reproduza a submissão pelo WP-CLI para
forçar a execução do handler e imprimir apenas o resultado controlado:

```bash
npx wp-env run cli -- wp eval '$settings = new Brevo_Leads_Capture_Settings(); $capture = new Brevo_Leads_Capture_Free_Material_Capture($settings); $result = $capture->process_submission(array("_wpnonce"=>wp_create_nonce("brevo_leads_capture_free_material"),"material_id"=>487,"name"=>"Codex Test","email"=>"codex-test@example.com","whatsapp"=>"11999999999")); echo wp_json_encode(array("success"=>$result->is_successful(),"status"=>$result->status_code(),"data"=>$result->data()));'
```

Para HTTP 400, confira no painel da Brevo se:

- a lista configurada existe e está acessível pela API key;
- todos os atributos enviados existem na conta Brevo;
- os atributos aceitam o tipo enviado pelo plugin;
- o número de telefone/WhatsApp está em formato aceito pela conta.

O payload de materiais gratuitos envia as chaves de atributo `FIRSTNAME`,
`LASTNAME`, `WHATSAPP`, `SOURCE`, `MATERIAL` e UTMs presentes no formulário. A
API Brevo exige que atributos customizados existam previamente na conta; quando
um deles está ausente ou incompatível, a resposta costuma vir como HTTP 400 com
`code` e `message` acionáveis.
