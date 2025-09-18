# Guia Completo de Instalação e Deploy - Plataforma WhatsApp AI (EasyPanel & GitHub)

## Visão Geral

Este guia fornece instruções detalhadas para realizar o deploy da **Plataforma WhatsApp AI** completa utilizando **EasyPanel** e **GitHub**. Esta abordagem moderna e eficiente garante um deploy simplificado, escalabilidade e fácil manutenção.

## Pré-requisitos

### Servidor
- Um servidor Linux (Ubuntu 22.04 LTS ou superior recomendado) com **EasyPanel** instalado e configurado.
- **RAM**: Mínimo 4GB (recomendado 8GB)
- **Armazenamento**: Mínimo 20GB de espaço livre

### Serviços Externos
- **Google Gemini API**: Chave de API válida
- **Evolution API**: Instância configurada e URL da API
- **N8N**: Instância configurada (opcional para automação avançada)

### Ferramentas
- Conta no **GitHub** para hospedar o código-fonte.

## Passo 1: Preparação do Repositório GitHub

1.  **Crie um novo repositório privado** no GitHub (ex: `whatsapp-ai-platform`).
2.  **Faça o upload de todo o código-fonte** da plataforma para este repositório. Certifique-se de incluir:
    - O diretório `whatsapp-platform-landing/` (Site de Divulgação)
    - O diretório `admin-master-panel/` (Painel Administrativo Master)
    - O diretório `whatsapp-client-platform/` (Plataforma Cliente)
    - O diretório `/var/www/whatsapp-platform/` (Backend PHP, renomeie para `backend-php/` no GitHub para melhor organização)
    - Todos os arquivos de documentação (`.md`)
    - Os arquivos Docker (`Dockerfile`, `docker-compose.yml`, `.env.example`, `.dockerignore`) que estão no diretório `backend-php/`.

    **Estrutura recomendada no GitHub:**
    ```
    whatsapp-ai-platform/
    ├── backend-php/             # Contém o backend PHP, Dockerfile, docker-compose.yml, .env.example
    │   ├── api/
    │   ├── config/
    │   ├── database/
    │   ├── tests/
    │   ├── .htaccess
    │   ├── test.php
    │   ├── Dockerfile
    │   ├── docker-compose.yml
    │   ├── .env.example
    │   └── .dockerignore
    ├── whatsapp-platform-landing/ # Site de Divulgação (Frontend React)
    ├── admin-master-panel/      # Painel Administrativo Master (Frontend React)
    ├── whatsapp-client-platform/ # Plataforma Cliente (Frontend React)
    ├── docs/                    # Documentação adicional (opcional)
    │   ├── GUIA_INSTALACAO_COMPLETO.md
    │   ├── DOCUMENTACAO_API_COMPLETA.md
    │   └── ...
    ├── README.md                # README principal do projeto
    └── LICENSE
    ```

3.  **Ajuste o arquivo `.env.example`** dentro de `backend-php/` para refletir as variáveis de ambiente que serão configuradas no EasyPanel.

## Passo 2: Deploy do Backend PHP (API) no EasyPanel

O backend PHP será a primeira parte a ser implantada, pois ele serve a API principal e o simulador de webhooks.

### 2.1 Criar Serviço de Banco de Dados

1.  Acesse o painel do EasyPanel.
2.  Vá para **"Applications"** e clique em **"Create Application"**.
3.  Selecione **"Database"** e escolha **"MySQL 8.0"**.
4.  Dê um nome ao serviço (ex: `whatsapp-ai-mysql`).
5.  O EasyPanel irá gerar as credenciais do banco de dados (Host, Porta, Usuário, Senha, Nome do Banco). **Anote-as**, pois serão usadas como variáveis de ambiente.

### 2.2 Criar Serviço do Backend PHP

1.  No EasyPanel, vá para **"Applications"** e clique em **"Create Application"**.
2.  Selecione **"Git Repository"** como fonte.
3.  **Conecte seu repositório GitHub** (`whatsapp-ai-platform`).
4.  Escolha a branch `main` (ou a que contém seu código).
5.  No campo **"Root Directory"**, especifique `backend-php/` (ou o nome da pasta onde está o `Dockerfile` do backend).
6.  O EasyPanel detectará automaticamente o `Dockerfile` e configurará o tipo de serviço como **"Docker"**.
7.  **Configurar Variáveis de Ambiente**: Vá para a seção **"Environment Variables"** e adicione as seguintes variáveis, substituindo pelos seus valores reais:

    ```env
    # Configurações do Banco de Dados (obtidas do serviço MySQL criado)
    DB_HOST=whatsapp-ai-mysql # Nome do serviço MySQL no EasyPanel
    DB_PORT=3306
    DB_NAME=whatsapp_platform # Nome do banco de dados (pode ser o gerado pelo EasyPanel ou o que você definiu)
    DB_USER=usuario_gerado_easypanel
    DB_PASSWORD=senha_gerada_easypanel
    
    # Configurações da Aplicação
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://anunciarnogoogle.com
    API_BASE_URL=https://anunciarnogoogle.com/api
    
    # Chaves de API (OBRIGATÓRIAS)
    GOOGLE_GEMINI_API_KEY=SUA_CHAVE_GOOGLE_GEMINI_AQUI
    EVOLUTION_API_KEY=SUA_CHAVE_EVOLUTION_API_AQUI
    EVOLUTION_API_URL=https://sua-evolution-api.com
    
    # Segurança (GERAR CHAVES ÚNICAS E FORTES)
    JWT_SECRET=sua_chave_jwt_secreta_aqui_64_caracteres_minimo
    PASSWORD_SALT=seu_salt_de_senha_aqui_32_caracteres_minimo
    
    # Integração N8N (Opcional - URL do seu webhook N8N)
    N8N_WEBHOOK_URL=https://seu-n8n.com/webhook/evolution-api-inbound
    
    # Executar migrações na inicialização (deve ser 'true' na primeira vez)
    RUN_MIGRATIONS=true
    ```

8.  **Configurar Domínio**: Vá para a seção **"Domains"** e adicione `anunciarnogoogle.com` (ou o subdomínio que você deseja usar para a API, ex: `api.anunciarnogoogle.com`). O EasyPanel configurará o SSL automaticamente.
9.  **Deploy**: Clique em **"Deploy"** e aguarde o EasyPanel construir a imagem Docker e iniciar o serviço.

### 2.3 Importar Schema do Banco de Dados

Após o deploy do backend, o script `schema.sql` será executado automaticamente na primeira inicialização do contêiner (devido à variável `RUN_MIGRATIONS=true` no `.env.example` e no `Dockerfile`). Você pode verificar os logs do serviço para confirmar a execução.

## Passo 3: Deploy dos Frontends (Site de Divulgação, Painel Master, Plataforma Cliente)

Para cada aplicação frontend (Site de Divulgação, Painel Administrativo Master, Plataforma Cliente), você seguirá um processo similar:

1.  No EasyPanel, vá para **"Applications"** e clique em **"Create Application"**.
2.  Selecione **"Git Repository"** como fonte e conecte o mesmo repositório GitHub (`whatsapp-ai-platform`).
3.  Escolha a branch `main`.
4.  No campo **"Root Directory"**, especifique o diretório correspondente (ex: `whatsapp-platform-landing/` para o site de divulgação).
5.  O EasyPanel detectará que é uma aplicação Node.js/React.
6.  **Configurar Variáveis de Ambiente**: Se o frontend precisar de variáveis de ambiente (ex: URL da API do backend), adicione-as na seção **"Environment Variables"** (ex: `VITE_API_BASE_URL=https://anunciarnogoogle.com/api`).
7.  **Configurar Domínio**: Vá para a seção **"Domains"** e adicione o domínio ou subdomínio apropriado (ex: `anunciarnogoogle.com` para o site de divulgação, `admin.anunciarnogoogle.com` para o painel master, `app.anunciarnogoogle.com` para a plataforma cliente).
8.  **Deploy**: Clique em **"Deploy"**.

## Passo 4: Configuração da Evolution API

1.  Acesse o painel da sua instância da Evolution API.
2.  Configure o webhook para apontar para o endpoint do seu backend PHP no EasyPanel:
    ```
    https://anunciarnogoogle.com/api/webhook/evolution
    ```
    (Substitua `anunciarnogoogle.com` pelo domínio que você configurou para o backend, se for diferente).

## Passo 5: Configuração do N8N (Opcional)

1.  Se você estiver usando N8N, importe os workflows JSON fornecidos na documentação (`n8n_workflows.md`).
2.  Configure os nós de webhook do N8N para receberem do seu backend PHP, se necessário.
3.  Certifique-se de que a variável de ambiente `N8N_WEBHOOK_URL` no EasyPanel esteja apontando para o webhook correto do seu N8N.

## Passo 6: Testes de Funcionamento

### 6.1 Teste Básico do Backend

Acesse no navegador: `https://anunciarnogoogle.com/test.php`

Deve retornar um JSON com informações do sistema e status das configurações.

### 6.2 Executar Testes Automatizados (via EasyPanel Terminal)

1.  No EasyPanel, vá para o serviço do seu backend PHP.
2.  Clique em **"Terminal"**.
3.  Execute o comando:
    ```bash
    php tests/api-tests.php
    ```
    Verifique os resultados dos testes. Lembre-se que alguns testes podem falhar se as chaves de API (Gemini, Evolution) não estiverem configuradas corretamente ou se não houver agentes cadastrados para o handoff.

### 6.3 Teste Manual dos Endpoints

Use ferramentas como `curl` ou Postman para testar os endpoints da API:

#### Listar Simulações Disponíveis
```bash
curl -X GET https://anunciarnogoogle.com/api/webhook-simulator/
```

#### Simular Mensagem
```bash
curl -X POST https://anunciarnogoogle.com/api/webhook-simulator/ \
  -H "Content-Type: application/json" \
  -d 
```

## Passo 7: Verificação Final

### 7.1 Checklist de Funcionamento
- [ ] Todos os serviços (backend, frontends, banco de dados) estão `Running` no EasyPanel.
- [ ] Domínios estão configurados e acessíveis via HTTPS.
- [ ] As variáveis de ambiente estão corretas para todos os serviços.
- [ ] O banco de dados foi inicializado com o `schema.sql`.
- [ ] A Evolution API está apontando para o webhook correto.
- [ ] Os testes automatizados do backend foram executados e os resultados são satisfatórios.
- [ ] Os frontends estão carregando e interagindo corretamente com o backend.

## Solução de Problemas Comuns no EasyPanel

### Aplicação não inicia ou falha no deploy
1.  **Verifique os Logs**: No EasyPanel, vá para o serviço da aplicação e clique em **"Logs"**. Procure por mensagens de erro durante o build ou a inicialização.
2.  **Variáveis de Ambiente**: Certifique-se de que todas as variáveis de ambiente obrigatórias estão configuradas corretamente e sem erros de digitação.
3.  **Dockerfile**: Verifique se o `Dockerfile` está correto e se todas as dependências estão sendo instaladas.
4.  **Root Directory**: Confirme se o "Root Directory" no EasyPanel aponta para a pasta correta do seu repositório onde o `Dockerfile` (para backend) ou `package.json` (para frontends) está localizado.

### Erro de Conexão com Banco de Dados
1.  **Verifique o Serviço MySQL**: Certifique-se de que o serviço MySQL no EasyPanel está `Running`.
2.  **Credenciais**: Confirme se `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` nas variáveis de ambiente do backend estão corretas e correspondem às credenciais do serviço MySQL.
3.  **Firewall**: Verifique se há alguma regra de firewall no EasyPanel que possa estar bloqueando a comunicação entre os serviços.

### Erro 500 - Internal Server Error (Backend PHP)
1.  **Logs do Backend**: Acesse os logs do serviço backend no EasyPanel para ver os erros detalhados do PHP ou Apache.
2.  **Permissões**: Embora o Docker gerencie a maioria das permissões, verifique se há algum problema específico de permissão dentro do contêiner.
3.  **Configurações de API**: Certifique-se de que as chaves `GOOGLE_GEMINI_API_KEY`, `EVOLUTION_API_KEY`, `EVOLUTION_API_URL` estão preenchidas corretamente.

## Conclusão

Seguindo este guia, você terá uma instalação completa e funcional da Plataforma WhatsApp AI implantada via EasyPanel e GitHub. Esta configuração oferece um ambiente robusto, seguro e fácil de gerenciar para sua aplicação.

**Importante**: Mantenha sempre seus repositórios GitHub atualizados e monitore os logs regularmente para garantir o funcionamento adequado do sistema.

