<?php
/**
 * Endpoint para geração de respostas da IA
 * 
 * Este endpoint é chamado pelo N8N para processar mensagens
 * e gerar respostas usando Google Gemini AI.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use POST.']);
    exit();
}

require_once __DIR__ . '/../classes/GeminiAI.php';
require_once __DIR__ . '/../classes/WhatsAppManager.php';

try {
    // Obter dados da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados JSON inválidos');
    }
    
    // Validar campos obrigatórios
    $required_fields = ['instanceId', 'from', 'message'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Campo obrigatório ausente: $field");
        }
    }
    
    $instance_id = $input['instanceId'];
    $from = $input['from'];
    $message = $input['message'];
    $ai_prompt = $input['ai_prompt'] ?? '';
    $knowledge_base = $input['knowledge_base'] ?? '';
    $conversation_history = $input['conversation_history'] ?? [];
    
    // Inicializar classes
    $gemini = new GeminiAI();
    $whatsapp_manager = new WhatsAppManager();
    
    // Obter configurações da instância
    $instance_settings = $whatsapp_manager->getInstanceSettings($instance_id);
    if (!$instance_settings) {
        throw new Exception('Instância não encontrada');
    }
    
    // Verificar se a instância está ativa
    if ($instance_settings['status'] !== 'connected') {
        throw new Exception('Instância não está conectada');
    }
    
    // Obter configurações de IA
    $ai_settings = $gemini->getAISettings($instance_settings['id']);
    if (!$ai_settings) {
        throw new Exception('Configurações de IA não encontradas');
    }
    
    // Usar prompt e base de conhecimento das configurações se não fornecidos
    if (empty($ai_prompt)) {
        $ai_prompt = $ai_settings['ai_prompt'];
    }
    if (empty($knowledge_base)) {
        $knowledge_base = $ai_settings['knowledge_base'];
    }
    
    // Verificar se deve transferir para atendimento humano
    if ($gemini->shouldHandoffToHuman($message, $ai_settings['auto_handoff_keywords'])) {
        echo json_encode([
            'success' => true,
            'ai_response' => 'Entendo que você precisa de uma atenção especial. Vou transferir você para um de nossos agentes humanos que poderá ajudá-lo melhor.',
            'handoff_to_human' => true,
            'reason' => 'Palavra-chave de transferência detectada'
        ]);
        exit();
    }
    
    // Verificar horário de funcionamento
    if (!$gemini->isWithinActiveHours($ai_settings['active_hours_start'], $ai_settings['active_hours_end'], $ai_settings['active_days'])) {
        $response_message = "Obrigado por entrar em contato! Nosso horário de atendimento é das " . 
                          date('H:i', strtotime($ai_settings['active_hours_start'])) . " às " . 
                          date('H:i', strtotime($ai_settings['active_hours_end'])) . 
                          ", de segunda a sexta-feira. Retornaremos seu contato no próximo horário comercial.";
        
        echo json_encode([
            'success' => true,
            'ai_response' => $response_message,
            'outside_business_hours' => true
        ]);
        exit();
    }
    
    // Obter histórico da conversa se não fornecido
    if (empty($conversation_history)) {
        $conversation_history = $whatsapp_manager->getConversationHistory($instance_settings['id'], $from, 10);
    }
    
    // Gerar resposta da IA
    $ai_result = $gemini->generateResponse(
        $message,
        $ai_prompt,
        $knowledge_base,
        $conversation_history,
        [
            'temperature' => $ai_settings['temperature'],
            'max_tokens' => $ai_settings['max_tokens']
        ]
    );
    
    if ($ai_result['success']) {
        // Obter ou criar conversa
        $conversation = $whatsapp_manager->getOrCreateConversation($instance_settings['id'], $from);
        
        // Salvar mensagem do usuário
        $whatsapp_manager->saveMessage(
            $conversation['id'],
            null, // message_id será gerado
            'user',
            $from,
            $message,
            'text'
        );
        
        // Salvar resposta da IA
        $whatsapp_manager->saveMessage(
            $conversation['id'],
            null,
            'ai',
            $instance_settings['phone_number'],
            $ai_result['response'],
            'text'
        );
        
        // Salvar log da IA
        $gemini->logInteraction(
            $instance_settings['id'],
            $conversation['id'],
            $message,
            $ai_result['response'],
            $ai_result['processing_time_ms'],
            $ai_result['tokens_used']
        );
        
        // Aplicar delay se configurado
        if ($ai_settings['response_delay_seconds'] > 0) {
            sleep($ai_settings['response_delay_seconds']);
        }
        
        // Retornar resposta
        echo json_encode([
            'success' => true,
            'ai_response' => $ai_result['response'],
            'processing_time_ms' => $ai_result['processing_time_ms'],
            'tokens_used' => $ai_result['tokens_used'],
            'model_used' => $ai_result['model_used'],
            'conversation_id' => $conversation['id']
        ]);
        
    } else {
        // Erro na geração da resposta
        echo json_encode([
            'success' => false,
            'error' => $ai_result['error'],
            'ai_response' => $ai_result['response'] // Resposta de fallback
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erro no endpoint generate-response: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'ai_response' => 'Desculpe, estou com dificuldades técnicas no momento. Um agente humano irá atendê-lo em breve.'
    ]);
}
?>
