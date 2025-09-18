<?php
/**
 * Script de Testes Abrangentes da API
 * 
 * Este script testa todos os endpoints da plataforma WhatsApp AI
 * para garantir que estão funcionando corretamente.
 */

// Configurações de teste
$base_url = 'http://localhost';
$test_instance_id = 'test_instance_001';
$test_contact = '5511999999999@c.us';

// Cores para output
$colors = [
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'reset' => "\033[0m"
];

echo $colors['blue'] . "=== INICIANDO TESTES DA API WHATSAPP AI PLATFORM ===" . $colors['reset'] . "\n\n";

$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

/**
 * Função para fazer requisições HTTP
 */
function makeRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    if ($method === 'POST' || $method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => !$curl_error && $http_code >= 200 && $http_code < 300,
        'http_code' => $http_code,
        'response' => $response,
        'data' => json_decode($response, true),
        'error' => $curl_error
    ];
}

/**
 * Função para executar um teste
 */
function runTest($test_name, $url, $method = 'GET', $data = null, $expected_keys = []) {
    global $total_tests, $passed_tests, $failed_tests, $colors;
    
    $total_tests++;
    echo "Teste {$total_tests}: {$test_name}... ";
    
    $result = makeRequest($url, $method, $data);
    
    $passed = $result['success'];
    
    // Verificar chaves esperadas na resposta
    if ($passed && !empty($expected_keys) && is_array($result['data'])) {
        foreach ($expected_keys as $key) {
            if (!array_key_exists($key, $result['data'])) {
                $passed = false;
                break;
            }
        }
    }
    
    if ($passed) {
        echo $colors['green'] . "PASSOU" . $colors['reset'] . "\n";
        $passed_tests++;
    } else {
        echo $colors['red'] . "FALHOU" . $colors['reset'];
        echo " (HTTP {$result['http_code']})";
        if ($result['error']) {
            echo " - Erro: {$result['error']}";
        }
        echo "\n";
        $failed_tests++;
    }
    
    return $result;
}

// ===== TESTES =====

echo $colors['yellow'] . "1. Testando Endpoints Básicos" . $colors['reset'] . "\n";

// Teste 1: Página de teste básica
runTest(
    "Página de teste básica",
    "$base_url/test.php",
    'GET',
    null,
    ['success', 'message', 'php_version']
);

// Teste 2: Simulador de webhooks - listar simulações
runTest(
    "Simulador de webhooks - listar simulações",
    "$base_url/api/endpoints/webhook-simulator.php",
    'GET',
    null,
    ['success', 'simulations']
);

echo "\n" . $colors['yellow'] . "2. Testando Configurações" . $colors['reset'] . "\n";

// Teste 3: Obter configurações de instância
runTest(
    "Obter configurações de instância",
    "$base_url/api/settings/instance/$test_instance_id/conversation/$test_contact",
    'GET',
    null,
    ['success', 'instance_status']
);

// Teste 4: Atualizar auto-resposta global
runTest(
    "Atualizar auto-resposta global",
    "$base_url/api/settings/instance/$test_instance_id/auto-reply",
    'PUT',
    ['auto_reply_global' => true],
    ['success']
);

// Teste 5: Obter estatísticas da instância
runTest(
    "Obter estatísticas da instância",
    "$base_url/api/settings/instance/$test_instance_id/stats",
    'GET',
    null,
    ['success', 'stats']
);

echo "\n" . $colors['yellow'] . "3. Testando Simulações de Webhook" . $colors['reset'] . "\n";

// Teste 6: Simular recebimento de mensagem
runTest(
    "Simular recebimento de mensagem",
    "$base_url/api/endpoints/webhook-simulator.php",
    'POST',
    [
        'simulation_type' => 'message_received',
        'instance_id' => $test_instance_id,
        'from' => $test_contact,
        'message' => 'Teste de mensagem automática',
        'contact_name' => 'Cliente Teste'
    ],
    ['success', 'result']
);

// Teste 7: Simular atualização de conexão
runTest(
    "Simular atualização de conexão",
    "$base_url/api/endpoints/webhook-simulator.php",
    'POST',
    [
        'simulation_type' => 'connection_update',
        'instance_id' => $test_instance_id,
        'status' => 'connected'
    ],
    ['success', 'result']
);

// Teste 8: Simular QR Code
runTest(
    "Simular atualização de QR Code",
    "$base_url/api/endpoints/webhook-simulator.php",
    'POST',
    [
        'simulation_type' => 'qr_code_update',
        'instance_id' => $test_instance_id,
        'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
    ],
    ['success', 'result']
);

echo "\n" . $colors['yellow'] . "4. Testando Sistema de IA" . $colors['reset'] . "\n";

// Teste 9: Gerar resposta da IA (sem chave real do Gemini, deve falhar graciosamente)
$ai_result = runTest(
    "Gerar resposta da IA",
    "$base_url/api/ai/generate-response",
    'POST',
    [
        'instanceId' => $test_instance_id,
        'from' => $test_contact,
        'message' => 'Olá, preciso de informações sobre os planos'
    ],
    ['success'] // Pode falhar devido à falta da chave do Gemini, mas deve retornar estrutura correta
);

echo "\n" . $colors['yellow'] . "5. Testando Sistema de Transferência Humana" . $colors['reset'] . "\n";

// Teste 10: Transferir para atendimento humano
runTest(
    "Transferir para atendimento humano",
    "$base_url/api/human-handoff",
    'POST',
    [
        'instanceId' => $test_instance_id,
        'from' => $test_contact,
        'last_message' => 'Preciso falar com um atendente',
        'reason' => 'Solicitação do cliente',
        'priority' => 'normal'
    ],
    ['success'] // Pode falhar se não houver agentes cadastrados, mas deve processar
);

echo "\n" . $colors['yellow'] . "6. Testando Fluxo Completo de IA" . $colors['reset'] . "\n";

// Teste 11: Teste completo de IA via simulador
runTest(
    "Teste completo de fluxo de IA",
    "$base_url/api/endpoints/webhook-simulator.php",
    'POST',
    [
        'simulation_type' => 'ai_response_test',
        'instance_id' => $test_instance_id,
        'from' => $test_contact,
        'message' => 'Quais são os planos disponíveis?',
        'contact_name' => 'Cliente Interessado'
    ],
    ['success', 'result']
);

// ===== RESULTADOS =====

echo "\n" . $colors['blue'] . "=== RESULTADOS DOS TESTES ===" . $colors['reset'] . "\n";
echo "Total de testes: $total_tests\n";
echo $colors['green'] . "Testes aprovados: $passed_tests" . $colors['reset'] . "\n";
echo $colors['red'] . "Testes falharam: $failed_tests" . $colors['reset'] . "\n";

$success_rate = round(($passed_tests / $total_tests) * 100, 2);
echo "Taxa de sucesso: $success_rate%\n";

if ($success_rate >= 80) {
    echo $colors['green'] . "✅ Sistema está funcionando adequadamente!" . $colors['reset'] . "\n";
} elseif ($success_rate >= 60) {
    echo $colors['yellow'] . "⚠️  Sistema tem alguns problemas, mas está funcional." . $colors['reset'] . "\n";
} else {
    echo $colors['red'] . "❌ Sistema tem problemas significativos que precisam ser corrigidos." . $colors['reset'] . "\n";
}

echo "\n" . $colors['blue'] . "=== NOTAS IMPORTANTES ===" . $colors['reset'] . "\n";
echo "• Para funcionamento completo da IA, configure a chave do Google Gemini em /config/database.php\n";
echo "• Para funcionamento completo da Evolution API, configure a chave em /config/database.php\n";
echo "• Para testes de transferência humana, cadastre agentes no banco de dados\n";
echo "• Para produção, configure HTTPS e domínio real\n";

echo "\n" . $colors['green'] . "Testes concluídos!" . $colors['reset'] . "\n";
?>
