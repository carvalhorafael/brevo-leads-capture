# Brevo Leads Capture

Plugin WordPress para centralizar capturas de leads no Brevo CRM.

## Objetivo

O plugin deve receber dados de formulários WordPress, criar ou atualizar contatos no Brevo e adicionar esses contatos a listas específicas.

O caso inicial deve continuar atendendo Elementor Pro, mas também permitir captura de leads em outras interfaces, como a página de materiais gratuitos do tema `executive-signal-wordpress-theme`. O contexto histórico de migração fica documentado em `docs/implementation-plan.md`.

## Casos de uso iniciais

- Enviar leads de formulários Elementor Pro para uma lista Brevo.
- Enviar leads do formulário de materiais gratuitos para uma lista Brevo.
- Redirecionar usuários para uma URL de entrega após captura bem-sucedida.
- Mapear campos como nome, email, WhatsApp, origem, material e UTMs para atributos do Brevo.

## Integração Brevo

A integração deve usar a API de contatos da Brevo:

- `POST https://api.brevo.com/v3/contacts`
- header `api-key`
- `email`
- `attributes`
- `listIds`
- `updateEnabled: true`

O plugin deve manter a API key fora do código versionado.

## Relação com o tema Executive Signal

O tema `executive-signal-wordpress-theme` deve continuar responsável por layout, templates e exibição dos materiais gratuitos.

Este plugin deve ser responsável pela captura e envio ao Brevo. O tema pode apontar o formulário para um endpoint do plugin e receber de volta o redirecionamento para a página ou URL de entrega do material.

## Desenvolvimento e testes

O projeto usa PHPUnit com uma suíte unitária rápida e uma suíte integrada com o ambiente de testes do WordPress.

```bash
composer install
composer test:unit
composer install:wp-tests
composer test
```

Mais detalhes estão em `docs/testing.md`.

## Status

Projeto em desenvolvimento inicial. O bootstrap do plugin e o core de payload/client Brevo já foram iniciados; o plano de trabalho está em `docs/work-plan.md`.
