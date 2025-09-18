# Análise do Projeto Existente e Planejamento da Arquitetura

## 1. Análise do Projeto Existente (WhatsApp Clone PHP/MySQL)

O projeto base fornecido é um clone da interface do WhatsApp Web, desenvolvido em PHP, MySQL, HTML, CSS e JavaScript, projetado para rodar em ambiente XAMPP. Ele simula as funcionalidades básicas de um aplicativo de mensagens, incluindo:

*   **Interface de Usuário:** Design responsivo e fiel ao WhatsApp Web, com barra lateral, lista de conversas e área de chat.
*   **Funcionalidades de Mensagens:** Visualização de mensagens, envio de mensagens (via AJAX), indicadores de leitura, pesquisa e filtros de conversas.
*   **Backend:** PHP 7.4+ para lógica de negócio e APIs REST. MySQL 8.0+ para armazenamento de dados, utilizando PDO para conexão segura.
*   **Estrutura de Banco de Dados:** Tabelas para `users`, `conversations`, `conversation_participants`, `messages` e `message_read_status`.
*   **APIs PHP:** Endpoints para buscar conversas, buscar mensagens, enviar mensagens, buscar usuários e criar conversas.
*   **Segurança:** Implementa prepared statements (PDO) e escape de HTML para prevenir SQL injection e XSS, além de validação de acesso e headers CORS.

### Pontos Fortes:
*   Base sólida para a interface do usuário e gerenciamento de conversas.
*   Uso de tecnologias amplamente conhecidas (PHP, MySQL, JavaScript).
*   Estrutura de banco de dados bem definida para o core de mensagens.
*   Preocupações básicas com segurança já implementadas.

### Pontos Fracos e Oportunidades de Melhoria para o Novo Projeto:
*   **Simulação:** O projeto atual simula o envio/recebimento de mensagens, mas não se conecta a uma API de WhatsApp real.
*   **Escalabilidade:** Projetado para XAMPP, pode não ser ideal para ambientes de produção de alta escalabilidade sem modificações.
*   **Real-time:** O envio de mensagens via AJAX não é real-time no sentido de WebSockets, o que é crucial para uma experiência de chat moderna.
*   **Autenticação:** O sistema de autenticação é básico (`$_SESSION['user_id'] = 1;`), necessitando de um sistema robusto para múltiplos usuários.
*   **Gerenciamento de Usuários:** Não há um painel administrativo para criar e gerenciar usuários e suas permissões.
*   **Integrações:** Não possui integrações com APIs externas (Evolution API, N8N, Google Gemini).
*   **Funcionalidades Avançadas:** Ausência de upload de arquivos, chamadas, notificações push, etc.

## 2. Requisitos do Novo Projeto

O objetivo é transformar o clone do WhatsApp em uma plataforma completa com as seguintes funcionalidades:

1.  **Integração com Evolution API:** Conectar a plataforma a uma API de WhatsApp real para envio e recebimento de mensagens.
2.  **Integração com N8N:** Utilizar N8N para orquestrar fluxos de automação, incluindo webhooks para comunicação com a plataforma.
3.  **Integração com Google Gemini:** Implementar IA para respostas automáticas, utilizando uma base de conhecimento e prompts configuráveis.
4.  **Sistema Multi-usuário:** Permitir que múltiplos usuários respondam ao mesmo WhatsApp, com login individual.
5.  **Controle de Resposta Automática:** Botão global e individual (por conversa) para ativar/desativar respostas automáticas da IA.
6.  **Painel Master (Gestor do Sistema):**
    *   Criação e gerenciamento de usuários.
    *   Gerenciamento de mensalidades.
    *   Ativação de cadastros.
7.  **Painel Cliente:**
    *   Acesso aos recursos de automação.
    *   Leitura de QR Code da Evolution API para conectar o celular.
    *   Configuração de prompts e base de conhecimento para a IA.
8.  **Site de Divulgação:** Landing page para cadastro de novos clientes.
9.  **Tecnologias:** Manter PHP/MySQL para o backend principal, mas considerar a adição de WebSockets para real-time e N8N para automação.

## 3. Planejamento da Arquitetura Proposta

Para atender aos novos requisitos, a arquitetura precisará ser expandida e modularizada. A seguir, uma proposta de arquitetura e as principais modificações necessárias:

### 3.1. Componentes da Arquitetura

*   **Frontend (Plataforma Cliente e Painel Master):** HTML, CSS, JavaScript (com framework moderno como Vue.js ou React para melhor experiência e escalabilidade, ou manter Vanilla JS/jQuery para compatibilidade com o projeto existente, mas com refatoração).
*   **Backend (PHP/MySQL):**
    *   **Core da Plataforma:** Gerenciamento de usuários, conversas, mensagens, configurações de IA, ativação/desativação de automação.
    *   **APIs Internas:** Endpoints RESTful para comunicação com o frontend e com o N8N.
    *   **Módulo Evolution API:** Lógica para interagir com a Evolution API (envio/recebimento de mensagens, QR Code, status da conexão).
*   **Evolution API:** Serviço externo para conexão com o WhatsApp.
*   **N8N:** Plataforma de automação para orquestrar fluxos de trabalho, receber webhooks da Evolution API e da plataforma, e enviar requisições para o Google Gemini e para a plataforma.
*   **Google Gemini:** Serviço de IA para geração de respostas automáticas.
*   **Banco de Dados (MySQL):** Armazenar dados de usuários, conversas, mensagens, configurações de IA, status de automação, logs, etc.

### 3.2. Descrição da Arquitetura (Conceitual)


A arquitetura proposta é modular e distribuída, integrando diversos serviços para fornecer a funcionalidade desejada. O fluxo principal de mensagens e automação se dará da seguinte forma:

1.  **Usuário Final (WhatsApp)** envia uma mensagem.
2.  A **Evolution API** recebe a mensagem e a encaminha via webhook para o **N8N**.
3.  O **N8N** atua como orquestrador, processando a mensagem e tomando decisões com base na lógica configurada.
4.  O **N8N** interage com a **Plataforma PHP/MySQL** para verificar configurações de automação (global e individual), obter prompts e bases de conhecimento para a IA.
5.  A **Plataforma PHP/MySQL** consulta e atualiza o **Banco de Dados MySQL** para gerenciar usuários, conversas, configurações e logs.
6.  Se a automação estiver ativa, o **N8N** envia a mensagem do usuário, o prompt e a base de conhecimento para o **Google Gemini**.
7.  O **Google Gemini** gera uma resposta baseada na IA e a retorna ao **N8N**.
8.  O **N8N** envia a resposta gerada pela IA de volta para a **Evolution API**, que a entrega ao usuário final no WhatsApp.
9.  Para mensagens manuais enviadas por agentes, a **Plataforma PHP/MySQL** se comunica diretamente com a **Evolution API** para envio.

Os componentes de frontend incluem:

*   **Site de Divulgação:** Para cadastro de novos clientes.
*   **Painel Master:** Para o gestor do sistema gerenciar usuários e mensalidades.
*   **Painel Cliente:** Onde os clientes configuram a IA, ativam/desativam automações, visualizam o QR Code para conexão da instância do WhatsApp e utilizam o chat multi-usuário.

Todos esses componentes de frontend interagem com a **Plataforma PHP/MySQL** através de APIs internas.


### 3.3. Modificações no Banco de Dados (Sugestões)

Além das tabelas existentes, as seguintes tabelas e campos seriam necessários:

*   **`users` (existente):**
    *   `role` (e.g., 'admin', 'client', 'agent')
    *   `status` (e.g., 'active', 'inactive', 'pending')
    *   `api_key` (para autenticação de APIs internas, se necessário)
    *   `gemini_api_key` (se cada cliente tiver sua própria chave Gemini)
*   **`clients` (nova):**
    *   `client_id` (PK)
    *   `user_id` (FK para `users` - o usuário master do cliente)
    *   `company_name`
    *   `subscription_status`
    *   `monthly_fee`
    *   `last_payment_date`
    *   `created_at`, `updated_at`
*   **`whatsapp_instances` (nova):**
    *   `instance_id` (PK - ID da instância na Evolution API)
    *   `client_id` (FK para `clients`)
    *   `phone_number`
    *   `status` (e.g., 'connected', 'disconnected', 'pairing')
    *   `qr_code_data` (temporário, para exibir o QR Code)
    *   `auto_reply_global_enabled` (BOOLEAN, default TRUE)
    *   `created_at`, `updated_at`
*   **`conversations` (existente):**
    *   `instance_id` (FK para `whatsapp_instances`)
    *   `auto_reply_individual_enabled` (BOOLEAN, default TRUE)
    *   `assigned_user_id` (FK para `users` - para atribuição de conversas a agentes)
*   **`ai_configs` (nova):**
    *   `config_id` (PK)
    *   `instance_id` (FK para `whatsapp_instances`)
    *   `prompt_template` (TEXT - prompt base para o Gemini)
    *   `knowledge_base` (TEXT/JSON - base de conhecimento para a IA)
    *   `model_parameters` (JSON - temperatura, top_p, etc.)
    *   `created_at`, `updated_at`
*   **`messages` (existente):**
    *   `is_ai_generated` (BOOLEAN, para identificar mensagens da IA)

### 3.4. Fluxo de Integração com Evolution API e N8N

1.  **Conexão da Instância:**
    *   No Painel Cliente, o usuário solicita uma nova instância de WhatsApp.
    *   A plataforma PHP chama a Evolution API para criar uma nova instância e obter o QR Code.
    *   O QR Code é exibido no Painel Cliente.
    *   O usuário escaneia o QR Code com o celular.
    *   A Evolution API notifica a plataforma (via webhook) sobre o status da conexão.
2.  **Recebimento de Mensagens:**
    *   Uma nova mensagem chega no WhatsApp do cliente.
    *   A Evolution API envia um webhook para um endpoint do N8N.
    *   O N8N recebe o webhook e:
        *   Verifica o status `auto_reply_global_enabled` e `auto_reply_individual_enabled` na plataforma PHP (via API).
        *   Se a resposta automática estiver ativada, o N8N chama a API da plataforma PHP para obter o `prompt_template` e a `knowledge_base` configurados para aquela instância/conversa.
        *   O N8N envia a mensagem do usuário, o prompt e a base de conhecimento para o Google Gemini.
        *   O Gemini retorna a resposta.
        *   O N8N envia a resposta gerada pela IA de volta para a Evolution API para ser enviada ao usuário final.
        *   O N8N também pode registrar a mensagem e a resposta da IA na plataforma PHP (via API).
3.  **Envio de Mensagens (Manual):**
    *   Um agente no Painel Cliente envia uma mensagem.
    *   A plataforma PHP chama a Evolution API para enviar a mensagem.
    *   A mensagem é registrada no banco de dados da plataforma.

### 3.5. Webhooks e Simulações

Para simular os webhooks, criaremos endpoints PHP que podem ser chamados manualmente ou via ferramentas como Postman/Insomnia para testar o fluxo. No N8N, os nós de webhook serão configurados para escutar esses endpoints ou diretamente os da Evolution API.

### 3.6. Considerações de Segurança
*   Todas as comunicações entre a plataforma PHP e a Evolution API/N8N/Gemini devem ser via HTTPS.
*   Utilizar chaves de API e tokens para autenticação entre os serviços.
*   Implementar validação de entrada rigorosa em todos os endpoints da API.
*   Gerenciamento de permissões de usuário (ACL) no Painel Master e Cliente.

Este documento serve como base para as próximas fases do projeto, detalhando a análise do sistema existente e a arquitetura proposta para a nova plataforma.
