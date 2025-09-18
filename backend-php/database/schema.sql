-- Schema do Banco de Dados - Plataforma WhatsApp AI
-- Este arquivo contém a estrutura completa do banco de dados

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS whatsapp_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE whatsapp_platform;

-- Criar usuário específico para a aplicação
CREATE USER IF NOT EXISTS 'whatsapp_user'@'localhost' IDENTIFIED BY 'whatsapp_password_2024';
GRANT ALL PRIVILEGES ON whatsapp_platform.* TO 'whatsapp_user'@'localhost';
FLUSH PRIVILEGES;

-- Tabela de usuários master (administradores do sistema)
CREATE TABLE IF NOT EXISTS master_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de clientes (usuários da plataforma)
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    company_name VARCHAR(255),
    phone VARCHAR(20),
    plan ENUM('basic', 'professional', 'enterprise') DEFAULT 'basic',
    status ENUM('pending', 'active', 'suspended', 'cancelled') DEFAULT 'pending',
    trial_ends_at TIMESTAMP NULL,
    subscription_ends_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de instâncias do WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    instance_id VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    status ENUM('disconnected', 'connecting', 'connected', 'error') DEFAULT 'disconnected',
    qr_code TEXT NULL,
    auto_reply_global BOOLEAN DEFAULT FALSE,
    webhook_url VARCHAR(500),
    evolution_api_key VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_instance_id (instance_id),
    INDEX idx_status (status)
);

-- Tabela de conversas
CREATE TABLE IF NOT EXISTS conversations (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES whatsapp_instances(id) ON DELETE CASCADE,
    UNIQUE KEY unique_instance_contact (instance_id, contact_number),
    INDEX idx_instance_id (instance_id),
    INDEX idx_contact_number (contact_number),
    INDEX idx_last_message_timestamp (last_message_timestamp)
);

-- Tabela de mensagens
CREATE TABLE IF NOT EXISTS messages (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_sender_type (sender_type),
    INDEX idx_message_id (message_id)
);

-- Tabela de configurações de IA por instância
CREATE TABLE IF NOT EXISTS ai_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    ai_prompt TEXT NOT NULL,
    knowledge_base TEXT,
    temperature DECIMAL(3,2) DEFAULT 0.70,
    max_tokens INT DEFAULT 1000,
    response_delay_seconds INT DEFAULT 2,
    active_hours_start TIME DEFAULT '09:00:00',
    active_hours_end TIME DEFAULT '18:00:00',
    active_days VARCHAR(20) DEFAULT '1,2,3,4,5', -- 1=Segunda, 7=Domingo
    auto_handoff_keywords TEXT, -- Palavras-chave que acionam transferência para humano
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES whatsapp_instances(id) ON DELETE CASCADE,
    UNIQUE KEY unique_instance_ai_settings (instance_id)
);

-- Tabela de logs de IA (para análise e melhoria)
CREATE TABLE IF NOT EXISTS ai_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    conversation_id INT NOT NULL,
    user_message TEXT NOT NULL,
    ai_response TEXT NOT NULL,
    processing_time_ms INT,
    tokens_used INT,
    confidence_score DECIMAL(5,4),
    feedback ENUM('positive', 'negative', 'neutral') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES whatsapp_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    INDEX idx_instance_id (instance_id),
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_created_at (created_at)
);

-- Tabela de webhooks (para N8N e outras integrações)
CREATE TABLE IF NOT EXISTS webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    webhook_type ENUM('message_received', 'message_sent', 'status_change', 'qr_code') NOT NULL,
    webhook_url VARCHAR(500) NOT NULL,
    secret_token VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_triggered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES whatsapp_instances(id) ON DELETE CASCADE,
    INDEX idx_instance_id (instance_id),
    INDEX idx_webhook_type (webhook_type)
);

-- Tabela de templates de mensagens
CREATE TABLE IF NOT EXISTS message_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    variables JSON, -- Variáveis dinâmicas no template
    category ENUM('greeting', 'support', 'sales', 'general') DEFAULT 'general',
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_category (category)
);

-- Tabela de sessões de usuários (para autenticação)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('master', 'client') NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id_type (user_id, user_type),
    INDEX idx_expires_at (expires_at)
);

-- Tabela de auditoria (logs de ações importantes)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_type ENUM('master', 'client', 'system') NOT NULL,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id INT,
    details JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id_type (user_id, user_type),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Inserir usuário master padrão (senha: admin123)
INSERT INTO master_users (name, email, password_hash, role) VALUES 
('Administrador', 'admin@whatsapp-platform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- Inserir cliente de teste (senha: cliente123)
INSERT INTO clients (name, email, password_hash, company_name, plan, status) VALUES 
('Cliente Teste', 'cliente@teste.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Empresa Teste Ltda', 'professional', 'active');

-- Inserir instância de teste
INSERT INTO whatsapp_instances (client_id, instance_id, name, status, auto_reply_global) VALUES 
(1, 'test_instance_001', 'WhatsApp Vendas', 'disconnected', TRUE);

-- Inserir configurações de IA padrão para a instância de teste
INSERT INTO ai_settings (instance_id, ai_prompt, knowledge_base) VALUES 
(1, 'Você é um assistente de vendas amigável e prestativo da Empresa Teste Ltda. Seu objetivo é responder às perguntas dos clientes, qualificar leads e, se possível, agendar uma demonstração com um vendedor humano. Seja sempre cordial e profissional.', 'Nossa empresa oferece soluções de automação para WhatsApp. Planos: Básico (R$99/mês), Profissional (R$199/mês), Empresarial (R$399/mês). Horário de funcionamento: 9h às 18h, de segunda a sexta-feira. Contato: (11) 99999-9999.');
