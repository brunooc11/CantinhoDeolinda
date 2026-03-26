<?php
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
    die('Configuracao da base de dados incompleta. Preenche DB_HOST, DB_NAME, DB_USER e DB_PASS em Seguranca/config.env.');
}

$con = mysqli_init();
if (!$con) {
    die('Erro ao iniciar ligacao a base de dados.');
}

mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 5);

if (!mysqli_real_connect($con, $dbHost, $dbUser, $dbPass, $dbName, $dbPort)) {
    die('Erro de ligacao a base de dados.');
}

if (!mysqli_set_charset($con, $dbCharset)) {
    die('Erro ao definir charset da base de dados.');
}
?>
