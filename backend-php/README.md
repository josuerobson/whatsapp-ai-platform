# 🤖 Plataforma WhatsApp AI

Uma plataforma completa para automação de WhatsApp com Inteligência Artificial, integração com Google Gemini, Evolution API e N8N.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1.svg)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 🚀 Características Principais

- **🧠 IA Conversacional**: Integração nativa com Google Gemini para respostas inteligentes
- **📱 WhatsApp Nativo**: Conexão via Evolution API para máxima compatibilidade
- **👥 Múltiplos Usuários**: Vários agentes podem atender a mesma instância
- **🔄 Automação Avançada**: Workflows personalizáveis com N8N
- **🎯 Transferência Inteligente**: Sistema híbrido IA + atendimento humano
- **📊 Analytics Completo**: Métricas detalhadas de conversas e performance
- **🔒 Segurança Empresarial**: Autenticação, logs e auditoria completa

## 🏗️ Arquitetura

```
WhatsApp → Evolution API → Webhook → Backend PHP → Google Gemini → Resposta
                                  ↓
                              Banco MySQL
                                  ↓
                            Dashboard Web
```

## 🛠️ Tecnologias

- **Backend**: PHP 8.1, Apache 2.4
- **Banco de Dados**: MySQL 8.0
- **IA**: Google Gemini Pro
- **WhatsApp**: Evolution API
- **Automação**: N8N
- **Containerização**: Docker & Docker Compose
- **Deploy**: EasyPanel Ready

## 📦 Instalação Rápida (Local com Docker Compose)

### Pré-requisitos

- Docker e Docker Compose
- Chave da API do Google Gemini
- Instância da Evolution API

### 1. Clone o Repositório

```bash
git clone https://github.com/seu-usuario/whatsapp-ai-platform.git
cd whatsapp-ai-platform/backend-php
```

### 2. Configure as Variáveis de Ambiente

```bash
cp .env.example .env
nano .env
```

Configure as seguintes variáveis obrigatórias:

```env
# Chaves de API (OBRIGATÓRIAS)
GOOGLE_GEMINI_API_KEY=sua_chave_google_gemini_aqui
EVOLUTION_API_KEY=sua_chave_evolution_api_aqui
EVOLUTION_API_URL=https://sua-evolution-api.com

# Segurança (GERAR CHAVES ÚNICAS)
JWT_SECRET=sua_chave_jwt_secreta_aqui_64_caracteres_minimo
PASSWORD_SALT=seu_salt_de_senha_aqui_32_caracteres_minimo

# Domínio (para produção)
APP_URL=https://anunciarnogoogle.com
API_BASE_URL=https://anunciarnogoogle.com/api
```

### 3. Inicie os Serviços

```bash
docker-compose up -d
```

### 4. Verifique a Instalação

Acesse: `http://localhost/test.php` (ou a porta mapeada no docker-compose)

Deve retornar um JSON com informações do sistema.

## 🌐 Deploy no EasyPanel (Recomendado)

O EasyPanel simplifica o deploy e gerenciamento de aplicações Dockerizadas. Siga o guia completo para um deploy sem complicações.

### 1. Preparar Repositório GitHub

Organize seu projeto no GitHub conforme a estrutura recomendada no **[Guia de Instalação Completo](docs/GUIA_INSTALACAO_COMPLETO.md)**.

### 2. Deploy do Backend PHP (API)

1.  **Crie um serviço de Banco de Dados MySQL 8.0** no EasyPanel (ex: `whatsapp-ai-mysql`). Anote as credenciais.
2.  **Crie um novo serviço de Aplicação** no EasyPanel, conectando seu repositório GitHub.
    -   **Root Directory**: `backend-php/`
    -   **Tipo**: Docker (detectado automaticamente pelo `Dockerfile`)
    -   **Variáveis de Ambiente**: Configure as variáveis do `.env.example` (DB, API Keys, JWT_SECRET, PASSWORD_SALT, etc.) usando as credenciais do MySQL e suas chaves de API.
    -   **Domínio**: Adicione `anunciarnogoogle.com` (ou subdomínio para a API).
3.  **Deploy** e aguarde a inicialização. O `schema.sql` será executado automaticamente.

### 3. Deploy dos Frontends (Site de Divulgação, Painel Master, Plataforma Cliente)

Para cada frontend (localizados em `whatsapp-platform-landing/`, `admin-master-panel/`, `whatsapp-client-platform/`):

1.  **Crie um novo serviço de Aplicação** no EasyPanel, conectando o mesmo repositório GitHub.
2.  **Root Directory**: Especifique o diretório do frontend (ex: `whatsapp-platform-landing/`).
3.  **Tipo**: Node.js (detectado automaticamente pelo `package.json`).
4.  **Variáveis de Ambiente**: Se necessário (ex: `VITE_API_BASE_URL=https://anunciarnogoogle.com/api`).
5.  **Domínio**: Adicione o domínio ou subdomínio apropriado (ex: `anunciarnogoogle.com` para o site, `admin.anunciarnogoogle.com` para o painel, `app.anunciarnogoogle.com` para a plataforma cliente).
6.  **Deploy**.

## 📚 Documentação Completa

-   **[Guia de Instalação Completo (EasyPanel & GitHub)](GUIA_INSTALACAO_COMPLETO.md)**
-   **[Documentação Técnica da API](DOCUMENTACAO_API_COMPLETA.md)**
-   **[Fluxos N8N](n8n_workflows.md)**
-   **[Análise e Arquitetura do Sistema](analise_e_arquitetura.md)**

## 🔧 Configuração

Consulte o `GUIA_INSTALACAO_COMPLETO.md` para detalhes sobre como configurar as chaves de API, webhooks e outras configurações essenciais.

## 🧪 Testes

Após o deploy, acesse `https://anunciarnogoogle.com/test.php` para um teste básico. Para testes mais abrangentes, use o terminal do EasyPanel no serviço do backend e execute `php tests/api-tests.php`.

## 🤝 Contribuição

Sinta-se à vontade para contribuir com o projeto. Veja as diretrizes em [CONTRIBUTING.md](CONTRIBUTING.md) (se aplicável).

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🆘 Suporte

Para suporte técnico ou dúvidas, consulte a documentação ou abra uma issue no GitHub.

---

**Desenvolvido por**: Manus AI  
**Versão**: 1.0.0  
**Status**: ✅ Pronto para Produção
