<?php
session_start();
include("ligar.php");
require_once("popup_helper.php");
require_once("mesa_status_helper.php");
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

function get_available_mesas(mysqli $con, string $dataReserva, string $horaReserva, int $excludeReservaId = 0): array
{
    $hasCapacidade = has_column($con, 'mesas', 'capacidade');
    $hasAtiva = has_column($con, 'mesas', 'ativa');
    $hasTipo = has_column($con, 'mesas', 'tipo');
    $capField = $hasCapacidade ? "m.capacidade" : "2";
    $whereParts = [];

    if ($hasTipo && has_rows_for_condition($con, 'mesas', "tipo = 'mesa'")) {
        $whereParts[] = "m.tipo = 'mesa'";
    }
    if ($hasAtiva && has_rows_for_condition($con, 'mesas', "ativa = 1")) {
        $whereParts[] = "m.ativa = 1";
    }
    $wherePrefix = count($whereParts) > 0 ? implode("\n          AND ", $whereParts) . "\n          AND " : "";

    $sql = "
        SELECT m.id, $capField AS capacidade
        FROM mesas m
        WHERE {$wherePrefix}m.id NOT IN (
              SELECT rm.mesa_id
              FROM reserva_mesas rm
              JOIN reservas r ON r.id = rm.reserva_id
              WHERE r.data_reserva = ?
                AND r.hora_reserva = ?
                AND r.confirmado = 1
                AND r.estado NOT IN ('recusada', 'nao_compareceu')
                AND r.id <> ?
          )
        ORDER BY capacidade ASC, m.id ASC
    ";

    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, "ssi", $dataReserva, $horaReserva, $excludeReservaId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = [
                'id' => (string)$row['id'],
                'capacidade' => (int)$row['capacidade'],
            ];
        }
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    verify_csrf_or_fail();
    $id = (int)$_POST['confirmar'];

    $mesasSelecionadas = $_POST['mesas'] ?? [];
    if (!is_array($mesasSelecionadas)) {
        $mesasSelecionadas = [];
    }
    $mesasSelecionadas = array_values(array_unique(array_filter(array_map(function ($v) {
        $v = trim((string)$v);
        return preg_match('/^[A-Za-z0-9_-]{1,50}$/', $v) ? $v : '';
    }, $mesasSelecionadas))));

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
        redirect_with_alert('Reserva n?o encontrada.');
    }

    if (count($mesasSelecionadas) === 0) {
        redirect_with_alert('Selecione pelo menos uma mesa para confirmar.');
    }

    $mesasDisponiveis = get_available_mesas($con, (string)$reserva['data_reserva'], (string)$reserva['hora_reserva'], $id);
    $disponiveisMap = [];
    foreach ($mesasDisponiveis as $m) {
        $disponiveisMap[$m['id']] = $m['capacidade'];
    }

    $capacidadeTotal = 0;
    foreach ($mesasSelecionadas as $mesaId) {
        if (!array_key_exists($mesaId, $disponiveisMap)) {
            redirect_with_alert("A mesa {$mesaId} já não está disponível para este horário.");
        }
        $capacidadeTotal += (int)$disponiveisMap[$mesaId];
    }

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

    $envPath = $_SERVER['DOCUMENT_ROOT'] . "/Seguranca/config.env";
    $env = file_exists($envPath) ? parse_ini_file($envPath) : [];

    $token = $env['META_TOKEN'] ?? '';
    $phone_id = $env['PHONE_NUMBER_ID'] ?? '';
    $dono_numero = $env['DESTINO'] ?? '';

    if ($token !== '' && $phone_id !== '' && $dono_numero !== '') {
        $msgDono = "Reserva Confirmada!\n\n" .
            "Cliente: {$reserva['nome']}\n" .
            "Data: {$reserva['data_reserva']}\n" .
            "Hora: {$reserva['hora_reserva']}\n" .
            "Pessoas: {$reserva['numero_pessoas']}\n" .
            "Mesas: " . implode(', ', $mesasSelecionadas) . "\n\n" .
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
    $mensagemEmail = "
        <p>Olá {$reserva['nome']},</p>
        <p>A sua reserva foi <strong>confirmada</strong>.</p>
        <p>
            <strong>Data:</strong> {$reserva['data_reserva']}<br>
            <strong>Hora:</strong> {$reserva['hora_reserva']}<br>
            <strong>Pessoas:</strong> {$reserva['numero_pessoas']}
        </p>
        <p>Obrigado por escolher o Cantinho Deolinda.</p>
        <p>Estamos ao seu dispor.</p>
    ";

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
            // Mantem o fluxo de confirmação mesmo se o email falhar.
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
  <link rel="stylesheet" href="../Css/confirmar_reservas.css">

</head>
<body class="cdol-admin cdol-admin-home cdol-confirmar-admin">
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
          <?php $mesasLivres = get_available_mesas($con, (string)$row['data_reserva'], (string)$row['hora_reserva'], (int)$row['id']); ?>
          <article class="card" data-reserva-id="<?php echo (int)$row['id']; ?>">
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
                  aria-expanded="true"
                  aria-label="Minimizar reserva"
                  title="Minimizar reserva"
                >-</button>
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
                  <p class="mesa-help">Selecione uma ou mais mesas para compor a capacidade:</p>
                  <?php if (count($mesasLivres) > 0): ?>
                    <p class="mesa-summary"><?php echo count($mesasLivres); ?> mesas disponíveis</p>
                    <div class="mesa-grid">
                      <?php foreach ($mesasLivres as $mesa): ?>
                        <label class="mesa-check">
                          <input type="checkbox" name="mesas[]" value="<?php echo esc($mesa['id']); ?>">
                          <span class="mesa-check-txt">
                            <span class="mesa-check-id">Mesa <?php echo esc($mesa['id']); ?></span>
                            <span class="mesa-check-cap"><?php echo (int)$mesa['capacidade']; ?> lugares</span>
                          </span>
                        </label>
                      <?php endforeach; ?>
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
                    <button type="submit" class="btn btn-ok btn-decision" form="confirmForm-<?php echo (int)$row['id']; ?>">Confirmar</button>
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
</body>
</html>



