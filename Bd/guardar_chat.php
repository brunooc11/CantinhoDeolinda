<?php
session_start();
require("ligar.php");

$mensagem  = $_POST['mensagem'] ?? '';
$remetente = $_POST['remetente'] ?? 'user';
$cliente_id = $_SESSION['id'] ?? null;

if (trim($mensagem) === '') {
    exit;
}

$stmt = $con->prepare(
  "INSERT INTO chat_bot (cliente_id, mensagem, remetente)
   VALUES (?, ?, ?)"
);
$stmt->bind_param("iss", $cliente_id, $mensagem, $remetente);
$stmt->execute();
$stmt->close();

echo "OK";
