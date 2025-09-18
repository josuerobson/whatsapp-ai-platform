<?php
/**
 * Endpoint para transferência para atendimento humano
 * 
 * Este endpoint é chamado quando uma conversa precisa ser
 * transferida da IA para um agente humano.
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

require_once __DIR__ . '/../classes/WhatsAppManager.php';
require_once __DIR__ . '/../classes/HumanHandoffManager.php';

try {
    // Obter dados da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados JSON inválidos');
    }
    
    // Validar campos obrigatórios
    $required_fields = ['instanceId', 'from'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Campo obrigatório ausente: $field");
        }
    }
    
    $instance_id = $input['instanceId'];
    $from = $input['from'];
    $last_message = $input['last_message'] ?? '';
    $reason = $input['reason'] ?? 'Transferência solicitada';
    $priority = $input['priority'] ?? 'normal'; // low, normal, high, urgent
    
    // Inicializar classes
    $whatsapp_manager = new WhatsAppManager();
    $handoff_manager = new HumanHandoffManager();
    
    // Verificar se a instância existe
    $instance_settings = $whatsapp_manager->getInstanceSettings($instance_id);
    if (!$instance_settings) {
        throw new Exception('Instância não encontrada');
    }
    
    // Obter ou criar conversa
    $conversation = $whatsapp_manager->getOrCreateConversation($instance_settings['id'], $from);
    if (!$conversation) {
        throw new Exception('Erro ao obter conversa');
    }
    
    // Desativar auto-resposta para esta conversa
    $whatsapp_manager->updateConversationAutoReply($instance_settings['id'], $from, false);
    
    // Criar ticket de atendimento humano
    $ticket = $handoff_manager->createHandoffTicket(
        $instance_settings['id'],
        $conversation['id'],
        $from,
        $last_message,
        $reason,
        $priority
    );
    
    if ($ticket) {
        // Notificar agentes disponíveis
        $notification_result = $handoff_manager->notifyAvailableAgents($ticket, $instance_settings);
        
        // Enviar mensagem automática para o cliente
        $auto_message = $handoff_manager->getHandoffMessage($reason, $priority);
        
        // Salvar mensagem automática no banco
        $whatsapp_manager->saveMessage(
            $conversation['id'],
            null,
            'agent',
            $instance_settings['phone_number'],
            $auto_message,
            'text'
        );
        
        // Retornar resposta de sucesso
        echo json_encode([
            'success' => true,
            'message' => 'Transferência para atendimento humano realizada com sucesso',
            'ticket_id' => $ticket['id'],
            'auto_message' => $auto_message,
            'conversation_id' => $conversation['id'],
            'agents_notified' => $notification_result['agents_notified'],
            'estimated_wait_time' => $notification_result['estimated_wait_time']
        ]);
        
    } else {
        throw new Exception('Erro ao criar ticket de atendimento');
    }
    
} catch (Exception $e) {
    error_log("Erro no endpoint human-handoff: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
