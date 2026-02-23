<?php
session_start();
include("ligar.php");
require_once("popup_helper.php");

if (!isset($_SESSION['permissoes'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['permissoes'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

function redirect_with_alert(string $message): void {
    cd_popup($message, 'info', 'confirmar_reservas.php');
    exit;
}

if (isset($_GET['confirmar'])) {
    $id = (int)$_GET['confirmar'];

    $sql = "SELECT r.*, c.nome, c.email, c.telefone
            FROM reservas r
            JOIN Cliente c ON r.cliente_id = c.id
            WHERE r.id=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $reserva = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reserva) {
        redirect_with_alert('Reserva nao encontrada.');
    }

    $sql = "UPDATE reservas
            SET confirmado = 1,
                estado = 'pendente',
                notificado_reserva = 0
            WHERE id=?";
    $stmt2 = $con->prepare($sql);
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

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
            "Pessoas: {$reserva['numero_pessoas']}\n\n" .
            "Sistema de Reservas - Cantinho Deolinda";

        $url = "https://graph.facebook.com/v20.0/{$phone_id}/messages";
        $payload_dono = [
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_dono));
        curl_exec($ch);
        curl_close($ch);
    }

    $para = $reserva['email'];
    $assunto = "Reserva Confirmada - Cantinho Deolinda";

    $mensagem_email = "
        Ola {$reserva['nome']},<br><br>
        A sua reserva foi <strong>confirmada</strong>!<br><br>
        <strong>Data:</strong> {$reserva['data_reserva']}<br>
        <strong>Hora:</strong> {$reserva['hora_reserva']}<br>
        <strong>Pessoas:</strong> {$reserva['numero_pessoas']}<br><br>
        Obrigado por escolher o Cantinho Deolinda!<br>
        Estamos ao seu dispor.
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Cantinho Deolinda <cantinhodeolina@gmail.com>\r\n";

    mail($para, $assunto, $mensagem_email, $headers);

    redirect_with_alert('Reserva confirmada! Email enviado ao cliente.');
}

if (isset($_GET['recusar'])) {
    $id = (int)$_GET['recusar'];

    $sql = "UPDATE reservas
            SET confirmado = -1,
                estado = 'recusada',
                notificado_reserva = 0
            WHERE id=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    redirect_with_alert('Reserva recusada!');
}

$sql = "SELECT r.id, c.nome, c.email, r.data_reserva, r.hora_reserva, r.numero_pessoas
        FROM reservas r
        JOIN Cliente c ON r.cliente_id = c.id
        WHERE r.confirmado = 0
          AND r.estado = 'pendente'
        ORDER BY r.data_reserva ASC";

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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmar Reservas</title>
  <style>
    :root {
      --bg: #0f1115;
      --panel: #151922;
      --panel-soft: #1b2130;
      --text: #f2f4f8;
      --muted: #b8c0d0;
      --gold: #f4b942;
      --line: rgba(255, 255, 255, 0.08);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: "Poppins", Arial, sans-serif;
      color: var(--text);
      background:
        radial-gradient(circle at 14% 12%, rgba(244, 185, 66, 0.14), transparent 34%),
        radial-gradient(circle at 92% 86%, rgba(244, 185, 66, 0.08), transparent 36%),
        var(--bg);
    }

    .page {
      width: min(1100px, 94vw);
      margin: 34px auto 48px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 14px;
      margin-bottom: 18px;
      padding: 18px 20px;
      border-radius: 16px;
      background: linear-gradient(160deg, #1c2434, #131924);
      border: 1px solid rgba(244, 185, 66, 0.25);
      box-shadow: 0 16px 28px rgba(0, 0, 0, 0.35);
    }

    .title h1 {
      margin: 0;
      font-size: clamp(1.35rem, 2.2vw, 1.85rem);
      color: #ffe09a;
    }

    .title p {
      margin: 6px 0 0;
      color: var(--muted);
      font-size: 0.94rem;
    }

    .back-link {
      display: inline-block;
      text-decoration: none;
      color: #1d1406;
      background: linear-gradient(135deg, #ffd67a, #f4b942);
      border: 1px solid rgba(255, 234, 188, 0.6);
      padding: 10px 14px;
      border-radius: 11px;
      font-weight: 700;
      white-space: nowrap;
    }

    .empty {
      padding: 20px;
      border-radius: 14px;
      background: var(--panel);
      border: 1px solid var(--line);
      color: var(--muted);
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 14px;
    }

    .card {
      background: linear-gradient(180deg, var(--panel-soft), var(--panel));
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 16px;
      box-shadow: 0 10px 22px rgba(0, 0, 0, 0.22);
    }

    .name {
      margin: 0 0 4px;
      font-size: 1.03rem;
      color: #fff4d8;
    }

    .email {
      margin: 0 0 12px;
      color: var(--muted);
      font-size: 0.9rem;
      word-break: break-word;
    }

    .meta {
      display: grid;
      gap: 8px;
      margin-bottom: 14px;
      font-size: 0.93rem;
    }

    .meta-line {
      display: flex;
      justify-content: space-between;
      gap: 8px;
      padding-bottom: 6px;
      border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
    }

    .meta-line span:first-child { color: var(--muted); }
    .meta-line span:last-child { color: var(--text); font-weight: 600; }

    .actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .btn {
      text-decoration: none;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.88rem;
      padding: 9px 12px;
      border: 1px solid transparent;
    }

    .btn-ok {
      color: #082111;
      background: linear-gradient(135deg, #6ff5a2, #2ecc71);
      border-color: rgba(178, 255, 208, 0.6);
    }

    .btn-no {
      color: #2a0c0c;
      background: linear-gradient(135deg, #ff9595, #ff5c5c);
      border-color: rgba(255, 192, 192, 0.7);
    }
  </style>
</head>
<body>
  <main class="page">
    <section class="header">
      <div class="title">
        <h1>Reservas pendentes</h1>
        <p>Confirme ou recuse os pedidos em espera.</p>
      </div>
      <a class="back-link" href="../admin.php">&larr; Voltar ao Admin</a>
    </section>

    <?php if (count($reservas) === 0): ?>
      <p class="empty">Nao ha reservas pendentes.</p>
    <?php else: ?>
      <section class="grid">
        <?php foreach ($reservas as $row): ?>
          <article class="card">
            <h2 class="name"><?php echo htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="email"><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></p>

            <div class="meta">
              <div class="meta-line">
                <span>Data</span>
                <span><?php echo htmlspecialchars($row['data_reserva'], ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="meta-line">
                <span>Hora</span>
                <span><?php echo htmlspecialchars($row['hora_reserva'], ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="meta-line">
                <span>Pessoas</span>
                <span><?php echo (int)$row['numero_pessoas']; ?></span>
              </div>
            </div>

            <div class="actions">
              <a class="btn btn-ok" href="confirmar_reservas.php?confirmar=<?php echo (int)$row['id']; ?>">Confirmar</a>
              <a class="btn btn-no" href="confirmar_reservas.php?recusar=<?php echo (int)$row['id']; ?>">Recusar</a>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
