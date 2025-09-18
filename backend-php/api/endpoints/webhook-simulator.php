<?php
/**
 * Simulador de Webhooks para Testes
 * 
 * Este endpoint permite simular diferentes tipos de webhooks
 * da Evolution API para testar o sistema sem precisar de
 * uma instância real do WhatsApp conectada.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../classes/WebhookManager.php';
require_once __DIR__ . '/../classes/WhatsAppManager.php';

try {
    $whatsapp_manager = new WhatsAppManager();
    
    // GET - Listar simulações disponíveis
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $simulations = [
            'message_received' => [
                'description' => 'Simula recebimento de mensagem de texto',
                'method' => 'POST',
                'endpoint' => '/api/webhook-simulator/message-received',
                'example_payload' => [
                    'instance_id' => 'test_instance_001',
                    'from' => '5511999999999@c.us',
                    'message' => 'Olá, preciso de ajuda!',
                    'contact_name' => 'Cliente Teste'
                ]
            ],
            'connection_update' => [
                'description' => 'Simula atualização de status de conexão',
                'method' => 'POST',
                'endpoint' => '/api/webhook-simulator/connection-update',
                'example_payload' => [
                    'instance_id' => 'test_instance_001',
                    'status' => 'connected', // connected, disconnected, connecting
                    'qr_code' => null
                ]
            ],
            'qr_code_update' => [
                'description' => 'Simula geração de novo QR Code',
                'method' => 'POST',
                'endpoint' => '/api/webhook-simulator/qr-code-update',
                'example_payload' => [
                    'instance_id' => 'test_instance_001',
                    'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...'
                ]
            ],
            'ai_response_test' => [
                'description' => 'Testa o fluxo completo de IA',
                'method' => 'POST',
                'endpoint' => '/api/webhook-simulator/ai-response-test',
                'example_payload' => [
                    'instance_id' => 'test_instance_001',
                    'from' => '5511999999999@c.us',
                    'message' => 'Quais são os seus planos de preços?',
                    'contact_name' => 'Cliente Interessado'
                ]
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Simulações disponíveis',
            'simulations' => $simulations
        ]);
        exit();
    }
    
    // POST - Executar simulação
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Dados JSON inválidos');
        }
        
        $simulation_type = $input['simulation_type'] ?? '';
        $instance_id = $input['instance_id'] ?? 'test_instance_001';
        
        // Verificar se a instância existe
        $instance_settings = $whatsapp_manager->getInstanceSettings($instance_id);
        if (!$instance_settings) {
            throw new Exception('Instância não encontrada: ' . $instance_id);
        }
        
        switch ($simulation_type) {
            case 'message_received':
                $result = simulateMessageReceived($input, $instance_settings);
                break;
                
            case 'connection_update':
                $result = simulateConnectionUpdate($input, $instance_settings);
                break;
                
            case 'qr_code_update':
                $result = simulateQRCodeUpdate($input, $instance_settings);
                break;
                
            case 'ai_response_test':
                $result = simulateAIResponseTest($input, $instance_settings);
                break;
                
            default:
                throw new Exception('Tipo de simulação não suportado: ' . $simulation_type);
        }
        
        echo json_encode([
            'success' => true,
            'simulation_type' => $simulation_type,
            'result' => $result
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erro no simulador de webhooks: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Simula recebimento de mensagem
 */
function simulateMessageReceived($input, $instance_settings) {
    $webhook_data = [
        'event' => 'messages.upsert',
        'instance' => $instance_settings['instance_id'],
        'data' => [
            'messages' => [
                [
                    'key' => [
                        'id' => 'SIM_' . uniqid(),
                        'remoteJid' => $input['from'] ?? '5511999999999@c.us',
                        'fromMe' => false
                    ],
                    'message' => [
                        'conversation' => $input['message'] ?? 'Mensagem de teste'
                    ],
                    'messageTimestamp' => time(),
                    'pushName' => $input['contact_name'] ?? 'Cliente Teste'
                ]
            ]
        ]
    ];
    
    $webhook_manager = new WebhookManager();
    return $webhook_manager->handleMessageReceived($webhook_data, $instance_settings);
}

/**
 * Simula atualização de conexão
 */
function simulateConnectionUpdate($input, $instance_settings) {
    $webhook_data = [
        'event' => 'connection.update',
        'instance' => $instance_settings['instance_id'],
        'data' => [
            'state' => $input['status'] ?? 'connected',
            'qr' => $input['qr_code'] ?? null
        ]
    ];
    
    $webhook_manager = new WebhookManager();
    return $webhook_manager->handleConnectionUpdate($webhook_data, $instance_settings);
}

/**
 * Simula atualização de QR Code
 */
function simulateQRCodeUpdate($input, $instance_settings) {
    $webhook_data = [
        'event' => 'qrcode.updated',
        'instance' => $instance_settings['instance_id'],
        'data' => [
            'state' => 'qr',
            'qr' => $input['qr_code'] ?? 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ]
    ];
    
    $webhook_manager = new WebhookManager();
    return $webhook_manager->handleConnectionUpdate($webhook_data, $instance_settings);
}

/**
 * Simula teste completo de resposta da IA
 */
function simulateAIResponseTest($input, $instance_settings) {
    // Primeiro, simular recebimento da mensagem
    $message_result = simulateMessageReceived($input, $instance_settings);
    
    // Se a mensagem foi processada e encaminhada para IA, simular resposta
    if ($message_result['action'] === 'forwarded_to_ai') {
        // Simular chamada para o endpoint de geração de resposta
        $ai_payload = [
            'instanceId' => $instance_settings['instance_id'],
            'from' => $input['from'] ?? '5511999999999@c.us',
            'message' => $input['message'] ?? 'Mensagem de teste'
        ];
        
        // Fazer requisição para o endpoint de IA
        $ai_response = callAIEndpoint($ai_payload);
        
        return [
            'message_processing' => $message_result,
            'ai_response' => $ai_response
        ];
    }
    
    return [
        'message_processing' => $message_result,
        'ai_response' => null,
        'note' => 'Mensagem não foi encaminhada para IA (auto-resposta desativada)'
    ];
}

/**
 * Chama o endpoint de IA para teste
 */
function callAIEndpoint($payload) {
    $url = 'http://localhost/api/ai/generate-response';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
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
    
    return [
        'success' => $http_code === 200,
        'http_code' => $http_code,
        'response' => json_decode($response, true)
    ];
}
?>
