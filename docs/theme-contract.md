# Contrato com o tema

Este plugin concentra captura e envio para Brevo. O tema deve cuidar apenas da renderização.

## Responsabilidades do tema

- Renderizar o formulário.
- Postar para `admin_url( 'admin-post.php' )`.
- Incluir `action=brevo_leads_capture_free_material`.
- Incluir nonce `brevo_leads_capture_free_material`.
- Incluir `material_id`.
- Renderizar campos de nome, email e WhatsApp.
- Renderizar honeypot vazio.
- Preencher UTMs, se existirem.
- Manter layout, texto do botão e experiência visual.

## Responsabilidades do plugin

- Validar nonce e honeypot.
- Validar material.
- Ler list ID e URL de entrega do material.
- Montar payload Brevo.
- Enviar contato para Brevo.
- Redirecionar em sucesso ou falha.
- Manter API key fora do tema.

## Action do formulário

```php
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
```

Campo obrigatório:

```html
<input type="hidden" name="action" value="brevo_leads_capture_free_material">
```

Nonce:

```php
wp_nonce_field( 'brevo_leads_capture_free_material' );
```

Material atual:

```php
<input type="hidden" name="material_id" value="<?php echo esc_attr( get_the_ID() ); ?>">
```

Honeypot:

```html
<input type="text" name="brevo_leads_capture_website" value="" autocomplete="off" tabindex="-1">
```

## Metadados por material

- `_brevo_leads_capture_list_id`: lista Brevo específica do material.
- `_brevo_leads_capture_delivery_url`: URL de entrega pós-captura.

Fallback temporário:

- `_executive_signal_material_capture_url`: usado como URL de entrega quando `_brevo_leads_capture_delivery_url` está vazio.

## Campos enviados

- `name`
- `email`
- `whatsapp`
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_term`
- `utm_content`

## Erros

O tema não deve tentar interpretar resposta da Brevo.

Em falha, o plugin redireciona com:

```text
brevo_leads_capture=error
brevo_error=<codigo-controlado>
```
