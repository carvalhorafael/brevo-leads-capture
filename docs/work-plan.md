# Plano de trabalho

Este plano transforma a especificação inicial em entregas pequenas, testáveis e próprias para pull requests.

## Princípios de execução

- Toda entrega deve sair de `develop` em uma branch `codex/`.
- Cada feature nova deve ter testes proporcionais ao risco.
- O core Brevo deve ficar independente dos adaptadores de entrada.
- O tema não deve conhecer a API da Brevo.
- Segredos devem ser lidos por constante ou configuração segura, nunca por arquivo versionado.

## Fase 1: Bootstrap e core base

Status: em andamento.

Entregas:

- arquivo principal `brevo-leads-capture.php`;
- constantes do plugin;
- classe principal de bootstrap;
- carregamento do text domain;
- resultado padronizado;
- settings iniciais via constantes;
- builder de payload Brevo;
- client HTTP para `POST /v3/contacts`;
- testes unitários de payload e client;
- teste integrado de bootstrap WordPress.

## Fase 2: Captura de materiais gratuitos

Status: pendente.

Entregas:

- handler `admin-post.php` para `brevo_leads_capture_free_material`;
- validação de nonce;
- honeypot;
- leitura de `material_id`;
- leitura de metadados de list ID e URL de entrega;
- montagem do payload com origem `free_material`;
- redirecionamento seguro em sucesso e falha;
- testes integrados cobrindo nonce, email inválido, sucesso e falha.

## Fase 3: Compatibilidade Elementor

Status: pendente.

Entregas:

- adaptador Elementor Pro;
- action name compatível com formulários existentes;
- controles preservados quando possível;
- conversão de campos Elementor para payload padrão;
- remoção de chamada HTTP duplicada do adaptador;
- testes de mapeamento e compatibilidade.

## Fase 4: Configuração e administração

Status: pendente.

Entregas:

- decisão entre constante, option ou ambos para API key;
- list ID padrão;
- tela administrativa apenas se necessária;
- validação de capabilities e nonces;
- mensagens administrativas sem expor segredos.

## Fase 5: Hardening e documentação de uso

Status: pendente.

Entregas:

- logs técnicos controlados;
- documentação de instalação;
- contrato final para o tema `executive-signal-wordpress-theme`;
- checklist de migração Elementor;
- instruções de operação sem versionar segredos.
