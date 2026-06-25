<?php
require __DIR__ . "/../config.php";
require_once __DIR__ . '/email_template_helper.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../phpmailer/src/Exception.php';
require __DIR__ . '/../phpmailer/src/PHPMailer.php';
require __DIR__ . '/../phpmailer/src/SMTP.php';

$env = parse_ini_file(__DIR__ . "/../Seguranca/config.env");

function podeEnviarFeedback(mysqli $con, int $clienteId): bool
{
    $sql = "
        SELECT 1
        FROM reservas
        WHERE cliente_id = ?
          AND confirmado = 1
          AND TIMESTAMP(data_reserva, hora_reserva) <= NOW()
        LIMIT 1
    ";

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $clienteId);
    $stmt->execute();
    $stmt->store_result();
    $ok = $stmt->num_rows > 0;
    $stmt->close();

    return $ok;
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    exit;
}

$clienteId = (int) $_SESSION['id'];
if (!podeEnviarFeedback($con, $clienteId)) {
    http_response_code(403);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    exit;
}

$nome = $data['nome'] ?? '';
$email = $data['email'] ?? '';
$assunto = $data['assunto'] ?? '';
$mensagem = $data['mensagem'] ?? '';

try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $env['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $env['SMTP_USER'];
    $mail->Password = $env['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($env['SMTP_USER'], "Cantinho Deolinda");
    $mail->addReplyTo($email, $nome);
    $mail->addAddress($env['SMTP_ADMIN']);

    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->isHTML(true);
    $mail->Subject = "Novo contacto: $assunto";
    $mail->Body = cd_email_template(
        'Novo contacto',
        'Mensagem recebida pelo site',
        'Entrou um novo contacto atraves do formulario do Cantinho Deolinda.',
        '
            <p style="margin:0 0 16px;">Segue abaixo o resumo da mensagem recebida.</p>
            ' . cd_email_detail_rows([
                'Nome' => $nome,
                'Email' => $email,
                'Assunto' => $assunto,
                'Mensagem' => $mensagem,
            ]) . '
        '
    );

    $mail->send();
} catch (Exception $e) {
}
