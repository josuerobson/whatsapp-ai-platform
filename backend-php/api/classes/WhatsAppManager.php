<?php
/**
 * Classe para gerenciar conversas e mensagens do WhatsApp
 * 
 * Esta classe gerencia o armazenamento e recuperação de conversas,
 * mensagens e configurações das instâncias do WhatsApp.
 */

require_once __DIR__ . '/../../config/database.php';

class WhatsAppManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Obtém configurações de uma instância
     */
    public function getInstanceSettings($instance_id) {
        try {
            $query = "SELECT wi.*, c.name as client_name, c.email as client_email 
                      FROM whatsapp_instances wi 
                      JOIN clients c ON wi.client_id = c.id 
                      WHERE wi.instance_id = :instance_id AND c.status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':instance_id' => $instance_id]);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Erro ao obter configurações da instância: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém ou cria uma conversa
     */
    public function getOrCreateConversation($instance_id, $contact_number, $contact_name = null) {
        try {
            // Tentar obter conversa existente
            $query = "SELECT * FROM conversations WHERE instance_id = :instance_id AND contact_number = :contact_number";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':instance_id' => $instance_id,
                ':contact_number' => $contact_number
            ]);
            
            $conversation = $stmt->fetch();
            
            if ($conversation) {
                return $conversation;
            }
            
            // Criar nova conversa
            $query = "INSERT INTO conversations (instance_id, contact_number, contact_name, status, created_at) 
                      VALUES (:instance_id, :contact_number, :contact_name, 'active', NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':instance_id' => $instance_id,
                ':contact_number' => $contact_number,
                ':contact_name' => $contact_name
            ]);
            
            $conversation_id = $this->db->lastInsertId();
            
            // Retornar a conversa criada
            $query = "SELECT * FROM conversations WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $conversation_id]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Erro ao obter/criar conversa: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Salva uma mensagem
     */
    public function saveMessage($conversation_id, $message_id, $sender_type, $sender_number, $content, $message_type = 'text', $media_url = null, $is_from_me = false) {
        try {
            $query = "INSERT INTO messages (conversation_id, message_id, sender_type, sender_number, content, message_type, media_url, is_from_me, timestamp, created_at) 
                      VALUES (:conversation_id, :message_id, :sender_type, :sender_number, :content, :message_type, :media_url, :is_from_me, NOW(), NOW())";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':conversation_id' => $conversation_id,
                ':message_id' => $message_id ?: uniqid('msg_'),
                ':sender_type' => $sender_type,
                ':sender_number' => $sender_number,
                ':content' => $content,
                ':message_type' => $message_type,
                ':media_url' => $media_url,
                ':is_from_me' => $is_from_me ? 1 : 0
            ]);
            
            if ($result) {
                // Atualizar última mensagem da conversa
                $this->updateConversationLastMessage($conversation_id, $content);
                return $this->db->lastInsertId();
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao salvar mensagem: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza a última mensagem da conversa
     */
    private function updateConversationLastMessage($conversation_id, $last_message) {
        try {
            $query = "UPDATE conversations SET last_message = :last_message, last_message_timestamp = NOW(), updated_at = NOW() 
                      WHERE id = :conversation_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':conversation_id' => $conversation_id,
                ':last_message' => substr($last_message, 0, 255) // Limitar tamanho
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar última mensagem: " . $e->getMessage());
        }
    }

    /**
     * Obtém histórico de uma conversa
     */
    public function getConversationHistory($instance_id, $contact_number, $limit = 10) {
        try {
            $query = "SELECT m.* FROM messages m 
                      JOIN conversations c ON m.conversation_id = c.id 
                      WHERE c.instance_id = :instance_id AND c.contact_number = :contact_number 
                      ORDER BY m.timestamp DESC LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':instance_id', $instance_id, PDO::PARAM_INT);
            $stmt->bindValue(':contact_number', $contact_number, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll();
            
            // Retornar em ordem cronológica (mais antiga primeiro)
            return array_reverse($messages);
            
        } catch (Exception $e) {
            error_log("Erro ao obter histórico da conversa: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém configurações de auto-resposta de uma conversa
     */
    public function getConversationAutoReplySettings($instance_id, $contact_number) {
        try {
            $query = "SELECT wi.auto_reply_global, c.auto_reply_enabled 
                      FROM whatsapp_instances wi 
                      LEFT JOIN conversations c ON wi.id = c.instance_id AND c.contact_number = :contact_number 
                      WHERE wi.id = :instance_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':instance_id' => $instance_id,
                ':contact_number' => $contact_number
            ]);
            
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'auto_reply_global' => (bool)$result['auto_reply_global'],
                    'auto_reply_conversation' => $result['auto_reply_enabled'] !== null ? (bool)$result['auto_reply_enabled'] : true
                ];
            }
            
            return [
                'auto_reply_global' => false,
                'auto_reply_conversation' => false
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter configurações de auto-resposta: " . $e->getMessage());
            return [
                'auto_reply_global' => false,
                'auto_reply_conversation' => false
            ];
        }
    }

    /**
     * Atualiza configuração de auto-resposta global de uma instância
     */
    public function updateGlobalAutoReply($instance_id, $auto_reply_status) {
        try {
            $query = "UPDATE whatsapp_instances SET auto_reply_global = :auto_reply_global, updated_at = NOW() 
                      WHERE instance_id = :instance_id";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':instance_id' => $instance_id,
                ':auto_reply_global' => $auto_reply_status ? 1 : 0
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar auto-resposta global: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza configuração de auto-resposta de uma conversa específica
     */
    public function updateConversationAutoReply($instance_id, $contact_number, $auto_reply_status) {
        try {
            // Primeiro, garantir que a conversa existe
            $conversation = $this->getOrCreateConversation($instance_id, $contact_number);
            
            if (!$conversation) {
                return false;
            }
            
            $query = "UPDATE conversations SET auto_reply_enabled = :auto_reply_enabled, updated_at = NOW() 
                      WHERE id = :conversation_id";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':conversation_id' => $conversation['id'],
                ':auto_reply_enabled' => $auto_reply_status ? 1 : 0
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar auto-resposta da conversa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém lista de conversas de uma instância
     */
    public function getConversations($instance_id, $limit = 50, $offset = 0) {
        try {
            $query = "SELECT c.*, 
                             (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_type = 'user' AND m.status != 'read') as unread_count
                      FROM conversations c 
                      WHERE c.instance_id = :instance_id AND c.status = 'active'
                      ORDER BY c.last_message_timestamp DESC 
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':instance_id', $instance_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Erro ao obter conversas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marca mensagens como lidas
     */
    public function markMessagesAsRead($conversation_id, $sender_type = 'user') {
        try {
            $query = "UPDATE messages SET status = 'read' 
                      WHERE conversation_id = :conversation_id AND sender_type = :sender_type AND status != 'read'";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':conversation_id' => $conversation_id,
                ':sender_type' => $sender_type
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao marcar mensagens como lidas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém estatísticas de uma instância
     */
    public function getInstanceStats($instance_id, $days = 30) {
        try {
            $date_from = date('Y-m-d H:i:s', strtotime("-$days days"));
            
            // Total de conversas
            $query = "SELECT COUNT(*) as total_conversations FROM conversations WHERE instance_id = :instance_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':instance_id' => $instance_id]);
            $total_conversations = $stmt->fetch()['total_conversations'];
            
            // Mensagens enviadas pela IA
            $query = "SELECT COUNT(*) as ai_messages FROM messages m 
                      JOIN conversations c ON m.conversation_id = c.id 
                      WHERE c.instance_id = :instance_id AND m.sender_type = 'ai' AND m.created_at >= :date_from";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':instance_id' => $instance_id, ':date_from' => $date_from]);
            $ai_messages = $stmt->fetch()['ai_messages'];
            
            // Mensagens recebidas
            $query = "SELECT COUNT(*) as user_messages FROM messages m 
                      JOIN conversations c ON m.conversation_id = c.id 
                      WHERE c.instance_id = :instance_id AND m.sender_type = 'user' AND m.created_at >= :date_from";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':instance_id' => $instance_id, ':date_from' => $date_from]);
            $user_messages = $stmt->fetch()['user_messages'];
            
            // Tempo médio de resposta da IA
            $query = "SELECT AVG(processing_time_ms) as avg_response_time FROM ai_logs 
                      WHERE instance_id = :instance_id AND created_at >= :date_from";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':instance_id' => $instance_id, ':date_from' => $date_from]);
            $avg_response_time = $stmt->fetch()['avg_response_time'];
            
            return [
                'total_conversations' => (int)$total_conversations,
                'ai_messages_sent' => (int)$ai_messages,
                'user_messages_received' => (int)$user_messages,
                'avg_response_time_ms' => round($avg_response_time, 2),
                'period_days' => $days
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            return null;
        }
    }
}
?>
