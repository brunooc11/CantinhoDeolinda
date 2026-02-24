<?php
require __DIR__ . "/../config.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "LOGIN_REQUIRED"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["ok" => false, "error" => "METHOD_NOT_ALLOWED"]);
    exit;
}

$clienteId = (int) $_SESSION['id'];
$raw = file_get_contents("php://input");
$json = json_decode($raw, true);
if (!is_array($json)) {
    $json = [];
}

$acao = $json['acao'] ?? ($_POST['acao'] ?? '');

if ($acao === 'listar') {
    $ids = [];
    $sql = "SELECT item_id FROM favoritos WHERE cliente_id = ?";
    $stmt = mysqli_prepare($con, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $clienteId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $itemId);
        while (mysqli_stmt_fetch($stmt)) {
            $ids[] = $itemId;
        }
        mysqli_stmt_close($stmt);
    }

    echo json_encode(["ok" => true, "ids" => $ids]);
    exit;
}

$itemId = trim((string) ($json['item_id'] ?? $_POST['item_id'] ?? ''));

if ($itemId === '') {
    http_response_code(422);
    echo json_encode(["ok" => false, "error" => "ITEM_INVALIDO"]);
    exit;
}

if ($acao === 'adicionar') {
    $sql = "INSERT IGNORE INTO favoritos (cliente_id, item_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "DB_ERROR"]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "is", $clienteId, $itemId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(["ok" => true]);
    exit;
}

if ($acao === 'remover') {
    $sql = "DELETE FROM favoritos WHERE cliente_id = ? AND item_id = ?";
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "DB_ERROR"]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "is", $clienteId, $itemId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(["ok" => true]);
    exit;
}

http_response_code(400);
echo json_encode(["ok" => false, "error" => "ACAO_INVALIDA"]);
