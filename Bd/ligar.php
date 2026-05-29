<?php
date_default_timezone_set('Europe/Lisbon');

$env = [];
$envPaths = [
    __DIR__ . '/../Seguranca/config.env',
    __DIR__ . '/../seguranca/config.env',
];

foreach ($envPaths as $envPath) {
    if (is_file($envPath) && is_readable($envPath)) {
        $parsedEnv = parse_ini_file($envPath, false, INI_SCANNER_RAW);
        if (is_array($parsedEnv)) {
            $env = $parsedEnv;
            break;
        }
    }
}

$dbHost = trim((string)($env['DB_HOST'] ?? getenv('DB_HOST') ?? ''));
$dbName = trim((string)($env['DB_NAME'] ?? getenv('DB_NAME') ?? ''));
$dbUser = trim((string)($env['DB_USER'] ?? getenv('DB_USER') ?? ''));
$dbPass = (string)($env['DB_PASS'] ?? getenv('DB_PASS') ?? '');
$dbPort = (int)($env['DB_PORT'] ?? getenv('DB_PORT') ?? 3306);
$dbCharset = trim((string)($env['DB_CHARSET'] ?? getenv('DB_CHARSET') ?? 'utf8mb4'));

if ($dbHost === '' || $dbName === '' || $dbUser === '') {
    error_log('Cantinho Deolinda: configuração da base de dados incompleta.');
    http_response_code(500);
    die('Erro interno do servidor. Por favor tente mais tarde.');
}

$con = mysqli_init();
if (!$con) {
    error_log('Cantinho Deolinda: falha ao iniciar mysqli.');
    http_response_code(500);
    die('Erro interno do servidor. Por favor tente mais tarde.');
}

mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 5);

if (!mysqli_real_connect($con, $dbHost, $dbUser, $dbPass, $dbName, $dbPort)) {
    error_log('Cantinho Deolinda: erro de ligação à base de dados — ' . mysqli_connect_error());
    http_response_code(500);
    die('Erro interno do servidor. Por favor tente mais tarde.');
}

if (!mysqli_set_charset($con, $dbCharset)) {
    error_log('Cantinho Deolinda: erro ao definir charset da base de dados.');
    http_response_code(500);
    die('Erro interno do servidor. Por favor tente mais tarde.');
}
?>
