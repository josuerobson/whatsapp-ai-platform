<?php
/**
 * Classe para integração com Google Gemini AI
 * 
 * Esta classe gerencia a comunicação com a API do Google Gemini,
 * processando mensagens e gerando respostas inteligentes.
 */

require_once __DIR__ . '/../../config/database.php';

class GeminiAI {
    private $api_key;
    private $api_url;
    private $model;
    private $default_settings;
    private $db;

    public function __construct() {
        $this->api_key = GeminiConfig::$api_key;
        $this->api_url = GeminiConfig::$api_url;
        $this->model = GeminiConfig::$model;
        $this->default_settings = GeminiConfig::$default_settings;
        
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Gera uma resposta usando Google Gemini
     * 
     * @param string $message Mensagem do usuário
     * @param string $system_prompt Prompt do sistema
     * @param string $knowledge_base Base de conhecimento
     * @param array $conversation_history Histórico da conversa
     * @param array $settings Configurações específicas da IA
     * @return array Resposta da IA com metadados
     */
    public function generateResponse($message, $system_prompt = '', $knowledge_base = '', $conversation_history = [], $settings = []) {
        $start_time = microtime(true);
        
        try {
            // Mesclar configurações padrão com as específicas
            $ai_settings = array_merge($this->default_settings, $settings);
            
            // Construir o contexto completo
            $full_context = $this->buildContext($system_prompt, $knowledge_base, $conversation_history, $message);
            
            // Preparar payload para a API do Gemini
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $full_context]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $ai_settings['temperature'],
                    'maxOutputTokens' => $ai_settings['max_tokens'],
                    'topP' => $ai_settings['top_p'],
                    'topK' => $ai_settings['top_k']
                ]
            ];

            // Fazer requisição para a API do Gemini
            $response = $this->makeApiRequest($payload);
            
            if ($response['success']) {
                $ai_response = $response['data']['candidates'][0]['content']['parts'][0]['text'] ?? 'Desculpe, não consegui gerar uma resposta.';
                
                // Limpar e formatar a resposta
                $ai_response = $this->cleanResponse($ai_response);
                
                $processing_time = round((microtime(true) - $start_time) * 1000); // em milissegundos
                
                return [
                    'success' => true,
                    'response' => $ai_response,
                    'processing_time_ms' => $processing_time,
                    'tokens_used' => $response['data']['usageMetadata']['totalTokenCount'] ?? 0,
                    'model_used' => $this->model
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'],
                    'response' => 'Desculpe, estou com dificuldades técnicas no momento. Um agente humano irá atendê-lo em breve.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erro no GeminiAI: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => 'Desculpe, estou com dificuldades técnicas no momento. Um agente humano irá atendê-lo em breve.'
            ];
        }
    }

    /**
     * Constrói o contexto completo para a IA
     */
    private function buildContext($system_prompt, $knowledge_base, $conversation_history, $current_message) {
        $context = "";
        
        // Adicionar prompt do sistema
        if (!empty($system_prompt)) {
            $context .= "INSTRUÇÕES DO SISTEMA:\n" . $system_prompt . "\n\n";
        }
        
        // Adicionar base de conhecimento
        if (!empty($knowledge_base)) {
            $context .= "BASE DE CONHECIMENTO:\n" . $knowledge_base . "\n\n";
        }
        
        // Adicionar histórico da conversa (últimas 10 mensagens)
        if (!empty($conversation_history)) {
            $context .= "HISTÓRICO DA CONVERSA:\n";
            $recent_history = array_slice($conversation_history, -10);
            
            foreach ($recent_history as $msg) {
                $sender = $msg['sender_type'] === 'user' ? 'Cliente' : 'Assistente';
                $context .= $sender . ": " . $msg['content'] . "\n";
            }
            $context .= "\n";
        }
        
        // Adicionar mensagem atual
        $context .= "MENSAGEM ATUAL DO CLIENTE:\n" . $current_message . "\n\n";
        
        // Adicionar instruções finais
        $context .= "INSTRUÇÕES FINAIS:\n";
        $context .= "- Responda de forma natural e conversacional\n";
        $context .= "- Seja útil, cordial e profissional\n";
        $context .= "- Use as informações da base de conhecimento quando relevante\n";
        $context .= "- Se não souber algo, seja honesto e ofereça transferir para um agente humano\n";
        $context .= "- Mantenha as respostas concisas mas completas\n";
        $context .= "- Use emojis moderadamente para tornar a conversa mais amigável\n\n";
        
        $context .= "RESPOSTA:";
        
        return $context;
    }

    /**
     * Faz requisição para a API do Google Gemini
     */
    private function makeApiRequest($payload) {
        $url = $this->api_url . '?key=' . $this->api_key;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: WhatsApp-AI-Platform/1.0'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $curl_error
            ];
        }
        
        $decoded_response = json_decode($response, true);
        
        if ($http_code === 200 && isset($decoded_response['candidates'])) {
            return [
                'success' => true,
                'data' => $decoded_response
            ];
        } else {
            $error_message = $decoded_response['error']['message'] ?? 'Erro desconhecido da API';
            return [
                'success' => false,
                'error' => "Erro da API (HTTP $http_code): " . $error_message
            ];
        }
    }

    /**
     * Limpa e formata a resposta da IA
     */
    private function cleanResponse($response) {
        // Remover quebras de linha excessivas
        $response = preg_replace('/\n{3,}/', "\n\n", $response);
        
        // Remover espaços em branco no início e fim
        $response = trim($response);
        
        // Limitar tamanho da resposta (máximo 1000 caracteres para WhatsApp)
        if (strlen($response) > 1000) {
            $response = substr($response, 0, 997) . '...';
        }
        
        return $response;
    }

    /**
     * Salva log da interação com a IA
     */
    public function logInteraction($instance_id, $conversation_id, $user_message, $ai_response, $processing_time, $tokens_used) {
        try {
            $query = "INSERT INTO ai_logs (instance_id, conversation_id, user_message, ai_response, processing_time_ms, tokens_used, created_at) 
                      VALUES (:instance_id, :conversation_id, :user_message, :ai_response, :processing_time_ms, :tokens_used, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':instance_id' => $instance_id,
                ':conversation_id' => $conversation_id,
                ':user_message' => $user_message,
                ':ai_response' => $ai_response,
                ':processing_time_ms' => $processing_time,
                ':tokens_used' => $tokens_used
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao salvar log da IA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém configurações de IA para uma instância
     */
    public function getAISettings($instance_id) {
        try {
            $query = "SELECT * FROM ai_settings WHERE instance_id = :instance_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':instance_id' => $instance_id]);
            
            $settings = $stmt->fetch();
            
            if ($settings) {
                return [
                    'ai_prompt' => $settings['ai_prompt'],
                    'knowledge_base' => $settings['knowledge_base'],
                    'temperature' => (float)$settings['temperature'],
                    'max_tokens' => (int)$settings['max_tokens'],
                    'response_delay_seconds' => (int)$settings['response_delay_seconds'],
                    'active_hours_start' => $settings['active_hours_start'],
                    'active_hours_end' => $settings['active_hours_end'],
                    'active_days' => $settings['active_days'],
                    'auto_handoff_keywords' => $settings['auto_handoff_keywords']
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Erro ao obter configurações de IA: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica se deve transferir para atendimento humano
     */
    public function shouldHandoffToHuman($message, $auto_handoff_keywords) {
        if (empty($auto_handoff_keywords)) {
            return false;
        }
        
        $keywords = explode(',', strtolower($auto_handoff_keywords));
        $message_lower = strtolower($message);
        
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (!empty($keyword) && strpos($message_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verifica se está dentro do horário de funcionamento
     */
    public function isWithinActiveHours($active_hours_start, $active_hours_end, $active_days) {
        $current_time = date('H:i:s');
        $current_day = date('N'); // 1 = Segunda, 7 = Domingo
        
        // Verificar se hoje é um dia ativo
        $active_days_array = explode(',', $active_days);
        if (!in_array($current_day, $active_days_array)) {
            return false;
        }
        
        // Verificar se está dentro do horário
        return ($current_time >= $active_hours_start && $current_time <= $active_hours_end);
    }
}
?>
