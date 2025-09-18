<?php
/**
 * Classe para gerenciar webhooks da Evolution API
 * 
 * Esta classe processa diferentes tipos de eventos recebidos
 * da Evolution API e toma as ações apropriadas.
 */

require_once __DIR__ . '/../../config/database.php';

class WebhookManager {
    private $db;
    private $n8n_webhook_url;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->n8n_webhook_url = 'http://localhost:5678/webhook/evolution-api-inbound'; // URL do N8N
    }

    /**
     * Processa mensagem recebida
     */
    public function handleMessageReceived($webhook_data, $instance_settings) {
        try {
            // Extrair dados da mensagem
            $message_data = $this->extractMessageData($webhook_data);
            
            if (!$message_data) {
                return ['status' => 'ignored', 'reason' => 'Dados de mensagem inválidos'];
            }
            
            // Verificar se não é uma mensagem enviada por nós
            if ($message_data['fromMe']) {
                return ['status' => 'ignored', 'reason' => 'Mensagem enviada por nós'];
            }
            
            // Verificar se é uma mensagem de grupo (ignorar por enquanto)
            if ($message_data['isGroup']) {
                return ['status' => 'ignored', 'reason' => 'Mensagem de grupo'];
            }
            
            // Salvar mensagem no banco de dados
            $whatsapp_manager = new WhatsAppManager();
            $conversation = $whatsapp_manager->getOrCreateConversation(
                $instance_settings['id'],
                $message_data['from'],
                $message_data['pushName']
            );
            
            if ($conversation) {
                $message_id = $whatsapp_manager->saveMessage(
                    $conversation['id'],
                    $message_data['id'],
                    'user',
                    $message_data['from'],
                    $message_data['body'],
                    $message_data['type']
                );
                
                // Verificar configurações de auto-resposta
                $auto_reply_settings = $whatsapp_manager->getConversationAutoReplySettings(
                    $instance_settings['id'],
                    $message_data['from']
                );
                
                // Se auto-resposta está ativa, encaminhar para N8N
                if ($auto_reply_settings['auto_reply_global'] && $auto_reply_settings['auto_reply_conversation']) {
                    $this->forwardToN8N($message_data, $instance_settings);
                    
                    return [
                        'status' => 'processed',
                        'action' => 'forwarded_to_ai',
                        'message_id' => $message_id,
                        'conversation_id' => $conversation['id']
                    ];
                } else {
                    // Notificar agentes humanos
                    $this->notifyHumanAgents($message_data, $instance_settings, $conversation);
                    
                    return [
                        'status' => 'processed',
                        'action' => 'forwarded_to_human',
                        'message_id' => $message_id,
                        'conversation_id' => $conversation['id']
                    ];
                }
            }
            
            return ['status' => 'error', 'reason' => 'Falha ao criar conversa'];
            
        } catch (Exception $e) {
            error_log("Erro ao processar mensagem recebida: " . $e->getMessage());
            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    /**
     * Processa atualização de conexão
     */
    public function handleConnectionUpdate($webhook_data, $instance_settings) {
        try {
            $connection_status = $webhook_data['data']['state'] ?? $webhook_data['state'] ?? 'unknown';
            $qr_code = $webhook_data['data']['qr'] ?? $webhook_data['qr'] ?? null;
            
            // Mapear status da Evolution API para nosso sistema
            $status_map = [
                'open' => 'connected',
                'close' => 'disconnected',
                'connecting' => 'connecting',
                'qr' => 'disconnected'
            ];
            
            $new_status = $status_map[$connection_status] ?? 'disconnected';
            
            // Atualizar status da instância no banco
            $query = "UPDATE whatsapp_instances SET status = :status, qr_code = :qr_code, updated_at = NOW() 
                      WHERE instance_id = :instance_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':status' => $new_status,
                ':qr_code' => $qr_code,
                ':instance_id' => $instance_settings['instance_id']
            ]);
            
            // Se há QR code, notificar cliente
            if ($qr_code) {
                $this->notifyQRCodeUpdate($instance_settings, $qr_code);
            }
            
            return [
                'status' => 'processed',
                'action' => 'status_updated',
                'new_status' => $new_status,
                'has_qr_code' => !empty($qr_code)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao processar atualização de conexão: " . $e->getMessage());
            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    /**
     * Processa mensagem enviada
     */
    public function handleMessageSent($webhook_data, $instance_settings) {
        try {
            $message_data = $this->extractMessageData($webhook_data);
            
            if (!$message_data || !$message_data['fromMe']) {
                return ['status' => 'ignored', 'reason' => 'Não é mensagem enviada por nós'];
            }
            
            // Atualizar status da mensagem no banco
            $query = "UPDATE messages SET status = 'sent' WHERE message_id = :message_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':message_id' => $message_data['id']]);
            
            return [
                'status' => 'processed',
                'action' => 'message_status_updated',
                'message_id' => $message_data['id']
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao processar mensagem enviada: " . $e->getMessage());
            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    /**
     * Processa eventos genéricos
     */
    public function handleGenericEvent($webhook_data, $instance_settings) {
        // Log do evento para análise futura
        $query = "INSERT INTO audit_logs (user_id, user_type, action, resource_type, details, created_at) 
                  VALUES (NULL, 'system', 'webhook_received', 'evolution_api', :details, NOW())";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':details' => json_encode($webhook_data)]);
        
        return [
            'status' => 'logged',
            'action' => 'generic_event_logged'
        ];
    }

    /**
     * Extrai dados da mensagem do webhook
     */
    private function extractMessageData($webhook_data) {
        // Diferentes formatos possíveis da Evolution API
        $message = null;
        
        if (isset($webhook_data['data']['messages'][0])) {
            $message = $webhook_data['data']['messages'][0];
        } elseif (isset($webhook_data['data'])) {
            $message = $webhook_data['data'];
        } elseif (isset($webhook_data['message'])) {
            $message = $webhook_data['message'];
        }
        
        if (!$message) {
            return null;
        }
        
        // Extrair informações padronizadas
        return [
            'id' => $message['key']['id'] ?? $message['id'] ?? uniqid('msg_'),
            'from' => $message['key']['remoteJid'] ?? $message['from'] ?? '',
            'to' => $message['key']['participant'] ?? $message['to'] ?? '',
            'body' => $message['message']['conversation'] ?? 
                     $message['message']['extendedTextMessage']['text'] ?? 
                     $message['body'] ?? '',
            'type' => $this->getMessageType($message),
            'timestamp' => $message['messageTimestamp'] ?? $message['timestamp'] ?? time(),
            'pushName' => $message['pushName'] ?? $message['notifyName'] ?? 'Usuário',
            'fromMe' => $message['key']['fromMe'] ?? $message['fromMe'] ?? false,
            'isGroup' => strpos($message['key']['remoteJid'] ?? $message['from'] ?? '', '@g.us') !== false
        ];
    }

    /**
     * Determina o tipo da mensagem
     */
    private function getMessageType($message) {
        if (isset($message['message']['conversation'])) {
            return 'text';
        } elseif (isset($message['message']['imageMessage'])) {
            return 'image';
        } elseif (isset($message['message']['audioMessage'])) {
            return 'audio';
        } elseif (isset($message['message']['videoMessage'])) {
            return 'video';
        } elseif (isset($message['message']['documentMessage'])) {
            return 'document';
        } else {
            return 'text';
        }
    }

    /**
     * Encaminha mensagem para N8N
     */
    private function forwardToN8N($message_data, $instance_settings) {
        $payload = [
            'instanceId' => $instance_settings['instance_id'],
            'id' => $message_data['id'],
            'from' => $message_data['from'],
            'to' => $message_data['to'],
            'body' => $message_data['body'],
            'type' => $message_data['type'],
            'timestamp' => $message_data['timestamp'],
            'pushName' => $message_data['pushName'],
            'fromMe' => $message_data['fromMe'],
            'isGroup' => $message_data['isGroup']
        ];
        
        $this->sendWebhook($this->n8n_webhook_url, $payload);
    }

    /**
     * Notifica agentes humanos sobre nova mensagem
     */
    private function notifyHumanAgents($message_data, $instance_settings, $conversation) {
        // Aqui você pode implementar notificações via:
        // - WebSocket para a plataforma cliente
        // - Email
        // - Push notifications
        // - Slack/Discord
        
        $notification_data = [
            'type' => 'new_message',
            'instance_id' => $instance_settings['instance_id'],
            'instance_name' => $instance_settings['name'],
            'conversation_id' => $conversation['id'],
            'from' => $message_data['from'],
            'contact_name' => $message_data['pushName'],
            'message' => $message_data['body'],
            'timestamp' => $message_data['timestamp']
        ];
        
        // Log da notificação
        error_log("Notificação para agentes humanos: " . json_encode($notification_data));
        
        // Aqui você implementaria o envio real da notificação
        // Por exemplo, via WebSocket ou endpoint da plataforma cliente
    }

    /**
     * Notifica sobre atualização de QR Code
     */
    private function notifyQRCodeUpdate($instance_settings, $qr_code) {
        $notification_data = [
            'type' => 'qr_code_update',
            'instance_id' => $instance_settings['instance_id'],
            'instance_name' => $instance_settings['name'],
            'qr_code' => $qr_code,
            'timestamp' => time()
        ];
        
        // Log da atualização
        error_log("QR Code atualizado para instância: " . $instance_settings['instance_id']);
        
        // Aqui você implementaria o envio da notificação para a plataforma cliente
    }

    /**
     * Envia webhook para URL especificada
     */
    private function sendWebhook($url, $payload) {
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
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false // Para desenvolvimento
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            error_log("Erro ao enviar webhook: " . $curl_error);
            return false;
        }
        
        if ($http_code !== 200) {
            error_log("Webhook retornou HTTP $http_code: " . $response);
            return false;
        }
        
        return true;
    }
}
?>
