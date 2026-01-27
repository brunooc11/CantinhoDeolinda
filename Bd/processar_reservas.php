<?php
require("../config.php");  
include("ligar.php");

// Verifica se o utilizador está autenticado
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}


//nao deixa fazer reserva se estiver na lista negra
$cliente_id = $_SESSION['id'];

$check = mysqli_query(
    $con,
    "SELECT lista_negra FROM Cliente WHERE id = $cliente_id LIMIT 1"
);

$user = mysqli_fetch_assoc($check);

if ($user && $user['lista_negra'] == 1) {
    header("Location: ../index.php?erro=lista_negra");
    exit;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cliente_id = $_SESSION['id'];
    $data_reserva = $_POST['data_reserva'];
    $hora_reserva = $_POST['hora_reserva'];
    $numero_pessoas = (int)$_POST['numero_pessoas']; // converte em número inteiro

    // Validações simples
if (empty($data_reserva) || empty($hora_reserva) || empty($numero_pessoas)) {
    echo "<script>
            alert('Erro: todos os campos obrigatórios devem ser preenchidos.');
            window.history.back();
          </script>";
    exit;
}

// 🧩 Validação do número de pessoas
if ($numero_pessoas < 1) {
    echo "<script>
            alert('Erro: número de pessoas inválido.');
            window.history.back();
          </script>";
    exit;
}

if ($numero_pessoas > 30) {
    echo "<script>
            alert('Erro: limite máximo de 30 pessoas por reserva. Contacte o restaurante.');
            window.history.back();
          </script>";
    exit;
}


    // Cria reserva pendente (confirmado = 0)
    $sql = "INSERT INTO reservas (cliente_id, data_reserva, hora_reserva, numero_pessoas, confirmado)
            VALUES (?, ?, ?, ?, 0)";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "issi", $cliente_id, $data_reserva, $hora_reserva, $numero_pessoas);

    if (mysqli_stmt_execute($stmt)) {
        // Reserva criada com sucesso
        echo "<script>
                alert('Reserva efetuada!\\nSe a reserva for aceite será enviado um email.\\nReceberá também uma notificação quando voltar a entrar no site');
                window.location.href='../dashboard.php?tab=Reservas';
              </script>";
    } else {
        die('Erro ao efetuar reserva: ' . mysqli_error($con));
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);

} else {
    header("Location: ../index.php");
    exit;
}
?>
