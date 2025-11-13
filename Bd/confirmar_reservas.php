<?php
session_start();
include("ligar.php");

// (mais tarde podes proteger esta página para só o admin aceder)

echo "<h2>Reservas pendentes</h2>";

$sql = "SELECT r.id, c.nome, c.email, r.data_reserva, r.hora_reserva, r.numero_pessoas
        FROM reservas r
        JOIN Cliente c ON r.cliente_id = c.id
        WHERE r.confirmado = 0
        ORDER BY r.data_reserva ASC";

$result = $con->query($sql);

if ($result->num_rows === 0) {
    echo "<p>Não há reservas pendentes.</p>";
}

while ($row = $result->fetch_assoc()) {
    echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>
            <p><strong>Cliente:</strong> {$row['nome']} ({$row['email']})</p>
            <p><strong>Data:</strong> {$row['data_reserva']} às {$row['hora_reserva']}</p>
            <p><strong>Pessoas:</strong> {$row['numero_pessoas']}</p>
            <a href='confirmar_reservas.php?confirmar={$row['id']}'>✅ Confirmar</a> |
            <a href='confirmar_reservas.php?recusar={$row['id']}'>❌ Recusar</a>
          </div>";
}

/* =====================================================
   CONFIRMAR RESERVA — Email (cliente) — sem WhatsApp admin
   ===================================================== */
if (isset($_GET['confirmar'])) {

    $id = (int)$_GET['confirmar'];

    // Buscar info completa da reserva
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
        echo "<script>alert('Reserva não encontrada.'); window.location.href='confirmar_reservas.php';</script>";
        exit;
    }

    // Confirmar na BD
    $sql = "UPDATE reservas SET confirmado = 1 WHERE id=?";
    $stmt2 = $con->prepare($sql);
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();


    /* ==========================
   📲 WHATSAPP PARA O DONO
   ========================== */

    $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/Seguranca/config.env");

    $token = $env['META_TOKEN'];
    $phone_id = $env['PHONE_NUMBER_ID'];
    $dono_numero = $env['DESTINO']; // número do dono

    // MOSTRAR CONFIRMAÇÃO DO NÚMERO LIDO (DEBUG)
    echo "<pre>";
    echo "Número do dono carregado do .env: ";
    var_dump($dono_numero);
    echo "</pre>";

    // Mensagem enviada ao dono
    $msgDono = "🍽️ *Reserva Confirmada!*\n\n" .
        "👤 Cliente: {$reserva['nome']}\n" .
        "📅 Data: {$reserva['data_reserva']}\n" .
        "🕒 Hora: {$reserva['hora_reserva']}\n" .
        "👥 Pessoas: {$reserva['numero_pessoas']}\n\n" .
        "Sistema de Reservas — Cantinho Deolinda";

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
    $resp_dono = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // DEBUG do envio
    echo "<pre>";
    echo "HTTP CODE: $http_code\n";
    echo "RESPOSTA DA META:\n";
    print_r($resp_dono);
    echo "</pre>";


    /* ==========================
       📧 EMAIL PARA O CLIENTE
       ========================== */


    $para = $reserva['email'];
    $assunto = "Reserva Confirmada — Cantinho Deolinda";

    $mensagem_email = "
        Olá {$reserva['nome']},<br><br>
        A sua reserva foi <strong>confirmada</strong>!<br><br>
        <strong>Data:</strong> {$reserva['data_reserva']}<br>
        <strong>Hora:</strong> {$reserva['hora_reserva']}<br>
        <strong>Pessoas:</strong> {$reserva['numero_pessoas']}<br><br>
        Obrigado por escolher o Cantinho Deolinda!<br>
        Estamos ao seu dispor.
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Cantinho Deolinda <no-reply@cantinho.pt>\r\n";

    mail($para, $assunto, $mensagem_email, $headers);



    /* ==========================
   📲 WHATSAPP PARA O CLIENTE
   ========================== */

    /*

    $cliente_numero = preg_replace('/\D+/', '', $reserva['telefone']); // mantém só números

    if (!empty($cliente_numero)) {

        // Mensagem para o cliente
        $msgCliente = "Olá {$reserva['nome']}!\n\n" .
            "A sua reserva foi *confirmada*.\n\n" .
            "📅 Data: {$reserva['data_reserva']}\n" .
            "🕒 Hora: {$reserva['hora_reserva']}\n" .
            "👥 Pessoas: {$reserva['numero_pessoas']}\n\n" .
            "Obrigado por escolher o Cantinho Deolinda!";

        // Carregar configs do WhatsApp
        $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/Seguranca/config.env"); // Lê o ficheiro config.env usando o caminho absoluto do servidor (public_html/Seguranca)
        $token = $env['META_TOKEN'];
        $phone_id = $env['PHONE_NUMBER_ID'];

        // Endpoint oficial
        $url = "https://graph.facebook.com/v20.0/{$phone_id}/messages";

        // Payload (dados) a enviar
        $payload_cliente = [
            "messaging_product" => "whatsapp",
            "to" => $cliente_numero,
            "type" => "text",
            "text" => ["body" => $msgCliente]
        ];

        // Enviar via cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_cliente));
        $resp_cliente = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // DEBUG opcional (se quiseres ver erros da API)
        
    echo "<pre>";
    echo "HTTP CODE: $http_code\n";
    echo "RESPOSTA API:\n";
    print_r($resp_cliente);
    echo "</pre>";
    exit;
    
    }
    /*


    /* ==========================
       Finalização
       ========================== */

    echo "<script>
            alert('Reserva confirmada! Email enviado ao cliente. | Mensagem enviada ao Dono');
            window.location.href='confirmar_reservas.php';
          </script>";
    exit;
}



// Recusa reserva
if (isset($_GET['recusar'])) {
    $id = $_GET['recusar'];
    $sql = "DELETE FROM reservas WHERE id=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "<script>alert('Reserva recusada!'); window.location.href='confirmar_reservas.php';</script>";
}
