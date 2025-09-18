<?php
/**
 * Endpoint para obter configurações de instância e conversa
 * 
 * Este endpoint é chamado pelo N8N para verificar se deve
 * processar uma mensagem com IA ou encaminhar para humano.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../classes/WhatsAppManager.php';
require_once __DIR__ . '/../classes/GeminiAI.php';

try {
    $whatsapp_manager = new WhatsAppManager();
    $gemini = new GeminiAI();
    
    // Roteamento baseado no método HTTP e path
    $request_uri = $_SERVER['REQUEST_URI'];
    $path_parts = explode('/', trim($request_uri, '/'));
    
    // GET /api/settings/instance/{instanceId}/conversation/{from}
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && count($path_parts) >= 6 && 
        $path_parts[2] === 'settings' && $path_parts[3] === 'instance' && $path_parts[5] === 'conversation') {
        
        $instance_id = $path_parts[4];
        $from = $path_parts[6];
        
        // Obter configurações da instância
        $instance_settings = $whatsapp_manager->getInstanceSettings($instance_id);
        if (!$instance_settings) {
            throw new Exception('Instância não encontrada');
        }
        
        // Obter configurações de auto-resposta
        $auto_reply_settings = $whatsapp_manager->getConversationAutoReplySettings($instance_settings['id'], $from);
        
        // Obter configurações de IA
        $ai_settings = $gemini->getAISettings($instance_settings['id']);
        
        // Obter histórico da conversa
        $conversation_history = $whatsapp_manager->getConversationHistory($instance_settings['id'], $from, 10);
        
        echo json_encode([
            'success' => true,
            'instance_status' => $instance_settings['status'],
            'auto_reply_global' => $auto_reply_settings['auto_reply_global'],
            'auto_reply_conversation' => $auto_reply_settings['auto_reply_conversation'],
            'ai_prompt' => $ai_settings['ai_prompt'] ?? '',
            'knowledge_base' => $ai_settings['knowledge_base'] ?? '',
            'conversation_history' => $conversation_history,
            'instance_name' => $instance_settings['name'],
            'phone_number' => $instance_settings['phone_number']
        ]);
    }
    
    // PUT /api/settings/instance/{instanceId}/auto-reply
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' && count($path_parts) >= 5 && 
            $path_parts[2] === 'settings' && $path_parts[3] === 'instance' && $path_parts[5] === 'auto-reply') {
        
        $instance_id = $path_parts[4];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['auto_reply_global'])) {
            throw new Exception('Campo auto_reply_global é obrigatório');
        }
        
        $result = $whatsapp_manager->updateGlobalAutoReply($instance_id, $input['auto_reply_global']);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Auto-resposta global atualizada com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao atualizar auto-resposta global');
        }
    }
    
    // PUT /api/settings/conversation/{conversationId}/auto-reply
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' && count($path_parts) >= 5 && 
            $path_parts[2] === 'settings' && $path_parts[3] === 'conversation' && $path_parts[5] === 'auto-reply') {
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['instance_id']) || !isset($input['contact_number']) || !isset($input['auto_reply_conversation'])) {
            throw new Exception('Campos instance_id, contact_number e auto_reply_conversation são obrigatórios');
        }
        
        $result = $whatsapp_manager->updateConversationAutoReply(
            $input['instance_id'], 
            $input['contact_number'], 
            $input['auto_reply_conversation']
        );
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Auto-resposta da conversa atualizada com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao atualizar auto-resposta da conversa');
        }
    }
    
    // GET /api/settings/instance/{instanceId}/conversations
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && count($path_parts) >= 5 && 
            $path_parts[2] === 'settings' && $path_parts[3] === 'instance' && $path_parts[5] === 'conversations') {
        
        $instance_id = $path_parts[4];
        
        // Obter configurações da instância
        $instance_settings = $whatsapp_manager->getInstanceSettings($instance_id);
        if (!$instance_settings) {
            throw new Exception('Instância não encontrada');
        }
        
        // Obter parâmetros de paginação
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        // Obter conversas
        $conversations = $whatsapp_manager->getConversations($instance_settings['id'], $limit, $offset);
        
        echo json_encode([
            'success' => true,
            'conversations' => $conversations,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => count($conversations)
            ]
        ]);
    }
    
    // GET /api/settings/instance/{instanceId}/stats
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && count($path_parts) >= 5 && 
            $path_parts[2] === 'settings' && $path_parts[3] === 'instance' && $path_parts[5] === 'stats') {
        
        $instance_id = $path_parts[4];
        
        // Obter configurações da instância
        $instance_settings = $whatsapp_manager->getInstanceSettings($instance_id);
        if (!$instance_settings) {
            throw new Exception('Instância não encontrada');
        }
        
        // Obter período (padrão: 30 dias)
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        
        // Obter estatísticas
        $stats = $whatsapp_manager->getInstanceStats($instance_settings['id'], $days);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    }
    
    else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint não encontrado'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erro no endpoint settings: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
