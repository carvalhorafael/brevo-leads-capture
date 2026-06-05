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
- `_brevo_leads_capture_delivery_url`: URL de entrega após captura bem-sucedida.

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
- `brevo_error`
