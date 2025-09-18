<?php
/**
 * Configurações de Banco de Dados e APIs
 * 
 * Este arquivo centraliza todas as configurações da aplicação,
 * utilizando variáveis de ambiente para maior segurança e flexibilidade.
 */

class Database {
    // Configurações do Banco de Dados
    public static $host;
    public static $port;
    public static $database;
    public static $username;
    public static $password;
    
    // Configurações da Aplicação
    public static $app_env;
    public static $debug_mode;
    public static $base_url;
    public static $api_base_url;
    
    // Chaves de API
    public static $google_gemini_api_key;
    public static $evolution_api_key;
    public static $evolution_api_url;
    
    // Segurança
    public static $jwt_secret;
    public static $password_salt;
    
    // Integração N8N
    public static $n8n_webhook_url;
    
    // Configurações de Email
    public static $smtp_host;
    public static $smtp_port;
    public static $smtp_user;
    public static $smtp_password;
    public static $smtp_from_email;
    public static $smtp_from_name;
    
    private $connection;
    
    /**
     * Inicializar configurações a partir de variáveis de ambiente
     */
    public static function init() {
        // Configurações do Banco de Dados
        self::$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
        self::$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '3306';
        self::$database = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'whatsapp_platform';
        self::$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
        self::$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '';
        
        // Configurações da Aplicação
        self::$app_env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'production';
        self::$debug_mode = filter_var(
            $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?? 'false', 
            FILTER_VALIDATE_BOOLEAN
        );
        self::$base_url = $_ENV['APP_URL'] ?? getenv('APP_URL') ?? 'https://anunciarnogoogle.com';
        self::$api_base_url = $_ENV['API_BASE_URL'] ?? getenv('API_BASE_URL') ?? 'https://anunciarnogoogle.com/api';
        
        // Chaves de API
        self::$google_gemini_api_key = $_ENV['GOOGLE_GEMINI_API_KEY'] ?? getenv('GOOGLE_GEMINI_API_KEY') ?? '';
        self::$evolution_api_key = $_ENV['EVOLUTION_API_KEY'] ?? getenv('EVOLUTION_API_KEY') ?? '';
        self::$evolution_api_url = $_ENV['EVOLUTION_API_URL'] ?? getenv('EVOLUTION_API_URL') ?? '';
        
        // Segurança
        self::$jwt_secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?? 'default_jwt_secret_change_in_production';
        self::$password_salt = $_ENV['PASSWORD_SALT'] ?? getenv('PASSWORD_SALT') ?? 'default_salt_change_in_production';
        
        // Integração N8N
        self::$n8n_webhook_url = $_ENV['N8N_WEBHOOK_URL'] ?? getenv('N8N_WEBHOOK_URL') ?? '';
        
        // Configurações de Email
        self::$smtp_host = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?? '';
        self::$smtp_port = $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?? '587';
        self::$smtp_user = $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ?? '';
        self::$smtp_password = $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?? '';
        self::$smtp_from_email = $_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?? 'noreply@anunciarnogoogle.com';
        self::$smtp_from_name = $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?? 'WhatsApp AI Platform';
        
        // Configurar timezone
        date_default_timezone_set('America/Sao_Paulo');
        
        // Configurar logs de erro
        if (self::$debug_mode) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(E_ERROR | E_WARNING | E_PARSE);
            ini_set('display_errors', 0);
        }
        
        // Log de inicialização
        if (self::$debug_mode) {
            error_log("Database config initialized for environment: " . self::$app_env);
        }
    }
    
    /**
     * Obter conexão com o banco de dados
     */
    public function getConnection() {
        if ($this->connection === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$database . ";charset=utf8mb4";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ];
                
                $this->connection = new PDO($dsn, self::$username, self::$password, $options);
                
                if (self::$debug_mode) {
                    error_log("Database connection established successfully");
                }
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                
                if (self::$debug_mode) {
                    throw new Exception("Erro de conexão com o banco de dados: " . $e->getMessage());
                } else {
                    throw new Exception("Erro de conexão com o banco de dados. Verifique as configurações.");
                }
            }
        }
        
        return $this->connection;
    }
    
    /**
     * Verificar se todas as configurações obrigatórias estão definidas
     */
    public static function validateConfig() {
        $required_configs = [
            'GOOGLE_GEMINI_API_KEY' => self::$google_gemini_api_key,
            'EVOLUTION_API_KEY' => self::$evolution_api_key,
            'EVOLUTION_API_URL' => self::$evolution_api_url,
            'JWT_SECRET' => self::$jwt_secret,
            'PASSWORD_SALT' => self::$password_salt
        ];
        
        $missing_configs = [];
        
        foreach ($required_configs as $config_name => $config_value) {
            if (empty($config_value) || $config_value === 'sua_chave_' . strtolower($config_name) . '_aqui') {
                $missing_configs[] = $config_name;
            }
        }
        
        if (!empty($missing_configs)) {
            $message = "Configurações obrigatórias não definidas: " . implode(', ', $missing_configs);
            error_log($message);
            
            if (self::$debug_mode) {
                throw new Exception($message);
            }
        }
        
        return empty($missing_configs);
    }
    
    /**
     * Obter informações de status da configuração
     */
    public static function getConfigStatus() {
        return [
            'environment' => self::$app_env,
            'debug_mode' => self::$debug_mode,
            'database_host' => self::$host,
            'database_name' => self::$database,
            'base_url' => self::$base_url,
            'api_base_url' => self::$api_base_url,
            'google_gemini_configured' => !empty(self::$google_gemini_api_key),
            'evolution_api_configured' => !empty(self::$evolution_api_key),
            'n8n_configured' => !empty(self::$n8n_webhook_url),
            'smtp_configured' => !empty(self::$smtp_host),
            'config_valid' => self::validateConfig()
        ];
    }
}

/**
 * Classes de compatibilidade para manter o código existente funcionando
 */
class GeminiConfig {
    public static function getApiKey() {
        return Database::$google_gemini_api_key;
    }
    
    public static $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    public static $model = 'gemini-pro';
    
    public static $default_settings = [
        'temperature' => 0.7,
        'max_tokens' => 1000,
        'top_p' => 0.9,
        'top_k' => 40
    ];
}

class EvolutionConfig {
    public static function getApiUrl() {
        return Database::$evolution_api_url;
    }
    
    public static function getApiKey() {
        return Database::$evolution_api_key;
    }
    
    public static function getWebhookUrl() {
        return Database::$n8n_webhook_url;
    }
}

class AppConfig {
    public static $app_name = 'WhatsApp AI Platform';
    public static $app_version = '1.0.0';
    public static $timezone = 'America/Sao_Paulo';
    
    public static function getDebugMode() {
        return Database::$debug_mode;
    }
    
    public static function getBaseUrl() {
        return Database::$base_url;
    }
    
    public static function getApiBaseUrl() {
        return Database::$api_base_url;
    }
    
    public static function getJwtSecret() {
        return Database::$jwt_secret;
    }
    
    public static function getPasswordSalt() {
        return Database::$password_salt;
    }
}

// Inicializar configurações automaticamente
Database::init();

// Validar configurações em ambiente de produção
if (Database::$app_env === 'production') {
    Database::validateConfig();
}
?>
