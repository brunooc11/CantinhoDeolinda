<?php

// ========================================
// === TESTE COMPLETO PHP + HTML (WhatsApp Cloud API)
// ========================================

$env = parse_ini_file(__DIR__ . '/Seguranca/config.env');

$token = $env['META_TOKEN'];
$id_numero = $env['PHONE_NUMBER_ID'];
$numero_destino = $env['DESTINO'];

// === ENVIO AUTOMÁTICO ===
$mensagem_enviada = false;
$erro = false;
$resposta_api = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST["nome"] ?? "Cliente";
    $data = $_POST["data"] ?? "Data não informada";
    $hora = $_POST["hora"] ?? "Hora não informada";
    $pessoas = $_POST["pessoas"] ?? "1";

    // Monta mensagem personalizada
    $mensagem = "🍽️ *Reserva Confirmada!*\n\n" .
                "👤 Nome: $nome\n" .
                "📅 Data: $data\n" .
                "🕒 Hora: $hora\n" .
                "👥 Pessoas: $pessoas\n\n" .
                "Mensagem enviada automaticamente pelo sistema de reservas.";

    // Endpoint da API (v22.0)
    $url = "https://graph.facebook.com/v22.0/$id_numero/messages";

    $dados = [
        "messaging_product" => "whatsapp",
        "to" => $numero_destino,
        "type" => "text",
        "text" => ["body" => $mensagem]
    ];

    $opcoes = [
        "http" => [
            "header" => "Content-Type: application/json\r\nAuthorization: Bearer $token\r\n",
            "method" => "POST",
            "content" => json_encode($dados)
        ]
    ];

    $contexto = stream_context_create($opcoes);
    $resultado = @file_get_contents($url, false, $contexto);

    if ($resultado === FALSE) {
        $erro = true;
    } else {
        $mensagem_enviada = true;
        $resposta_api = $resultado;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Teste WhatsApp Cloud API</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }
    form {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 320px;
    }
    h2 { text-align: center; color: #333; }
    input, button {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 15px;
    }
    button {
      background: #25D366;
      color: white;
      font-weight: bold;
      cursor: pointer;
      border: none;
    }
    button:hover {
      background: #1DA955;
    }
    .mensagem {
      text-align: center;
      margin-top: 15px;
      padding: 10px;
      border-radius: 5px;
    }
    .ok { background: #D4EDDA; color: #155724; }
    .erro { background: #F8D7DA; color: #721C24; }
    pre {
      background: #eee;
      padding: 10px;
      border-radius: 6px;
      overflow-x: auto;
      font-size: 12px;
    }
  </style>
</head>
<body>

<form method="POST">
  <h2>Reserva de Teste</h2>
  <input type="text" name="nome" placeholder="Seu nome" required>
  <input type="date" name="data" required>
  <input type="time" name="hora" required>
  <input type="number" name="pessoas" min="1" max="10" placeholder="Nº de pessoas" required>
  <button type="submit">Enviar Reserva</button>

  <?php if ($mensagem_enviada): ?>
    <div class="mensagem ok">✅ Mensagem enviada com sucesso!</div>
    <pre><?php echo htmlspecialchars($resposta_api); ?></pre>
  <?php elseif ($erro): ?>
    <div class="mensagem erro">❌ Erro ao enviar mensagem. Verifica o token.</div>
  <?php endif; ?>
</form>

</body>
</html>
