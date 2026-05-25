<?php
session_start();
include("ligar.php");
require_once("popup_helper.php");
require_once("mesa_status_helper.php");
require_once("email_template_helper.php");
require_once(__DIR__ . "/../theme.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__ . "/../phpmailer/src/PHPMailer.php");
require_once(__DIR__ . "/../phpmailer/src/SMTP.php");
require_once(__DIR__ . "/../phpmailer/src/Exception.php");

$env = parse_ini_file(__DIR__ . "/../Seguranca/config.env");

if (!isset($_SESSION['permissoes'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['permissoes'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

cd_sync_mesa_states($con);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function esc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    return (string)($_SESSION['csrf_token'] ?? '');
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . esc(csrf_token()) . '">';
}

function verify_csrf_or_fail(): void
{
    $token = (string)($_POST['csrf_token'] ?? '');
    $sessionToken = csrf_token();
    if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        redirect_with_alert('Pedido inválido (CSRF).');
    }
}

function redirect_with_alert(string $message): void
{
    cd_popup($message, 'info', 'confirmar_reservas.php');
    exit;
}

function has_column(mysqli $con, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }
    $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $res = mysqli_query($con, $sql);
    return $res && mysqli_num_rows($res) > 0;
}

function has_rows_for_condition(mysqli $con, string $table, string $conditionSql): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }
    $sql = "SELECT 1 FROM `$table` WHERE $conditionSql LIMIT 1";
    $res = mysqli_query($con, $sql);
    return $res && mysqli_num_rows($res) > 0;
}

function get_busy_mesa_ids(mysqli $con, string $dataReserva, string $horaReserva, int $excludeReservaId = 0): array
{
    $sql = "
        SELECT DISTINCT rm.mesa_id
        FROM reserva_mesas rm
        JOIN reservas r ON r.id = rm.reserva_id
        WHERE r.data_reserva = ?
          AND r.hora_reserva = ?
          AND r.confirmado = 1
          AND r.estado NOT IN ('recusada', 'nao_compareceu')
          AND r.id <> ?
    ";

    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "ssi", $dataReserva, $horaReserva, $excludeReservaId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $busyIds = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $mesaId = trim((string)($row['mesa_id'] ?? ''));
            if ($mesaId !== '') {
                $busyIds[] = $mesaId;
            }
        }
    }
    mysqli_stmt_close($stmt);

    return $busyIds;
}

function mesa_sort_key(string $mesaId): array
{
    $mesaId = trim($mesaId);
    $prefix = strtolower(substr($mesaId, 0, 1));
    $areaRank = ['s' => 0, 'f' => 1, 'e' => 2][$prefix] ?? 3;
    $number = 999999;

    if (preg_match('/\d+/', $mesaId, $matches)) {
        $number = (int)$matches[0];
    }

    return [$areaRank, $number, strtolower($mesaId)];
}

function get_available_mesa_options(mysqli $con, string $dataReserva, string $horaReserva, int $excludeReservaId = 0): array
{
    $hasCapacidade = has_column($con, 'mesas', 'capacidade');
    $hasAtiva = has_column($con, 'mesas', 'ativa');
    $hasTipo = has_column($con, 'mesas', 'tipo');
    $hasGrupo = has_column($con, 'mesas', 'grupo');
    $capField = $hasCapacidade ? "m.capacidade" : "2";
    $whereParts = [];

    if ($hasTipo && has_rows_for_condition($con, 'mesas', "tipo = 'mesa'")) {
        $whereParts[] = "m.tipo = 'mesa'";
    }
    if ($hasAtiva && has_rows_for_condition($con, 'mesas', "ativa = 1")) {
        $whereParts[] = "m.ativa = 1";
    }
    $whereSql = count($whereParts) > 0 ? "WHERE " . implode("\n          AND ", $whereParts) : "";

    $sql = "
        SELECT
            m.id,
            $capField AS capacidade,
            " . ($hasGrupo ? "NULLIF(TRIM(m.grupo), '')" : "NULL") . " AS grupo
        FROM mesas m
        $whereSql
        ORDER BY m.id ASC
    ";

    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $mesas = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $mesas[] = [
                'id' => (string)$row['id'],
                'capacidade' => (int)$row['capacidade'],
                'grupo' => trim((string)($row['grupo'] ?? '')),
            ];
        }
    }
    mysqli_stmt_close($stmt);

    $busyMap = array_fill_keys(get_busy_mesa_ids($con, $dataReserva, $horaReserva, $excludeReservaId), true);
    $groupedMesas = [];
    $options = [];

    foreach ($mesas as $mesa) {
        $mesaId = trim((string)($mesa['id'] ?? ''));
        if ($mesaId === '') {
            continue;
        }

        $grupo = trim((string)($mesa['grupo'] ?? ''));
        if ($grupo !== '') {
            if (!array_key_exists($grupo, $groupedMesas)) {
                $groupedMesas[$grupo] = [];
            }
            $groupedMesas[$grupo][] = $mesa;
            continue;
        }

        if (isset($busyMap[$mesaId])) {
            continue;
        }

        $options[] = [
            'value' => 'mesa:' . $mesaId,
            'display_name' => 'Mesa ' . $mesaId,
            'detail' => '',
            'mesa_ids' => [$mesaId],
            'capacidade' => (int)$mesa['capacidade'],
            'sort_type' => 0,
            'sort_key' => mesa_sort_key($mesaId),
        ];
    }

    foreach ($groupedMesas as $grupo => $mesasDoGrupo) {
        $ids = [];
        $capacidadeTotal = 0;
        $groupAvailable = true;

        foreach ($mesasDoGrupo as $mesa) {
            $mesaId = trim((string)($mesa['id'] ?? ''));
            if ($mesaId === '' || isset($busyMap[$mesaId])) {
                $groupAvailable = false;
                break;
            }

            $ids[] = $mesaId;
            $capacidadeTotal += (int)$mesa['capacidade'];
        }

        if (!$groupAvailable || count($ids) === 0) {
            continue;
        }

        natsort($ids);
        $ids = array_values($ids);
        $detail = count($ids) > 1 ? 'Mesas ' . implode(', ', $ids) : 'Mesa ' . $ids[0];

        $options[] = [
            'value' => 'group:' . $grupo,
            'display_name' => 'Conjunto ' . $grupo,
            'detail' => $detail,
            'mesa_ids' => $ids,
            'capacidade' => $capacidadeTotal,
            'sort_type' => 1,
            'sort_key' => mesa_sort_key($ids[0]),
        ];
    }

    usort($options, static function (array $a, array $b): int {
        $typeCmp = ((int)($a['sort_type'] ?? 0)) <=> ((int)($b['sort_type'] ?? 0));
        if ($typeCmp !== 0) {
            return $typeCmp;
        }

        $aKey = $a['sort_key'] ?? [3, 999999, (string)($a['display_name'] ?? '')];
        $bKey = $b['sort_key'] ?? [3, 999999, (string)($b['display_name'] ?? '')];

        for ($i = 0; $i < 3; $i++) {
            $cmp = $aKey[$i] <=> $bKey[$i];
            if ($cmp !== 0) {
                return $cmp;
            }
        }

        return strnatcasecmp((string)$a['display_name'], (string)$b['display_name']);
    });

    return $options;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    verify_csrf_or_fail();
    $id = (int)$_POST['confirmar'];

    $mesasSelecionadasRaw = $_POST['mesas'] ?? [];
    if (!is_array($mesasSelecionadasRaw)) {
        $mesasSelecionadasRaw = [];
    }

    $mesasSelecionadasRaw = array_values(array_unique(array_filter(array_map(function ($v) {
        $v = trim((string)$v);
        if (preg_match('/^[A-Za-z0-9_-]{1,50}$/', $v)) {
            return 'mesa:' . $v;
        }
        return preg_match('/^(mesa|group):[A-Za-z0-9_-]{1,50}$/', $v) ? $v : '';
    }, $mesasSelecionadasRaw))));

    $sql = "SELECT r.*, c.nome, c.email, c.telefone
            FROM reservas r
            JOIN Cliente c ON r.cliente_id = c.id
            WHERE r.id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $reserva = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reserva) {
        redirect_with_alert('Reserva nao encontrada.');
    }

    if (count($mesasSelecionadasRaw) === 0) {
        redirect_with_alert('Selecione pelo menos uma mesa ou conjunto para confirmar.');
    }

    $mesasDisponiveis = get_available_mesa_options($con, (string)$reserva['data_reserva'], (string)$reserva['hora_reserva'], $id);
    $disponiveisMap = [];
    foreach ($mesasDisponiveis as $option) {
        $disponiveisMap[(string)$option['value']] = $option;
    }

    $mesasSelecionadas = [];
    $mesasResumo = [];
    $capacidadeTotal = 0;
    foreach ($mesasSelecionadasRaw as $optionValue) {
        if (!array_key_exists($optionValue, $disponiveisMap)) {
            redirect_with_alert('Uma das opções de mesa selecionadas já não está disponível para este horário.');
        }

        $option = $disponiveisMap[$optionValue];
        $capacidadeTotal += (int)$option['capacidade'];
        $mesasResumo[] = (string)$option['display_name'];

        foreach ((array)$option['mesa_ids'] as $mesaId) {
            $mesaId = trim((string)$mesaId);
            if ($mesaId !== '') {
                $mesasSelecionadas[$mesaId] = true;
            }
        }
    }

    $mesasSelecionadas = array_keys($mesasSelecionadas);

    if ($capacidadeTotal < (int)$reserva['numero_pessoas']) {
        redirect_with_alert('Capacidade das mesas insuficiente para esta reserva.');
    }

    mysqli_begin_transaction($con);
    try {
        $stmt2 = $con->prepare(
            "UPDATE reservas
             SET confirmado = 1,
                 estado = 'pendente',
                 notificado_reserva = 0
             WHERE id = ?"
        );
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();

        $stmtDelete = $con->prepare("DELETE FROM reserva_mesas WHERE reserva_id = ?");
        $stmtDelete->bind_param("i", $id);
        $stmtDelete->execute();
        $stmtDelete->close();

        $stmtInsert = $con->prepare("INSERT INTO reserva_mesas (reserva_id, mesa_id) VALUES (?, ?)");
        foreach ($mesasSelecionadas as $mesaId) {
            $stmtInsert->bind_param("is", $id, $mesaId);
            $stmtInsert->execute();
        }
        $stmtInsert->close();

        $stmtMesaEstado = $con->prepare("UPDATE mesas SET estado = 'reservada' WHERE id = ?");
        foreach ($mesasSelecionadas as $mesaId) {
            $stmtMesaEstado->bind_param("s", $mesaId);
            $stmtMesaEstado->execute();
        }
        $stmtMesaEstado->close();

        mysqli_commit($con);
    } catch (Throwable $e) {
        mysqli_rollback($con);
        redirect_with_alert('Erro ao confirmar reserva com mesas.');
    }

    $envCandidates = [
        __DIR__ . "/../Seguranca/config.env",
        __DIR__ . "/../seguranca/config.env",
    ];
    $env = [];
    foreach ($envCandidates as $envPath) {
        if (is_file($envPath) && is_readable($envPath)) {
            $parsedEnv = parse_ini_file($envPath, false, INI_SCANNER_RAW);
            if (is_array($parsedEnv)) {
                $env = $parsedEnv;
                break;
            }
        }
    }

    $token = $env['META_TOKEN'] ?? '';
    $phone_id = $env['PHONE_NUMBER_ID'] ?? '';
    $dono_numero = $env['DESTINO'] ?? '';

    if ($token !== '' && $phone_id !== '' && $dono_numero !== '') {
        $msgDono = "Reserva Confirmada!\n\n" .
            "Cliente: {$reserva['nome']}\n" .
            "Data: {$reserva['data_reserva']}\n" .
            "Hora: {$reserva['hora_reserva']}\n" .
            "Pessoas: {$reserva['numero_pessoas']}\n" .
            "Mesas: " . implode(', ', $mesasResumo) . "\n" .
            "Mesas físicas: " . implode(', ', $mesasSelecionadas) . "\n\n" .
            "Sistema de Reservas - Cantinho Deolinda";

        $url = "https://graph.facebook.com/v20.0/{$phone_id}/messages";
        $payloadDono = [
            "messaging_product" => "whatsapp",
            "to" => $dono_numero,
            "type" => "text",
            "text" => ["body" => $msgDono]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadDono));
        curl_exec($ch);
        curl_close($ch);
    }

    $para = (string)($reserva['email'] ?? '');
    $assunto = "Reserva Confirmada - Cantinho Deolinda";
    $mensagemEmail = cd_email_template(
        'Reserva confirmada',
        'A sua mesa está confirmada',
        "Olá {$reserva['nome']}, temos o prazer de confirmar a sua reserva.",
        '
            <p style="margin:0 0 16px;">Reservamos a sua visita e deixamos abaixo os principais detalhes para consulta rápida.</p>
            ' . cd_email_detail_rows([
                'Data' => (string)$reserva['data_reserva'],
                'Hora' => (string)$reserva['hora_reserva'],
                'Número de pessoas' => (string)$reserva['numero_pessoas'],
            ]) . '
            <p style="margin:16px 0 0;">Obrigado por escolher o Cantinho Deolinda. Esperamos por si.</p>
        '
    );

    if ($para !== '' && !empty($env['SMTP_HOST']) && !empty($env['SMTP_USER']) && !empty($env['SMTP_PASS'])) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $env['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $env['SMTP_USER'];
            $mail->Password = $env['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->setFrom($env['SMTP_FROM'] ?? $env['SMTP_USER'], $env['SMTP_FROM_NAME'] ?? 'Cantinho Deolinda');
            $mail->addAddress($para, (string)($reserva['nome'] ?? ''));
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $mensagemEmail;
            $mail->AltBody = "Olá {$reserva['nome']}, a sua reserva foi confirmada para {$reserva['data_reserva']} às {$reserva['hora_reserva']} para {$reserva['numero_pessoas']} pessoas.";
            $mail->send();
        } catch (Exception $e) {
            // Mantém o fluxo de confirmação mesmo se o email falhar.
        }
    }

    redirect_with_alert('Reserva confirmada e mesas atribuídas com sucesso.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recusar'])) {
    verify_csrf_or_fail();
    $id = (int)$_POST['recusar'];

    $mesasDaReserva = [];
    $stmtMesas = $con->prepare("SELECT mesa_id FROM reserva_mesas WHERE reserva_id = ?");
    $stmtMesas->bind_param("i", $id);
    $stmtMesas->execute();
    $resMesas = $stmtMesas->get_result();
    if ($resMesas) {
        while ($rowMesa = $resMesas->fetch_assoc()) {
            $mesaId = (string)($rowMesa['mesa_id'] ?? '');
            if ($mesaId !== '') {
                $mesasDaReserva[] = $mesaId;
            }
        }
    }
    $stmtMesas->close();

    $sql = "UPDATE reservas
            SET confirmado = -1,
                estado = 'recusada',
                notificado_reserva = 0
            WHERE id=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmtDeleteMesas = $con->prepare("DELETE FROM reserva_mesas WHERE reserva_id = ?");
    $stmtDeleteMesas->bind_param("i", $id);
    $stmtDeleteMesas->execute();
    $stmtDeleteMesas->close();

    if (count($mesasDaReserva) > 0) {
        $stmtMesaLivre = $con->prepare("UPDATE mesas SET estado = 'livre' WHERE id = ?");
        foreach ($mesasDaReserva as $mesaId) {
            $stmtMesaLivre->bind_param("s", $mesaId);
            $stmtMesaLivre->execute();
        }
        $stmtMesaLivre->close();
    }

    redirect_with_alert('Reserva recusada!');
}

$sql = "SELECT r.id, c.nome, c.email, r.data_reserva, r.hora_reserva, r.numero_pessoas
        FROM reservas r
        JOIN Cliente c ON r.cliente_id = c.id
        WHERE r.confirmado = 0
          AND r.estado = 'pendente'
        ORDER BY r.data_reserva ASC, r.hora_reserva ASC";

$result = $con->query($sql);
$reservas = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reservas[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../Imagens/logo_atual.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmar Reservas</title>
  <link rel="stylesheet" href="../Css/admin.css?v=<?php echo filemtime(__DIR__ . '/../Css/admin.css'); ?>">
  <link rel="stylesheet" href="../Css/bttlogin.css">
  <link rel="stylesheet" href="../Css/confirmar_reservas.css?v=<?php echo filemtime(__DIR__ . '/../Css/confirmar_reservas.css'); ?>">
  <?php cd_render_theme_head('../', dirname(__DIR__)); ?>

</head>
<body class="cdol-admin cdol-admin-home cdol-confirmar-admin">
  <?php cd_render_theme_toggle('../'); ?>
  <script>
    document.documentElement.classList.add('admin-home-page');
  </script>
  <button type="button" class="admin-home-menu-toggle" aria-label="Abrir menu admin" aria-expanded="false" aria-controls="adminHomeSidebar">
    <span></span>
    <span></span>
    <span></span>
  </button>
  <button type="button" class="admin-home-menu-overlay" aria-label="Fechar menu admin"></button>
  <aside class="admin-home-sidebar" id="adminHomeSidebar" aria-label="Menu administrativo">
    <div class="admin-home-sidebar-brand">
      <span class="admin-home-kicker">Cantinho Deolinda</span>
      <strong>Painel Admin</strong>
      <p>Centro de gestão do backoffice.</p>
    </div>
    <nav class="admin-home-nav">
      <a href="../admin.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 11.2 12 4l8 7.2V20a1 1 0 0 1-1 1h-4.8v-5.5H9.8V21H5a1 1 0 0 1-1-1z"/></svg></span><span class="admin-home-link-copy"><strong>Visão geral</strong><small>Painel principal</small></span></a>
      <a href="../Bd/confirmar_reservas.php" class="is-active" aria-current="page"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 12.5 10.2 16 17 8.8"/><rect x="4" y="4" width="16" height="16" rx="4"/></svg></span><span class="admin-home-link-copy"><strong>Confirmar reservas</strong><small>Entradas pendentes</small></span></a>
      <a href="../admin_reservas.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="5" width="6" height="6" rx="1.5"/><rect x="14" y="5" width="6" height="6" rx="1.5"/><rect x="4" y="13" width="6" height="6" rx="1.5"/><rect x="14" y="13" width="6" height="6" rx="1.5"/></svg></span><span class="admin-home-link-copy"><strong>Todas as reservas</strong><small>Lista completa</small></span></a>
      <a href="../admin_logs.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 7h10M7 12h10M7 17h10"/><rect x="4" y="4" width="16" height="16" rx="4"/></svg></span><span class="admin-home-link-copy"><strong>Logs</strong><small>Atividade do sistema</small></span></a>
      <a href="../admin_mapa.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M8 6.5 4.5 8v10L8 16.5l4 1.5 3.5-1.5L19.5 18V8l-4 1.5L12 8 8 9.5z"/><path d="M8 6.5v10M12 8v10M15.5 9.5v10"/></svg></span><span class="admin-home-link-copy"><strong>Mapa de mesas</strong><small>Disposição da sala</small></span></a>
      <a href="../admin_feedback.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17.5 4.5 20V7a2 2 0 0 1 2-2h11A2.5 2.5 0 0 1 20 7.5v7a2.5 2.5 0 0 1-2.5 2.5z"/><path d="M8 10h8M8 13h5"/></svg></span><span class="admin-home-link-copy"><strong>Feedback</strong><small>Opiniões dos clientes</small></span></a>
    </nav>
    <div class="admin-home-sidebar-footer">
      <a href="../dashboard.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 19V9.5L12 5l7 4.5V19z"/><path d="M9 19v-5h6v5"/></svg></span><span class="admin-home-link-copy"><strong>Dashboard</strong><small>Vista do utilizador</small></span></a>
      <a href="../index.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M10 7 5 12l5 5"/><path d="M6 12h9a4 4 0 1 0 0 8"/></svg></span><span class="admin-home-link-copy"><strong>Voltar ao site</strong><small>Regressar à homepage</small></span></a>
    </div>
  </aside>
  <div class="container">
  <main class="page">
    <section class="header">
      <div class="title">
        <h1>Reservas pendentes</h1>
        <p>Confirme ou recuse os pedidos em espera.</p>
      </div>
    </section>

    <?php if (count($reservas) === 0): ?>
      <p class="empty">Não há reservas pendentes.</p>
    <?php else: ?>
      <section class="grid">
        <?php foreach ($reservas as $row): ?>
          <?php $mesasLivres = get_available_mesa_options($con, (string)$row['data_reserva'], (string)$row['hora_reserva'], (int)$row['id']); ?>
          <article class="card<?php echo $row !== $reservas[0] ? ' is-collapsed' : ''; ?>" data-reserva-id="<?php echo (int)$row['id']; ?>">
            <div class="card-top">
              <div>
                <h2 class="name"><?php echo esc($row['nome']); ?></h2>
                <p class="email"><?php echo esc($row['email']); ?></p>
              </div>
              <div class="card-right">
                <span class="res-id">Reserva #<?php echo (int)$row['id']; ?></span>
                <button
                  type="button"
                  class="card-toggle-btn"
                  data-action="toggle-card"
                  aria-expanded="<?php echo $row === $reservas[0] ? 'true' : 'false'; ?>"
                  aria-label="<?php echo $row === $reservas[0] ? 'Minimizar reserva' : 'Expandir reserva'; ?>"
                  title="<?php echo $row === $reservas[0] ? 'Minimizar reserva' : 'Expandir reserva'; ?>"
                ><?php echo $row === $reservas[0] ? '-' : '+'; ?></button>
              </div>
            </div>

            <div class="card-body">
              <div class="meta">
                <div class="meta-line">
                  <span>Data</span>
                  <span><?php echo esc($row['data_reserva']); ?></span>
                </div>
                <div class="meta-line">
                  <span>Hora</span>
                  <span><?php echo esc($row['hora_reserva']); ?></span>
                </div>
                <div class="meta-line">
                  <span>Pessoas</span>
                  <span><?php echo (int)$row['numero_pessoas']; ?></span>
                </div>
              </div>

              <div class="actions">
                <form method="post" class="action-form action-form-confirm" id="confirmForm-<?php echo (int)$row['id']; ?>">
                  <?php echo csrf_input(); ?>
                  <p class="mesa-help">Selecione uma ou mais mesas ou conjuntos para compor a capacidade:</p>
                  <?php if (count($mesasLivres) > 0): ?>
                    <button type="button" class="mesa-toggle-btn" aria-expanded="false" data-action="toggle-mesas">
                      <?php echo count($mesasLivres); ?> opções disponíveis
                      <span class="mesa-toggle-arrow" aria-hidden="true">&#9660;</span>
                    </button>
                    <div class="mesa-grid-wrap">
                    <div class="mesa-grid">
                      <?php foreach ($mesasLivres as $mesa): ?>
                        <label class="mesa-check">
                          <input type="checkbox" name="mesas[]" value="<?php echo esc($mesa['value']); ?>" data-cap="<?php echo (int)$mesa['capacidade']; ?>">
                          <span class="mesa-check-txt">
                            <span class="mesa-check-id"><?php echo esc($mesa['display_name']); ?></span>
                            <?php if ((string)($mesa['detail'] ?? '') !== ''): ?>
                              <span class="mesa-check-detail"><?php echo esc($mesa['detail']); ?></span>
                            <?php endif; ?>
                            <span class="mesa-check-cap"><?php echo (int)$mesa['capacidade']; ?> lg.</span>
                          </span>
                        </label>
                      <?php endforeach; ?>
                    </div>
                    </div>
                  <?php else: ?>
                    <p class="mesa-empty">Sem mesas disponíveis para este horário.</p>
                  <?php endif; ?>
                  <input type="hidden" name="confirmar" value="<?php echo (int)$row['id']; ?>">
                </form>

                <form method="post" class="action-form action-form-reject" id="rejectForm-<?php echo (int)$row['id']; ?>">
                  <?php echo csrf_input(); ?>
                  <input type="hidden" name="recusar" value="<?php echo (int)$row['id']; ?>">
                </form>

                <div class="action-buttons">
                  <?php if (count($mesasLivres) > 0): ?>
                    <button type="submit" class="btn btn-ok btn-decision" form="confirmForm-<?php echo (int)$row['id']; ?>" data-pessoas="<?php echo (int)$row['numero_pessoas']; ?>" disabled>Confirmar</button>
                  <?php else: ?>
                    <button type="button" class="btn btn-ok btn-decision" disabled>Confirmar</button>
                  <?php endif; ?>
                  <button type="submit" class="btn btn-no btn-decision" form="rejectForm-<?php echo (int)$row['id']; ?>">Recusar</button>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>
  </div>
  <script>
    (function () {
      const cards = Array.from(document.querySelectorAll(".card"));
      cards.forEach((card) => {
        const btn = card.querySelector("[data-action='toggle-card']");
        const body = card.querySelector(".card-body");
        if (!btn || !body) return;
          btn.addEventListener("click", () => {
            const collapsed = card.classList.toggle("is-collapsed");
            btn.textContent = collapsed ? "+" : "-";
            btn.setAttribute("aria-expanded", collapsed ? "false" : "true");
            btn.setAttribute("aria-label", collapsed ? "Expandir reserva" : "Minimizar reserva");
            btn.setAttribute("title", collapsed ? "Expandir reserva" : "Minimizar reserva");
          });
      });

      document.querySelectorAll("[data-action='toggle-mesas']").forEach((btn) => {
        const wrap = btn.nextElementSibling;
        if (!wrap) return;
        btn.addEventListener("click", () => {
          const open = wrap.classList.toggle("is-open");
          btn.setAttribute("aria-expanded", open ? "true" : "false");
        });
      });

      document.querySelectorAll(".card").forEach((card) => {
        const confirmBtn = card.querySelector(".btn-ok[data-pessoas]");
        if (!confirmBtn) return;
        const pessoas = parseInt(confirmBtn.getAttribute("data-pessoas") || "0", 10);
        const checkboxes = card.querySelectorAll("input[type='checkbox'][data-cap]");
        if (checkboxes.length === 0) return;

        function updateConfirm() {
          let total = 0;
          checkboxes.forEach((cb) => {
            if (cb.checked) total += parseInt(cb.getAttribute("data-cap") || "0", 10);
          });
          const valid = total >= pessoas && total <= pessoas + 1;
          confirmBtn.disabled = !valid;
          confirmBtn.textContent = total > 0 ? "Confirmar · " + total + " lg." : "Confirmar";
        }

        checkboxes.forEach((cb) => cb.addEventListener("change", updateConfirm));
      });

    })();

    (function () {
      var body = document.body;
      var toggle = document.querySelector('.admin-home-menu-toggle');
      var overlay = document.querySelector('.admin-home-menu-overlay');
      var sidebar = document.querySelector('.admin-home-sidebar');
      if (!toggle || !overlay || !sidebar) return;
      function syncToggleToSidebar() {
        var styles = window.getComputedStyle(sidebar);
        var sidebarLeft = parseFloat(styles.left) || 0;
        var sidebarWidth = parseFloat(styles.width) || sidebar.offsetWidth || 0;
        var sidebarTop = parseFloat(styles.top) || 0;
        toggle.style.left = (sidebarLeft + sidebarWidth) + 'px';
        toggle.style.top = sidebarTop + 'px';
        toggle.style.transform = 'translateX(-50%)';
      }
      function closeMenu() {
        body.classList.remove('admin-home-menu-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.style.left = '';
        toggle.style.top = '';
        toggle.style.transform = '';
      }
      function openMenu() {
        body.classList.add('admin-home-menu-open');
        toggle.setAttribute('aria-expanded', 'true');
        window.setTimeout(syncToggleToSidebar, 20);
      }
      toggle.addEventListener('click', function () {
        if (body.classList.contains('admin-home-menu-open')) closeMenu();
        else openMenu();
      });
      overlay.addEventListener('click', closeMenu);
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeMenu();
      });
      window.addEventListener('resize', function () {
        if (body.classList.contains('admin-home-menu-open')) {
          syncToggleToSidebar();
        }
      });
      sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeMenu);
      });
    })();
  </script>
  <?php cd_render_theme_script('../', dirname(__DIR__)); ?>
  <nav class="admin-mobile-nav" aria-label="Navegação admin">
      <a class="admin-mob-item" data-nav="geral" href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="22" height="22" aria-hidden="true"><path d="M4 11.2 12 4l8 7.2V20a1 1 0 0 1-1 1h-4.8v-5.5H9.8V21H5a1 1 0 0 1-1-1z"/></svg>
          <span>Geral</span>
      </a>
      <a class="admin-mob-item" data-nav="confirmar" href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="22" height="22" aria-hidden="true"><path d="M7 12.5 10.2 16 17 8.8"/><rect x="4" y="4" width="16" height="16" rx="4"/></svg>
          <span>Confirmar</span>
      </a>
      <a class="admin-mob-item" data-nav="reservas" href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="22" height="22" aria-hidden="true"><rect x="4" y="5" width="6" height="6" rx="1.5"/><rect x="14" y="5" width="6" height="6" rx="1.5"/><rect x="4" y="13" width="6" height="6" rx="1.5"/><rect x="14" y="13" width="6" height="6" rx="1.5"/></svg>
          <span>Reservas</span>
      </a>
      <a class="admin-mob-item" data-nav="logs" href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="22" height="22" aria-hidden="true"><path d="M7 7h10M7 12h10M7 17h10"/><rect x="4" y="4" width="16" height="16" rx="4"/></svg>
          <span>Logs</span>
      </a>
      <button type="button" class="admin-mob-item admin-mob-mais" aria-label="Mais opções" aria-expanded="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="22" height="22" aria-hidden="true"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
          <span>Mais</span>
      </button>
  </nav>
  <script>
  (function () {
      var bd = window.location.pathname.replace(/\\/g, '/').indexOf('/Bd/') > -1;
      var p = bd ? '../' : '';
      var links = { geral: p + 'admin.php', confirmar: p + 'Bd/confirmar_reservas.php', reservas: p + 'admin_reservas.php', logs: p + 'admin_logs.php' };
      document.querySelectorAll('.admin-mob-item[data-nav]').forEach(function (el) {
          var nav = el.getAttribute('data-nav');
          if (links[nav]) el.href = links[nav];
      });
      var path = window.location.pathname;
      var pageMap = { 'admin.php': 'geral', 'confirmar_reservas.php': 'confirmar', 'admin_reservas.php': 'reservas', 'admin_logs.php': 'logs', 'admin_mapa.php': 'mapa', 'admin_feedback.php': 'feedback' };
      var active = null;
      for (var k in pageMap) { if (path.indexOf(k) > -1) { active = pageMap[k]; break; } }
      if (active) {
          var isMain = ['geral', 'confirmar', 'reservas', 'logs'].indexOf(active) > -1;
          var activeEl = isMain ? document.querySelector('.admin-mob-item[data-nav="' + active + '"]') : document.querySelector('.admin-mob-mais');
          if (activeEl) activeEl.classList.add('is-active');
      }
      var mais = document.querySelector('.admin-mob-mais');
      var toggle = document.querySelector('.admin-home-menu-toggle');
      if (mais && toggle) {
          mais.addEventListener('click', function () { toggle.click(); });
          new MutationObserver(function () {
              mais.setAttribute('aria-expanded', document.body.classList.contains('admin-home-menu-open') ? 'true' : 'false');
          }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
      }
  })();
  </script>
</body>
</html>
