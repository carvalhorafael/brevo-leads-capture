# Brevo Leads Capture

Plugin WordPress para centralizar capturas de leads no Brevo CRM.

## Objetivo

O plugin deve receber dados de formularios WordPress, criar ou atualizar contatos no Brevo e adicionar esses contatos a listas especificas.

O caso inicial deve continuar atendendo Elementor Pro, mas tambem permitir captura de leads em outras interfaces, como a pagina de materiais gratuitos do tema `executive-signal-wordpress-theme`. O contexto historico de migracao fica documentado em `docs/implementation-plan.md`.

## Casos de uso iniciais

- Enviar leads de formularios Elementor Pro para uma lista Brevo.
- Enviar leads do formulario de materiais gratuitos para uma lista Brevo.
- Redirecionar usuarios para uma URL de entrega apos captura bem-sucedida.
- Mapear campos como nome, email, WhatsApp, origem, material e UTMs para atributos do Brevo.

## Integracao Brevo

A integracao deve usar a API de contatos da Brevo:

- `POST https://api.brevo.com/v3/contacts`
- header `api-key`
- `email`
- `attributes`
- `listIds`
- `updateEnabled: true`

O plugin deve manter a API key fora do codigo versionado.

## Relacao com o tema Executive Signal

O tema `executive-signal-wordpress-theme` deve continuar responsavel por layout, templates e exibicao dos materiais gratuitos.

Este plugin deve ser responsavel pela captura e envio ao Brevo. O tema pode apontar o formulario para um endpoint do plugin e receber de volta o redirecionamento para a pagina ou URL de entrega do material.

## Status

Projeto em fase de especificacao inicial. Ainda nao ha codigo de plugin implementado nesta pasta.
