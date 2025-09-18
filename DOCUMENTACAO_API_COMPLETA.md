# Documentação Técnica da API - Plataforma WhatsApp AI

## Visão Geral

A API da Plataforma WhatsApp AI fornece endpoints RESTful para gerenciar automação de WhatsApp com inteligência artificial. A API é construída em PHP 8.1 e utiliza MySQL como banco de dados.

## Base URL

```
https://anunciarnogoogle.com/api
```

## Autenticação

Atualmente, a API não implementa autenticação para facilitar a integração inicial. Em produção, recomenda-se implementar autenticação JWT ou API Keys.

## Formato de Resposta

Todas as respostas seguem o formato JSON padrão:

```json
{
  "success": true|false,
  "message": "Mensagem descritiva",
  "data": {...},
  "error": "Mensagem de erro (se aplicável)"
}
```

## Códigos de Status HTTP

- **200 OK**: Requisição bem-sucedida
- **400 Bad Request**: Dados inválidos na requisição
- **404 Not Found**: Recurso não encontrado
- **405 Method Not Allowed**: Método HTTP não permitido
- **500 Internal Server Error**: Erro interno do servidor

## Endpoints da API

### 1. Inteligência Artificial

#### 1.1 Gerar Resposta da IA

Gera uma resposta inteligente usando Google Gemini para uma mensagem recebida.

**Endpoint**: `POST /api/ai/generate-response`

**Parâmetros**:
```json
{
  "instanceId": "string (obrigatório)",
  "from": "string (obrigatório)",
  "message": "string (obrigatório)",
  "ai_prompt": "string (opcional)",
  "knowledge_base": "string (opcional)",
  "conversation_history": "array (opcional)"
}
```

**Exemplo de Requisição**:
```bash
curl -X POST https://anunciarnogoogle.com/api/ai/generate-response \
  -H "Content-Type: application/json" \
  -d '{
    "instanceId": "minha_instancia_001",
    "from": "5511999999999@c.us",
    "message": "Quais são os planos disponíveis?"
  }'
```

**Exemplo de Resposta**:
```json
{
  "success": true,
  "ai_response": "Temos 3 planos disponíveis: Básico (R$99/mês), Profissional (R$199/mês) e Empresarial (R$399/mês). Qual deles gostaria de conhecer melhor?",
  "processing_time_ms": 1250,
  "tokens_used": 45,
  "model_used": "gemini-pro",
  "conversation_id": 123
}
```

**Respostas Especiais**:
```json
// Transferência para humano
{
  "success": true,
  "ai_response": "Vou transferir você para um agente humano...",
  "handoff_to_human": true,
  "reason": "Palavra-chave de transferência detectada"
}

// Fora do horário
{
  "success": true,
  "ai_response": "Nosso horário de atendimento é das 9h às 18h...",
  "outside_business_hours": true
}
```

### 2. Configurações

#### 2.1 Obter Configurações de Instância e Conversa

Obtém configurações de auto-resposta e IA para uma instância e conversa específica.

**Endpoint**: `GET /api/settings/instance/{instanceId}/conversation/{from}`

**Exemplo de Requisição**:
```bash
curl -X GET https://anunciarnogoogle.com/api/settings/instance/minha_instancia_001/conversation/5511999999999@c.us
```

**Exemplo de Resposta**:
```json
{
  "success": true,
  "instance_status": "connected",
  "auto_reply_global": true,
  "auto_reply_conversation": true,
  "ai_prompt": "Você é um assistente de vendas...",
  "knowledge_base": "Nossa empresa oferece...",
  "conversation_history": [
    {
      "id": 1,
      "sender_type": "user",
      "content": "Olá",
      "timestamp": "2025-09-18 10:00:00"
    }
  ],
  "instance_name": "WhatsApp Vendas",
  "phone_number": "5511888888888"
}
```

#### 2.2 Atualizar Auto-Resposta Global

Ativa ou desativa auto-resposta global para uma instância.

**Endpoint**: `PUT /api/settings/instance/{instanceId}/auto-reply`

**Parâmetros**:
```json
{
  "auto_reply_global": true|false
}
```

**Exemplo de Requisição**:
```bash
curl -X PUT https://anunciarnogoogle.com/api/settings/instance/minha_instancia_001/auto-reply \
  -H "Content-Type: application/json" \
  -d '{"auto_reply_global": false}'
```

**Exemplo de Resposta**:
```json
{
  "success": true,
  "message": "Auto-resposta global atualizada com sucesso"
}
```

#### 2.3 Atualizar Auto-Resposta por Conversa

Ativa ou desativa auto-resposta para uma conversa específica.

**Endpoint**: `PUT /api/settings/conversation/{conversationId}/auto-reply`

**Parâmetros**:
```json
{
  "instance_id": "string (obrigatório)",
  "contact_number": "string (obrigatório)",
  "auto_reply_conversation": true|false
}
```

#### 2.4 Listar Conversas

Lista conversas de uma instância com paginação.

**Endpoint**: `GET /api/settings/instance/{instanceId}/conversations`

**Parâmetros de Query**:
- `limit`: Número máximo de conversas (padrão: 50)
- `offset`: Offset para paginação (padrão: 0)

**Exemplo de Resposta**:
```json
{
  "success": true,
  "conversations": [
    {
      "id": 1,
      "contact_number": "5511999999999@c.us",
      "contact_name": "Cliente Teste",
      "last_message": "Obrigado pela informação",
      "last_message_timestamp": "2025-09-18 10:30:00",
      "unread_count": 0,
      "auto_reply_enabled": true,
      "status": "active"
    }
  ],
  "pagination": {
    "limit": 50,
    "offset": 0,
    "total": 1
  }
}
```

#### 2.5 Obter Estatísticas

Obtém estatísticas de uso de uma instância.

**Endpoint**: `GET /api/settings/instance/{instanceId}/stats`

**Parâmetros de Query**:
- `days`: Período em dias (padrão: 30)

**Exemplo de Resposta**:
```json
{
  "success": true,
  "stats": {
    "total_conversations": 25,
    "ai_messages_sent": 150,
    "user_messages_received": 200,
    "avg_response_time_ms": 1200.50,
    "period_days": 30
  }
}
```

### 3. Webhooks

#### 3.1 Webhook da Evolution API

Recebe webhooks da Evolution API para processar eventos do WhatsApp.

**Endpoint**: `POST /api/webhook/evolution`

**Tipos de Eventos Suportados**:
- `messages.upsert` / `message.received`
- `connection.update`
- `qrcode.updated`
- `message.sent`

**Exemplo de Payload (Mensagem Recebida)**:
```json
{
  "event": "messages.upsert",
  "instance": "minha_instancia_001",
  "data": {
    "messages": [
      {
        "key": {
          "id": "3EB0C767D82B632A2E",
          "remoteJid": "5511999999999@c.us",
          "fromMe": false
        },
        "message": {
          "conversation": "Olá, preciso de ajuda!"
        },
        "messageTimestamp": 1695038400,
        "pushName": "Cliente Teste"
      }
    ]
  }
}
```

**Exemplo de Resposta**:
```json
{
  "success": true,
  "message": "Webhook processado com sucesso",
  "event_type": "messages.upsert",
  "instance_id": "minha_instancia_001",
  "processed": {
    "status": "processed",
    "action": "forwarded_to_ai",
    "message_id": 456,
    "conversation_id": 123
  }
}
```

### 4. Transferência para Atendimento Humano

#### 4.1 Transferir para Humano

Transfere uma conversa da IA para atendimento humano.

**Endpoint**: `POST /api/human-handoff`

**Parâmetros**:
```json
{
  "instanceId": "string (obrigatório)",
  "from": "string (obrigatório)",
  "last_message": "string (opcional)",
  "reason": "string (opcional)",
  "priority": "low|normal|high|urgent (opcional)"
}
```

**Exemplo de Requisição**:
```bash
curl -X POST https://anunciarnogoogle.com/api/human-handoff \
  -H "Content-Type: application/json" \
  -d '{
    "instanceId": "minha_instancia_001",
    "from": "5511999999999@c.us",
    "last_message": "Preciso falar com um atendente",
    "reason": "Solicitação do cliente",
    "priority": "high"
  }'
```

**Exemplo de Resposta**:
```json
{
  "success": true,
  "message": "Transferência para atendimento humano realizada com sucesso",
  "ticket_id": 789,
  "auto_message": "Você foi transferido para nossa equipe de atendimento...",
  "conversation_id": 123,
  "agents_notified": 3,
  "estimated_wait_time": "5 minutos"
}
```

### 5. Simulador de Webhooks

#### 5.1 Listar Simulações Disponíveis

Lista todas as simulações disponíveis para teste.

**Endpoint**: `GET /api/webhook-simulator/`

**Exemplo de Resposta**:
```json
{
  "success": true,
  "message": "Simulações disponíveis",
  "simulations": {
    "message_received": {
      "description": "Simula recebimento de mensagem de texto",
      "method": "POST",
      "endpoint": "/api/webhook-simulator/message-received",
      "example_payload": {
        "instance_id": "test_instance_001",
        "from": "5511999999999@c.us",
        "message": "Olá, preciso de ajuda!",
        "contact_name": "Cliente Teste"
      }
    }
  }
}
```

#### 5.2 Executar Simulação

Executa uma simulação específica para teste.

**Endpoint**: `POST /api/webhook-simulator/`

**Parâmetros**:
```json
{
  "simulation_type": "message_received|connection_update|qr_code_update|ai_response_test",
  "instance_id": "string",
  // ... outros parâmetros específicos da simulação
}
```

**Tipos de Simulação**:

##### message_received
```json
{
  "simulation_type": "message_received",
  "instance_id": "test_instance_001",
  "from": "5511999999999@c.us",
  "message": "Teste de mensagem",
  "contact_name": "Cliente Teste"
}
```

##### connection_update
```json
{
  "simulation_type": "connection_update",
  "instance_id": "test_instance_001",
  "status": "connected"
}
```

##### qr_code_update
```json
{
  "simulation_type": "qr_code_update",
  "instance_id": "test_instance_001",
  "qr_code": "data:image/png;base64,..."
}
```

##### ai_response_test
```json
{
  "simulation_type": "ai_response_test",
  "instance_id": "test_instance_001",
  "from": "5511999999999@c.us",
  "message": "Quais são os planos?",
  "contact_name": "Cliente Interessado"
}
```

## Estrutura do Banco de Dados

### Tabelas Principais

#### whatsapp_instances
Armazena informações das instâncias do WhatsApp.

```sql
CREATE TABLE whatsapp_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    instance_id VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    status ENUM('disconnected', 'connecting', 'connected', 'error'),
    qr_code TEXT NULL,
    auto_reply_global BOOLEAN DEFAULT FALSE,
    webhook_url VARCHAR(500),
    evolution_api_key VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### conversations
Armazena conversas entre instâncias e contatos.

```sql
CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    contact_number VARCHAR(50) NOT NULL,
    contact_name VARCHAR(255),
    last_message TEXT,
    last_message_timestamp TIMESTAMP NULL,
    unread_count INT DEFAULT 0,
    auto_reply_enabled BOOLEAN DEFAULT TRUE,
    status ENUM('active', 'archived', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### messages
Armazena todas as mensagens trocadas.

```sql
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    message_id VARCHAR(255) UNIQUE,
    sender_type ENUM('user', 'agent', 'ai') NOT NULL,
    sender_number VARCHAR(50),
    content TEXT NOT NULL,
    message_type ENUM('text', 'image', 'audio', 'video', 'document') DEFAULT 'text',
    media_url VARCHAR(500) NULL,
    status ENUM('sent', 'delivered', 'read', 'failed') DEFAULT 'sent',
    is_from_me BOOLEAN DEFAULT FALSE,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### ai_settings
Configurações de IA por instância.

```sql
CREATE TABLE ai_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    ai_prompt TEXT NOT NULL,
    knowledge_base TEXT,
    temperature DECIMAL(3,2) DEFAULT 0.70,
    max_tokens INT DEFAULT 1000,
    response_delay_seconds INT DEFAULT 2,
    active_hours_start TIME DEFAULT '09:00:00',
    active_hours_end TIME DEFAULT '18:00:00',
    active_days VARCHAR(20) DEFAULT '1,2,3,4,5',
    auto_handoff_keywords TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Códigos de Erro Comuns

### Erro 400 - Bad Request
```json
{
  "success": false,
  "error": "Campo obrigatório ausente: instanceId"
}
```

### Erro 404 - Not Found
```json
{
  "success": false,
  "error": "Instância não encontrada"
}
```

### Erro 500 - Internal Server Error
```json
{
  "success": false,
  "error": "Erro interno do servidor",
  "ai_response": "Desculpe, estou com dificuldades técnicas no momento."
}
```

## Limites e Quotas

- **Taxa de Requisições**: Sem limite atual (recomenda-se implementar rate limiting em produção)
- **Tamanho da Mensagem**: Máximo 1000 caracteres para respostas da IA
- **Timeout**: 30 segundos para requisições da IA
- **Histórico de Conversa**: Últimas 10 mensagens são consideradas para contexto

## Webhooks de Saída

A API pode enviar webhooks para sistemas externos (como N8N) nos seguintes eventos:

### Mensagem Recebida (para N8N)
**URL**: Configurável em `WebhookManager.php`
**Payload**:
```json
{
  "instanceId": "minha_instancia_001",
  "id": "3EB0C767D82B632A2E",
  "from": "5511999999999@c.us",
  "to": "5511888888888@c.us",
  "body": "Mensagem do cliente",
  "type": "text",
  "timestamp": 1695038400,
  "pushName": "Cliente Teste",
  "fromMe": false,
  "isGroup": false
}
```

## Exemplos de Integração

### Integração com N8N

1. **Configurar Webhook Trigger** no N8N para receber de Evolution API
2. **Adicionar nó HTTP Request** para chamar `/api/ai/generate-response`
3. **Adicionar nó HTTP Request** para enviar resposta via Evolution API

### Integração com Evolution API

1. **Configurar Webhook** na Evolution API para `https://anunciarnogoogle.com/api/webhook/evolution`
2. **Configurar Instância** no banco de dados
3. **Testar** usando o simulador de webhooks

## Monitoramento e Logs

### Logs Disponíveis
- **Apache Error Log**: `/var/log/apache2/whatsapp-platform_error.log`
- **Apache Access Log**: `/var/log/apache2/whatsapp-platform_access.log`
- **PHP Error Log**: Configurável via `error_log()`

### Métricas Importantes
- Tempo de resposta da IA
- Taxa de sucesso das requisições
- Número de mensagens processadas
- Uso de tokens da API do Gemini

### Endpoints de Saúde
- **Teste Básico**: `GET /test.php`
- **Status da API**: `GET /api/webhook-simulator/`

## Segurança

### Recomendações
1. **HTTPS**: Sempre usar HTTPS em produção
2. **Autenticação**: Implementar JWT ou API Keys
3. **Rate Limiting**: Limitar número de requisições por IP
4. **Validação**: Validar todos os inputs
5. **Logs**: Monitorar logs de segurança
6. **Firewall**: Configurar firewall adequadamente

### Headers de Segurança
A API já inclui headers básicos de segurança:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`

## Versionamento

**Versão Atual**: 1.0.0

Futuras versões manterão compatibilidade com a API atual. Mudanças breaking serão comunicadas com antecedência.

## Suporte

Para suporte técnico ou dúvidas sobre a API:
- Consulte os logs de erro
- Execute os testes automatizados
- Verifique a documentação de instalação
- Entre em contato com a equipe de desenvolvimento
