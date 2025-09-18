<?php
/**
 * Endpoint para receber webhooks da Evolution API
 * 
 * Este endpoint recebe notificações da Evolution API sobre:
 * - Mensagens recebidas
 * - Status de conexão
 * - QR Code gerado
 * - Mensagens enviadas
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
require_once __DIR__ . '/../classes/WebhookManager.php';

try {
    // Obter dados da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados JSON inválidos');
    }
    
    // Log do webhook recebido
    error_log("Webhook Evolution API recebido: " . json_encode($input));
    
    $webhook_manager = new WebhookManager();
    $whatsapp_manager = new WhatsAppManager();
    
    // Identificar tipo de evento
    $event_type = $input['event'] ?? 'unknown';
    $instance_id = $input['instance'] ?? ($input['instanceId'] ?? null);
    
    if (!$instance_id) {
        throw new Exception('Instance ID não fornecido');
    }
    
    // Verificar se a instância existe
    $instance_settings = $whatsapp_manager->getInstanceSettings($instance_id);
    if (!$instance_settings) {
        throw new Exception('Instância não encontrada: ' . $instance_id);
    }
    
    switch ($event_type) {
        case 'messages.upsert':
        case 'message.received':
            $result = $webhook_manager->handleMessageReceived($input, $instance_settings);
            break;
            
        case 'connection.update':
        case 'qrcode.updated':
            $result = $webhook_manager->handleConnectionUpdate($input, $instance_settings);
            break;
            
        case 'message.sent':
            $result = $webhook_manager->handleMessageSent($input, $instance_settings);
            break;
            
        default:
            $result = $webhook_manager->handleGenericEvent($input, $instance_settings);
            break;
    }
    
    // Retornar resposta
    echo json_encode([
        'success' => true,
        'message' => 'Webhook processado com sucesso',
        'event_type' => $event_type,
        'instance_id' => $instance_id,
        'processed' => $result
    ]);
    
} catch (Exception $e) {
    error_log("Erro no webhook Evolution API: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
