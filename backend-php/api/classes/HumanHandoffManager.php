<?php
/**
 * Classe para gerenciar transferências para atendimento humano
 * 
 * Esta classe gerencia o processo de transferência de conversas
 * da IA para agentes humanos, incluindo criação de tickets,
 * notificações e controle de fila.
 */

require_once __DIR__ . '/../../config/database.php';

class HumanHandoffManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->createHandoffTablesIfNotExists();
    }

    /**
     * Cria tabelas necessárias para o sistema de transferência
     */
    private function createHandoffTablesIfNotExists() {
        try {
            // Tabela de tickets de atendimento
            $query = "CREATE TABLE IF NOT EXISTS handoff_tickets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                instance_id INT NOT NULL,
                conversation_id INT NOT NULL,
                contact_number VARCHAR(50) NOT NULL,
                last_message TEXT,
                reason VARCHAR(255),
                priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
                status ENUM('pending', 'assigned', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
                assigned_agent_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                assigned_at TIMESTAMP NULL,
                resolved_at TIMESTAMP NULL,
                FOREIGN KEY (instance_id) REFERENCES whatsapp_instances(id) ON DELETE CASCADE,
                FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
                INDEX idx_status (status),
                INDEX idx_priority (priority),
                INDEX idx_created_at (created_at)
            )";
            $this->db->exec($query);

            // Tabela de agentes (usuários que podem atender)
            $query = "CREATE TABLE IF NOT EXISTS agents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                status ENUM('online', 'away', 'busy', 'offline') DEFAULT 'offline',
                max_concurrent_tickets INT DEFAULT 5,
                current_tickets INT DEFAULT 0,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
                INDEX idx_status (status),
                INDEX idx_client_id (client_id)
            )";
            $this->db->exec($query);

            // Tabela de mensagens de agentes
            $query = "CREATE TABLE IF NOT EXISTS agent_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT NOT NULL,
                agent_id INT NOT NULL,
                message TEXT NOT NULL,
                message_type ENUM('text', 'note', 'system') DEFAULT 'text',
                is_sent_to_customer BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ticket_id) REFERENCES handoff_tickets(id) ON DELETE CASCADE,
                FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
                INDEX idx_ticket_id (ticket_id),
                INDEX idx_created_at (created_at)
            )";
            $this->db->exec($query);

        } catch (Exception $e) {
            error_log("Erro ao criar tabelas de handoff: " . $e->getMessage());
        }
    }

    /**
     * Cria um ticket de transferência para atendimento humano
     */
    public function createHandoffTicket($instance_id, $conversation_id, $contact_number, $last_message, $reason, $priority = 'normal') {
        try {
            // Verificar se já existe um ticket pendente para esta conversa
            $query = "SELECT id FROM handoff_tickets 
                      WHERE conversation_id = :conversation_id AND status IN ('pending', 'assigned', 'in_progress')";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':conversation_id' => $conversation_id]);
            
            if ($stmt->fetch()) {
                throw new Exception('Já existe um ticket pendente para esta conversa');
            }
            
            // Criar novo ticket
            $query = "INSERT INTO handoff_tickets (instance_id, conversation_id, contact_number, last_message, reason, priority, created_at) 
                      VALUES (:instance_id, :conversation_id, :contact_number, :last_message, :reason, :priority, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':instance_id' => $instance_id,
                ':conversation_id' => $conversation_id,
                ':contact_number' => $contact_number,
                ':last_message' => $last_message,
                ':reason' => $reason,
                ':priority' => $priority
            ]);
            
            $ticket_id = $this->db->lastInsertId();
            
            // Retornar o ticket criado
            $query = "SELECT * FROM handoff_tickets WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $ticket_id]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Erro ao criar ticket de handoff: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Notifica agentes disponíveis sobre novo ticket
     */
    public function notifyAvailableAgents($ticket, $instance_settings) {
        try {
            // Buscar agentes online do cliente
            $query = "SELECT a.* FROM agents a 
                      WHERE a.client_id = :client_id 
                      AND a.status IN ('online', 'away') 
                      AND a.current_tickets < a.max_concurrent_tickets
                      ORDER BY a.current_tickets ASC, a.last_activity DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':client_id' => $instance_settings['client_id']]);
            
            $available_agents = $stmt->fetchAll();
            $agents_notified = 0;
            
            foreach ($available_agents as $agent) {
                // Enviar notificação para o agente
                $notification_sent = $this->sendAgentNotification($agent, $ticket, $instance_settings);
                
                if ($notification_sent) {
                    $agents_notified++;
                }
            }
            
            // Calcular tempo estimado de espera baseado na fila
            $estimated_wait_time = $this->calculateEstimatedWaitTime($instance_settings['client_id'], $ticket['priority']);
            
            return [
                'agents_notified' => $agents_notified,
                'available_agents' => count($available_agents),
                'estimated_wait_time' => $estimated_wait_time
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao notificar agentes: " . $e->getMessage());
            return [
                'agents_notified' => 0,
                'available_agents' => 0,
                'estimated_wait_time' => 'Indisponível'
            ];
        }
    }

    /**
     * Envia notificação para um agente específico
     */
    private function sendAgentNotification($agent, $ticket, $instance_settings) {
        // Aqui você implementaria o envio real da notificação
        // Pode ser via WebSocket, email, push notification, etc.
        
        $notification_data = [
            'type' => 'new_ticket',
            'ticket_id' => $ticket['id'],
            'instance_name' => $instance_settings['name'],
            'contact_number' => $ticket['contact_number'],
            'last_message' => $ticket['last_message'],
            'reason' => $ticket['reason'],
            'priority' => $ticket['priority'],
            'created_at' => $ticket['created_at']
        ];
        
        // Log da notificação
        error_log("Notificação enviada para agente {$agent['id']}: " . json_encode($notification_data));
        
        // Simular envio bem-sucedido
        return true;
    }

    /**
     * Calcula tempo estimado de espera
     */
    private function calculateEstimatedWaitTime($client_id, $priority) {
        try {
            // Contar tickets pendentes por prioridade
            $query = "SELECT priority, COUNT(*) as count FROM handoff_tickets ht
                      JOIN whatsapp_instances wi ON ht.instance_id = wi.id
                      WHERE wi.client_id = :client_id AND ht.status = 'pending'
                      GROUP BY priority";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':client_id' => $client_id]);
            
            $queue_counts = [];
            while ($row = $stmt->fetch()) {
                $queue_counts[$row['priority']] = $row['count'];
            }
            
            // Prioridades em ordem (urgent > high > normal > low)
            $priority_order = ['urgent', 'high', 'normal', 'low'];
            $current_priority_index = array_search($priority, $priority_order);
            
            // Calcular tickets à frente na fila
            $tickets_ahead = 0;
            for ($i = 0; $i < $current_priority_index; $i++) {
                $tickets_ahead += $queue_counts[$priority_order[$i]] ?? 0;
            }
            
            // Tempo médio de atendimento por ticket (em minutos)
            $avg_handling_time = 15;
            
            // Calcular tempo estimado
            $estimated_minutes = $tickets_ahead * $avg_handling_time;
            
            if ($estimated_minutes <= 0) {
                return 'Imediato';
            } elseif ($estimated_minutes < 60) {
                return $estimated_minutes . ' minutos';
            } else {
                $hours = floor($estimated_minutes / 60);
                $minutes = $estimated_minutes % 60;
                return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'min' : '');
            }
            
        } catch (Exception $e) {
            error_log("Erro ao calcular tempo de espera: " . $e->getMessage());
            return 'Indisponível';
        }
    }

    /**
     * Obtém mensagem automática de transferência
     */
    public function getHandoffMessage($reason, $priority) {
        $priority_messages = [
            'urgent' => 'Entendo que sua solicitação é urgente. Você foi transferido para nossa equipe de atendimento prioritário. Um agente especializado irá atendê-lo em instantes.',
            'high' => 'Sua solicitação foi transferida para nossa equipe de atendimento. Um agente irá atendê-lo em breve com prioridade alta.',
            'normal' => 'Obrigado por entrar em contato! Você foi transferido para um de nossos agentes humanos que poderá ajudá-lo melhor. Aguarde um momento, por favor.',
            'low' => 'Sua mensagem foi recebida e transferida para nossa equipe. Um agente irá atendê-lo assim que possível.'
        ];
        
        $base_message = $priority_messages[$priority] ?? $priority_messages['normal'];
        
        // Adicionar informação específica baseada no motivo
        $reason_additions = [
            'Palavra-chave de transferência detectada' => ' Detectamos que você precisa de atendimento especializado.',
            'Solicitação do cliente' => ' Conforme solicitado, você está sendo atendido por um agente humano.',
            'Erro da IA' => ' Nosso assistente virtual encontrou dificuldades para ajudá-lo adequadamente.',
            'Fora do horário' => ' Embora estejamos fora do horário comercial, sua solicitação foi registrada.'
        ];
        
        if (isset($reason_additions[$reason])) {
            $base_message .= $reason_additions[$reason];
        }
        
        return $base_message;
    }

    /**
     * Atribui ticket a um agente
     */
    public function assignTicketToAgent($ticket_id, $agent_id) {
        try {
            // Verificar se o agente pode receber mais tickets
            $query = "SELECT current_tickets, max_concurrent_tickets FROM agents WHERE id = :agent_id AND status IN ('online', 'away')";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':agent_id' => $agent_id]);
            
            $agent = $stmt->fetch();
            if (!$agent || $agent['current_tickets'] >= $agent['max_concurrent_tickets']) {
                return false;
            }
            
            // Atribuir ticket
            $query = "UPDATE handoff_tickets SET assigned_agent_id = :agent_id, status = 'assigned', assigned_at = NOW() 
                      WHERE id = :ticket_id AND status = 'pending'";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':agent_id' => $agent_id,
                ':ticket_id' => $ticket_id
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Incrementar contador de tickets do agente
                $query = "UPDATE agents SET current_tickets = current_tickets + 1 WHERE id = :agent_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':agent_id' => $agent_id]);
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao atribuir ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resolve um ticket
     */
    public function resolveTicket($ticket_id, $agent_id, $resolution_note = '') {
        try {
            // Atualizar status do ticket
            $query = "UPDATE handoff_tickets SET status = 'resolved', resolved_at = NOW() 
                      WHERE id = :ticket_id AND assigned_agent_id = :agent_id";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':ticket_id' => $ticket_id,
                ':agent_id' => $agent_id
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Decrementar contador de tickets do agente
                $query = "UPDATE agents SET current_tickets = current_tickets - 1 WHERE id = :agent_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':agent_id' => $agent_id]);
                
                // Salvar nota de resolução se fornecida
                if (!empty($resolution_note)) {
                    $query = "INSERT INTO agent_messages (ticket_id, agent_id, message, message_type, is_sent_to_customer, created_at) 
                              VALUES (:ticket_id, :agent_id, :message, 'note', FALSE, NOW())";
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        ':ticket_id' => $ticket_id,
                        ':agent_id' => $agent_id,
                        ':message' => $resolution_note
                    ]);
                }
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao resolver ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém tickets pendentes
     */
    public function getPendingTickets($client_id, $limit = 50) {
        try {
            $query = "SELECT ht.*, wi.name as instance_name, c.contact_name 
                      FROM handoff_tickets ht
                      JOIN whatsapp_instances wi ON ht.instance_id = wi.id
                      LEFT JOIN conversations c ON ht.conversation_id = c.id
                      WHERE wi.client_id = :client_id AND ht.status = 'pending'
                      ORDER BY 
                        CASE ht.priority 
                          WHEN 'urgent' THEN 1 
                          WHEN 'high' THEN 2 
                          WHEN 'normal' THEN 3 
                          WHEN 'low' THEN 4 
                        END,
                        ht.created_at ASC
                      LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':client_id', $client_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Erro ao obter tickets pendentes: " . $e->getMessage());
            return [];
        }
    }
}
?>
