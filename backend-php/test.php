<?php
/**
 * Página de teste para verificar o funcionamento do backend
 */

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Backend PHP da Plataforma WhatsApp AI está funcionando!',
    'php_version' => PHP_VERSION,
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
    ]
]);
?>
