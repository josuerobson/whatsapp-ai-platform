# Fluxos N8N para Automação da Plataforma WhatsApp AI

Este documento detalha os fluxos de automação a serem implementados no N8N para a plataforma de WhatsApp com IA. Os fluxos são projetados para gerenciar o recebimento de mensagens, a interação com a IA do Google Gemini, o envio de respostas via Evolution API e o controle de respostas automáticas global e por conversa.

## 1. Visão Geral da Arquitetura de Automação

O N8N atuará como o orquestrador central, conectando a Evolution API (para comunicação com o WhatsApp) e a API do Google Gemini (para inteligência artificial). O backend PHP/MySQL será responsável por gerenciar usuários, instâncias, configurações de IA (prompt e base de conhecimento) e o estado das conversas (auto-resposta global/individual).

```mermaid
graph TD
    A[Mensagem Recebida - Evolution API Webhook] --> B{Verificar Configurações da Instância}
    B -- Instância Ativa --> C{Verificar Auto-Resposta Global}
    C -- Auto-Resposta Global Ativa --> D{Verificar Auto-Resposta por Conversa}
    D -- Auto-Resposta por Conversa Ativa --> E[Processar Mensagem com IA (Google Gemini)]
    E --> F[Gerar Resposta da IA]
    F --> G[Salvar Conversa e Resposta no Banco de Dados (PHP/MySQL)]
    G --> H[Enviar Resposta via Evolution API]
    H --> I[Mensagem Enviada com Sucesso]

    B -- Instância Inativa --> J[Notificar Agente Humano (Opcional)]
    C -- Auto-Resposta Global Inativa --> J
    D -- Auto-Resposta por Conversa Inativa --> J
    J --> K[Encaminhar para Atendimento Humano (Plataforma Cliente)]

    subgraph N8N Workflows
        B
        C
        D
        E
        F
        G
        H
    end

    subgraph Backend PHP/MySQL
        DB[(Banco de Dados)]
        API_PHP[API PHP]
    end

    subgraph Plataforma Cliente
        PC[Interface do Agente Humano]
    end

    API_PHP <--> DB
    E <--> API_PHP
    G <--> API_PHP
    K <--> PC
```

## 2. Fluxo Principal: Processamento de Mensagens Recebidas

Este fluxo será acionado por um webhook da Evolution API sempre que uma nova mensagem for recebida em qualquer instância conectada. Ele é o coração da automação.

### 2.1. Webhook de Entrada (Evolution API)

*   **Tipo**: Webhook (POST)
*   **URL**: `https://your-n8n-instance.com/webhook/evolution-api-inbound`
*   **Dados Recebidos**: JSON contendo `instanceId`, `from` (número do remetente), `body` (conteúdo da mensagem), `messageId`, `timestamp`, etc.

### 2.2. Obter Configurações da Instância e Conversa (HTTP Request para Backend PHP)

*   **Ação**: Fazer uma requisição HTTP (GET) para o backend PHP.
*   **Endpoint**: `/api/settings/instance/{instanceId}/conversation/{from}`
*   **Parâmetros**: `instanceId` e `from` (número do remetente).
*   **Retorno Esperado**: JSON com:
    *   `instance_status`: `connected` ou `disconnected`
    *   `auto_reply_global`: `true` ou `false` (configuração global da instância)
    *   `auto_reply_conversation`: `true` ou `false` (configuração específica da conversa)
    *   `ai_prompt`: Prompt do sistema para o Gemini.
    *   `knowledge_base`: Base de conhecimento para o Gemini.
    *   `conversation_history`: Histórico recente da conversa.

### 2.3. Nó de Decisão (IF): Verificar Condições para IA

*   **Condições**: `instance_status === 'connected'` E `auto_reply_global === true` E `auto_reply_conversation === true`.
*   **Caminho TRUE**: Prossegue para o processamento da IA.
*   **Caminho FALSE**: Encaminha para atendimento humano (ou notificação).

### 2.4. Processar Mensagem com IA (HTTP Request para Google Gemini via Backend PHP)

*   **Ação**: Fazer uma requisição HTTP (POST) para o backend PHP, que por sua vez se comunicará com a API do Google Gemini.
*   **Endpoint**: `/api/ai/generate-response`
*   **Corpo da Requisição (JSON)**:
    ```json
    {
      "instanceId": "{{ $json.instanceId }}",
      "from": "{{ $json.from }}",
      "message": "{{ $json.body }}",
      "ai_prompt": "{{ $json.ai_prompt }}",
      "knowledge_base": "{{ $json.knowledge_base }}",
      "conversation_history": "{{ $json.conversation_history }}"
    }
    ```
*   **Retorno Esperado**: JSON com `ai_response` (a resposta gerada pela IA).

### 2.5. Salvar Mensagem e Resposta da IA (HTTP Request para Backend PHP)

*   **Ação**: Fazer uma requisição HTTP (POST) para o backend PHP para salvar a mensagem recebida e a resposta da IA.
*   **Endpoint**: `/api/messages/save`
*   **Corpo da Requisição (JSON)**:
    ```json
    {
      "instanceId": "{{ $json.instanceId }}",
      "from": "{{ $json.from }}",
      "user_message": "{{ $json.body }}",
      "ai_response": "{{ $json.ai_response }}",
      "timestamp": "{{ $json.timestamp }}"
    }
    ```
*   **Retorno Esperado**: Confirmação de sucesso.

### 2.6. Enviar Resposta via Evolution API (HTTP Request)

*   **Ação**: Fazer uma requisição HTTP (POST) para a Evolution API.
*   **Endpoint**: `https://api.evolution-api.com/message/sendText/{instanceId}`
*   **Headers**: `apiKey: YOUR_EVOLUTION_API_KEY`
*   **Corpo da Requisição (JSON)**:
    ```json
    {
      "number": "{{ $json.from }}",
      "textMessage": {
        "text": "{{ $json.ai_response }}"
      }
    }
    ```
*   **Retorno Esperado**: Confirmação de envio da Evolution API.

### 2.7. Caminho de Atendimento Humano (Webhook para Plataforma Cliente)

*   **Ação**: Fazer uma requisição HTTP (POST) para a plataforma cliente.
*   **Endpoint**: `/api/human-handoff`
*   **Corpo da Requisição (JSON)**:
    ```json
    {
      "instanceId": "{{ $json.instanceId }}",
      "from": "{{ $json.from }}",
      "last_message": "{{ $json.body }}",
      "reason": "Auto-resposta desativada ou condições não atendidas"
    }
    ```
*   **Retorno Esperado**: Confirmação de notificação.

## 3. Fluxo Secundário: Ativação/Desativação de Auto-Resposta

Este fluxo será acionado pelo backend PHP quando o status de auto-resposta global ou por conversa for alterado na plataforma cliente.

### 3.1. Webhook de Entrada (Backend PHP)

*   **Tipo**: Webhook (POST)
*   **URL**: `https://your-n8n-instance.com/webhook/update-auto-reply`
*   **Dados Recebidos**: JSON contendo `instanceId`, `conversationId` (opcional), `autoReplyStatus` (`true` ou `false`), `type` (`global` ou `conversation`).

### 3.2. Nó de Decisão (IF): Tipo de Atualização

*   **Condição**: `type === 'global'`
*   **Caminho TRUE**: Atualiza o status global da instância.
*   **Caminho FALSE**: Atualiza o status da conversa específica.

### 3.3. Atualizar Status Global da Instância (HTTP Request para Backend PHP)

*   **Ação**: Fazer uma requisição HTTP (PUT) para o backend PHP.
*   **Endpoint**: `/api/settings/instance/{instanceId}/auto-reply`
*   **Corpo da Requisição (JSON)**:
    ```json
    {
      "auto_reply_global": "{{ $json.autoReplyStatus }}"
    }
    ```

### 3.4. Atualizar Status da Conversa (HTTP Request para Backend PHP)

*   **Ação**: Fazer uma requisição HTTP (PUT) para o backend PHP.
*   **Endpoint**: `/api/settings/conversation/{conversationId}/auto-reply`
*   **Corpo da Requisição (JSON)**:
    ```json
    {
      "auto_reply_conversation": "{{ $json.autoReplyStatus }}"
    }
    ```

## 4. Simulações de Webhooks (Exemplos de JSON)

Para facilitar os testes e a implementação, abaixo estão exemplos de JSONs que seriam enviados para os webhooks do N8N.

### 4.1. Simulação de Mensagem Recebida (Evolution API para N8N)

```json
{
  "instanceId": "my_whatsapp_instance_123",
  "id": "false_5511999999999@c.us_ABCD12345EFGH6789",
  "from": "5511999999999@c.us",
  "to": "5511888888888@c.us",
  "body": "Olá, gostaria de saber mais sobre os seus produtos.",
  "type": "chat",
  "timestamp": 1678886400,
  "pushName": "Cliente Teste",
  "fromMe": false,
  "isGroup": false
}
```

### 4.2. Simulação de Atualização de Auto-Resposta Global (Backend PHP para N8N)

```json
{
  "instanceId": "my_whatsapp_instance_123",
  "type": "global",
  "autoReplyStatus": false
}
```

### 4.3. Simulação de Atualização de Auto-Resposta por Conversa (Backend PHP para N8N)

```json
{
  "instanceId": "my_whatsapp_instance_123",
  "conversationId": "5511999999999@c.us",
  "type": "conversation",
  "autoReplyStatus": true
}
```

## 5. Ideias para Tornar o Projeto Excepcional

Além dos fluxos básicos, aqui estão algumas ideias para elevar a plataforma:

*   **Processamento de Mídia**: Estender o fluxo para que a IA possa analisar imagens e áudios (transcrição de áudio para texto antes de enviar para a IA).
*   **Múltiplas Bases de Conhecimento**: Permitir que cada instância ou até mesmo cada usuário tenha sua própria base de conhecimento para a IA, adaptando-se a diferentes nichos de negócio.
*   **Agendamento Inteligente**: Integrar a IA com um calendário (Google Calendar) para agendar compromissos diretamente da conversa, confirmando horários e enviando lembretes.
*   **Feedback da IA**: Implementar um sistema onde os agentes humanos possam dar feedback sobre as respostas da IA, permitindo um aprendizado contínuo e melhoria da qualidade das interações.
*   **Modo de Treinamento da IA**: Uma interface onde o usuário possa simular conversas com a IA e corrigir suas respostas, treinando-a de forma interativa.
*   **Detecção de Intenção e Transferência Inteligente**: Usar a IA para detectar a intenção do cliente e, se for algo complexo ou que exija intervenção humana, transferir a conversa para o agente mais adequado (ex: vendas, suporte técnico).
*   **Templates de Mensagens**: Permitir que os agentes humanos criem e usem templates de mensagens rápidas, que podem ser preenchidos dinamicamente pela IA (ex: "Seu pedido {{numero_pedido}} foi enviado.").
*   **Relatórios Detalhados do N8N**: Utilizar os logs do N8N para gerar relatórios sobre o desempenho da automação, tempo de resposta da IA, número de conversas atendidas automaticamente vs. manualmente.
*   **Integração com CRM**: Conectar o N8N a sistemas de CRM para atualizar informações de clientes, criar novos leads ou registrar interações automaticamente.
*   **Notificações Personalizadas**: Enviar notificações personalizadas para os agentes humanos via e-mail ou na própria plataforma quando uma conversa precisar de atenção.

Estes fluxos e ideias fornecem uma base sólida para a construção de uma plataforma de WhatsApp com IA robusta e altamente funcional. A próxima etapa será a implementação da integração com o Google Gemini e o backend PHP para suportar essas automações.
