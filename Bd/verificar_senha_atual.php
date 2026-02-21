<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit();
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'not_authenticated']);
    exit();
}

require_once __DIR__ . '/ligar.php';

$senhaAtual = $_POST['senha_atual'] ?? '';
if ($senhaAtual === '') {
    echo json_encode(['ok' => false]);
    exit();
}

$id = (int) $_SESSION['id'];
$sql = 'SELECT password FROM Cliente WHERE id = ? LIMIT 1';
$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_prepare_failed']);
    exit();
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $hashBd);
$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$found || !$hashBd) {
    echo json_encode(['ok' => false]);
    exit();
}

$ok = password_verify($senhaAtual, $hashBd);
echo json_encode(['ok' => $ok]);

