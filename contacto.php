<?php
require __DIR__ . "/config.php";

function podeEnviarFeedback(mysqli $con, int $clienteId): bool
{
    $sql = "
        SELECT 1
        FROM reservas
        WHERE cliente_id = ?
          AND confirmado = 1
          AND TIMESTAMP(data_reserva, hora_reserva) <= NOW()
        LIMIT 1
    ";

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $clienteId);
    $stmt->execute();
    $stmt->store_result();
    $ok = $stmt->num_rows > 0;
    $stmt->close();

    return $ok;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['id'])) {
        http_response_code(401);
        echo "LOGIN_REQUIRED";
        exit;
    }

    $clienteId = (int) $_SESSION['id'];
    if (!podeEnviarFeedback($con, $clienteId)) {
        http_response_code(403);
        echo "RESERVA_REQUIRED";
        exit;
    }

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($nome === '' || $email === '' || $assunto === '' || $mensagem === '') {
        http_response_code(422);
        echo "INVALID_DATA";
        exit;
    }

    $stmt = $con->prepare(
        "INSERT INTO contactos (nome, email, assunto, mensagem)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ssss",
        $nome,
        $email,
        $assunto,
        $mensagem
    );
    $stmt->execute();
    $stmt->close();

    echo "OK";
    exit;
}
