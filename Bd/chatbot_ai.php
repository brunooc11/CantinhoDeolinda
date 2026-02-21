<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo nao permitido.']);
    exit();
}

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Corpo JSON invalido.']);
    exit();
}

$message = trim((string)($input['message'] ?? ''));
$history = $input['history'] ?? [];

if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mensagem vazia.']);
    exit();
}

if (mb_strlen($message) > 1200) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensagem demasiado longa.']);
    exit();
}

$env = [];
$candidateEnvPaths = [
    __DIR__ . '/../Seguranca/config.env',
    __DIR__ . '/../seguranca/config.env',
    __DIR__ . '/../../Seguranca/config.env',
    __DIR__ . '/../../seguranca/config.env'
];

foreach ($candidateEnvPaths as $candidatePath) {
    if (is_file($candidatePath) && is_readable($candidatePath)) {
        $parsed = parse_ini_file($candidatePath, false, INI_SCANNER_RAW);
        if (is_array($parsed)) {
            $env = $parsed;
            break;
        }
    }
}

$provider = strtolower(trim((string)($env['AI_PROVIDER'] ?? getenv('AI_PROVIDER') ?? '')));
$openAiKey = trim((string)($env['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?? ''));
$groqKey = trim((string)($env['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?? ''));
$apiKey = $openAiKey !== '' ? $openAiKey : $groqKey;
$model = trim((string)($env['OPENAI_MODEL'] ?? getenv('OPENAI_MODEL') ?? ''));
$groqModel = trim((string)($env['GROQ_MODEL'] ?? getenv('GROQ_MODEL') ?? ''));
$systemPrompt = trim((string)($env['CHATBOT_SYSTEM_PROMPT'] ?? getenv('CHATBOT_SYSTEM_PROMPT') ?? ''));
$systemPromptFile = trim((string)($env['CHATBOT_SYSTEM_PROMPT_FILE'] ?? getenv('CHATBOT_SYSTEM_PROMPT_FILE') ?? ''));

if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Nenhuma chave de IA configurada (OPENAI_API_KEY ou GROQ_API_KEY).']);
    exit();
}

if ($provider === '') {
    $provider = (stripos($apiKey, 'gsk_') === 0) ? 'groq' : 'openai';
}

if ($provider === 'groq') {
    if ($groqModel !== '') {
        $model = $groqModel;
    }
    if ($model === '' || stripos($model, 'gpt-') === 0) {
        $model = 'llama-3.1-8b-instant';
    }
} else {
    if ($model === '') {
        $model = 'gpt-4o-mini';
    }
}

if ($systemPromptFile !== '') {
    $promptCandidates = [$systemPromptFile];
    if (!preg_match('/^[A-Za-z]:\\\\|^\//', $systemPromptFile)) {
        $trimmed = ltrim($systemPromptFile, '/\\');
        $promptCandidates[] = __DIR__ . '/../' . $trimmed;
        $promptCandidates[] = __DIR__ . '/../../' . $trimmed;
    }

    foreach ($promptCandidates as $promptPath) {
        if (is_file($promptPath) && is_readable($promptPath)) {
            $promptFromFile = trim((string)file_get_contents($promptPath));
            if ($promptFromFile !== '') {
                $systemPrompt = $promptFromFile;
                break;
            }
        }
    }
}

if ($systemPrompt === '') {
    $systemPrompt = 'Es um assistente do restaurante Cantinho Deolinda. Responde em portugues de forma clara, educada e objetiva.';
}

$messages = [
    [
        'role' => 'system',
        'content' => $systemPrompt
    ]
];

if (is_array($history)) {
    foreach ($history as $item) {
        if (!is_array($item)) {
            continue;
        }

        $role = $item['role'] ?? '';
        $content = trim((string)($item['content'] ?? ''));

        if ($content === '') {
            continue;
        }

        if ($role !== 'user' && $role !== 'assistant') {
            continue;
        }

        $messages[] = [
            'role' => $role,
            'content' => $content
        ];
    }
}

$messages[] = [
    'role' => 'user',
    'content' => $message
];

$payload = [
    'model' => $model,
    'messages' => $messages,
    'temperature' => 0.4,
    'max_tokens' => 350
];

$payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($payloadJson === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao gerar JSON do pedido: ' . json_last_error_msg()]);
    exit();
}

$apiUrl = $provider === 'groq'
    ? 'https://api.groq.com/openai/v1/chat/completions'
    : 'https://api.openai.com/v1/chat/completions';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Falha de ligacao ao servico de IA: ' . $curlError]);
    exit();
}

$decoded = json_decode($result, true);
if (!is_array($decoded)) {
    $decoded = [];
}

if ($statusCode < 200 || $statusCode >= 300) {
    $rawSnippet = trim(substr((string)$result, 0, 300));
    $apiError = $decoded['error']['message'] ?? ($rawSnippet !== '' ? $rawSnippet : 'Erro desconhecido na API de IA.');
    http_response_code(502);
    echo json_encode(['error' => $apiError]);
    exit();
}

$reply = trim((string)($decoded['choices'][0]['message']['content'] ?? ''));

if ($reply === '') {
    http_response_code(502);
    echo json_encode(['error' => 'A IA nao devolveu resposta.']);
    exit();
}

echo json_encode(['reply' => $reply]);
