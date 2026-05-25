<?php
require("../config.php");
include("ligar.php");
require_once("popup_helper.php");

function cd_reserva_popup(string $msg, string $type, string $redirect = ''): never {
    echo '<!DOCTYPE html><html lang="pt"><head>'
        . '<meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1.0">'
        . '<style>*{margin:0;padding:0;box-sizing:border-box}html,body{height:100%;background:#111;font-family:system-ui,sans-serif}</style>'
        . '</head><body>';
    cd_popup($msg, $type, $redirect !== '' ? $redirect : null);
    echo '</body></html>';
    exit;
}

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

// Verifica se o utilizador está autenticado
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

// Não deixa fazer reserva se estiver na lista negra
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
    $numero_pessoas = (int)$_POST['numero_pessoas']; // converte em número inteiro

    // Validações simples
    if (empty($data_reserva) || empty($hora_reserva) || empty($numero_pessoas)) {
        cd_reserva_popup('Erro: todos os campos obrigatórios devem ser preenchidos.', 'error', '__HISTORY_BACK__');
        exit;
    }

    // Validação do número de pessoas
    if ($numero_pessoas < 1) {
        cd_reserva_popup('Erro: número de pessoas inválido.', 'error', '__HISTORY_BACK__');
        exit;
    }

    if ($numero_pessoas > 30) {
        cd_reserva_popup('Erro: limite máximo de 30 pessoas por reserva. Contacte o restaurante.', 'error', '__HISTORY_BACK__');
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
        cd_reserva_popup('Erro: data de reserva inválida.', 'error', '__HISTORY_BACK__');
        exit;
    }

    $hoje = new DateTime('today');
    if ($dataObj < $hoje) {
        cd_reserva_popup('Erro: não é possível reservar para uma data anterior a hoje.', 'error', '__HISTORY_BACK__');
        exit;
    }

    // Valida a hora (HH:MM), intervalo e regras de horário
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hora_reserva)) {
        cd_reserva_popup('Erro: hora de reserva inválida.', 'error', '__HISTORY_BACK__');
        exit;
    }

    $partesHora = explode(':', $hora_reserva);
    $hora = (int)$partesHora[0];
    $minuto = (int)$partesHora[1];
    $minutosTotais = ($hora * 60) + $minuto;
    $minutosTotais = (int)(round($minutosTotais / 5) * 5); // arredonda para o múltiplo de 5 mais próximo
    $hora = intdiv($minutosTotais, 60);
    $minuto = $minutosTotais % 60;
    $hora_reserva = sprintf('%02d:%02d', $hora, $minuto);

    $minimoPermitido = (8 * 60); // 08:00
    $diaSemana = (int)$dataObj->format('w'); // 0 = domingo
    $maximoPermitido = ($diaSemana === 0) ? (17 * 60) : (23 * 60 + 55);

    if ($minutosTotais < $minimoPermitido || $minutosTotais > $maximoPermitido) {
        cd_reserva_popup('Erro: hora fora do horário permitido para reservas.', 'error', '__HISTORY_BACK__');
        exit;
    }

    // Cria reserva pendente (confirmado = 0)
    $sql = "INSERT INTO reservas (cliente_id, data_reserva, hora_reserva, numero_pessoas, confirmado)
            VALUES (?, ?, ?, ?, 0)";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "issi", $cliente_id, $data_reserva, $hora_reserva, $numero_pessoas);

    if (mysqli_stmt_execute($stmt)) {
        cd_reserva_popup('Reserva efetuada!\nSe a reserva for aceite, será enviado um email.\nReceberá também uma notificação quando voltar a entrar no site.', 'success', '../dashboard.php?tab=Reservas');
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
