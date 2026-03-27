<?php
require("../config.php");
include("ligar.php");
require_once("popup_helper.php");

function cd_reserva_fetch_one(mysqli $con, string $sql, string $types = '', ...$params): ?array
{
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return null;
    }

    if ($types !== '' && count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return is_array($row) ? $row : null;
}

// Verifica se o utilizador esta autenticado
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

// Nao deixa fazer reserva se estiver na lista negra
$cliente_id = $_SESSION['id'];
$user = cd_reserva_fetch_one(
    $con,
    "SELECT lista_negra FROM Cliente WHERE id = ? LIMIT 1",
    "i",
    $cliente_id
);

if ($user && $user['lista_negra'] == 1) {
    header("Location: ../index.php?erro=lista_negra");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_SESSION['id'];
    $data_reserva = $_POST['data_reserva'];
    $hora_reserva = $_POST['hora_reserva'];
    $numero_pessoas = (int)$_POST['numero_pessoas']; // converte em numero inteiro

    // Validacoes simples
    if (empty($data_reserva) || empty($hora_reserva) || empty($numero_pessoas)) {
        cd_popup('Erro: todos os campos obrigatorios devem ser preenchidos.', 'error', '__HISTORY_BACK__');
        exit;
    }

    // Validacao do numero de pessoas
    if ($numero_pessoas < 1) {
        cd_popup('Erro: numero de pessoas invalido.', 'error', '__HISTORY_BACK__');
        exit;
    }

    if ($numero_pessoas > 30) {
        cd_popup('Erro: limite maximo de 30 pessoas por reserva. Contacte o restaurante.', 'error', '__HISTORY_BACK__');
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
        cd_popup('Erro: data de reserva invalida.', 'error', '__HISTORY_BACK__');
        exit;
    }

    $hoje = new DateTime('today');
    if ($dataObj < $hoje) {
        cd_popup('Erro: nao e possivel reservar para uma data anterior a hoje.', 'error', '__HISTORY_BACK__');
        exit;
    }

    // Valida a hora (HH:MM), intervalo e regras de horario
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hora_reserva)) {
        cd_popup('Erro: hora de reserva invalida.', 'error', '__HISTORY_BACK__');
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
        cd_popup('Erro: hora fora do horario permitido para reservas.', 'error', '__HISTORY_BACK__');
        exit;
    }

    // Cria reserva pendente (confirmado = 0)
    $sql = "INSERT INTO reservas (cliente_id, data_reserva, hora_reserva, numero_pessoas, confirmado)
            VALUES (?, ?, ?, ?, 0)";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "issi", $cliente_id, $data_reserva, $hora_reserva, $numero_pessoas);

    if (mysqli_stmt_execute($stmt)) {
        cd_popup('Reserva efetuada!\nSe a reserva for aceite, sera enviado um email.\nRecebera tambem uma notificacao quando voltar a entrar no site.', 'success', '../dashboard.php?tab=Reservas');
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
