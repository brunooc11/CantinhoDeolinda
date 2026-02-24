<?php
require("../config.php");  
include("ligar.php");
require_once("popup_helper.php");

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
    cd_popup('Erro: todos os campos obrigatórios devem ser preenchidos.', 'error', '__HISTORY_BACK__');
    exit;
}

// Validacao do numero de pessoas
if ($numero_pessoas < 1) {
    cd_popup('Erro: número de pessoas inválido.', 'error', '__HISTORY_BACK__');
    exit;
}

if ($numero_pessoas > 30) {
    cd_popup('Erro: limite máximo de 30 pessoas por reserva. Contacte o restaurante.', 'error', '__HISTORY_BACK__');
    exit;
}


// Valida formato da data e impede reservas em datas anteriores a hoje
$dataObj = DateTime::createFromFormat('Y-m-d', $data_reserva);
$errosData = DateTime::getLastErrors();
$warnings = is_array($errosData) ? $errosData['warning_count'] : 0;
$errors = is_array($errosData) ? $errosData['error_count'] : 0;

if (
    !$dataObj ||
    $warnings > 0 ||
    $errors > 0
) {
    cd_popup('Erro: data de reserva inválida.', 'error', '__HISTORY_BACK__');
    exit;
}

$hoje = new DateTime('today');
if ($dataObj < $hoje) {
    cd_popup('Erro: não é possível reservar para uma data anterior a hoje.', 'error', '__HISTORY_BACK__');
    exit;
}

// Valida a hora (HH:MM), intervalo e regras de horario
if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hora_reserva)) {
    cd_popup('Erro: hora de reserva inválida.', 'error', '__HISTORY_BACK__');
    exit;
}

$partesHora = explode(':', $hora_reserva);
$hora = (int)$partesHora[0];
$minuto = (int)$partesHora[1];
$minutosTotais = ($hora * 60) + $minuto;
$minutosTotais = (int)(round($minutosTotais / 5) * 5); // arredonda para o multiplo de 5 mais proximo
$hora = intdiv($minutosTotais, 60);
$minuto = $minutosTotais % 60;
$hora_reserva = sprintf('%02d:%02d', $hora, $minuto);

$minimoPermitido = (8 * 60); // 08:00
$diaSemana = (int)$dataObj->format('w'); // 0 = domingo
$maximoPermitido = ($diaSemana === 0) ? (17 * 60) : (23 * 60 + 55);

if ($minutosTotais < $minimoPermitido || $minutosTotais > $maximoPermitido) {
    cd_popup('Erro: hora fora do horário permitido para reservas.', 'error', '__HISTORY_BACK__');
    exit;
}

    // Cria reserva pendente (confirmado = 0)
    $sql = "INSERT INTO reservas (cliente_id, data_reserva, hora_reserva, numero_pessoas, confirmado)
            VALUES (?, ?, ?, ?, 0)";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "issi", $cliente_id, $data_reserva, $hora_reserva, $numero_pessoas);

    if (mysqli_stmt_execute($stmt)) {
        // Reserva criada com sucesso
        cd_popup('Reserva efetuada!\nSe a reserva for aceite, será enviado um email.\nReceberá também uma notificação quando voltar a entrar no site.', 'success', '../dashboard.php?tab=Reservas');
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

