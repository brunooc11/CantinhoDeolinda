<?php
require __DIR__ . "/../config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../phpmailer/src/Exception.php';
require __DIR__ . '/../phpmailer/src/PHPMailer.php';
require __DIR__ . '/../phpmailer/src/SMTP.php';

$env = parse_ini_file(__DIR__ . "/../Seguranca/config.env");

// 🔥 LER DADOS DO sendBeacon()
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    file_put_contents(
        __DIR__ . "/debug_email.txt",
        "JSON INVALIDO: " . $raw . "\n",
        FILE_APPEND
    );
    exit;
}

// ✅ Agora as variáveis EXISTEM
$nome = $data['nome'];
$email = $data['email'];
$assunto = $data['assunto'];
$mensagem = $data['mensagem'];

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

    $mail->isHTML(true);
    $mail->Subject = "Novo contacto: $assunto";
    $mail->Body = "
<table width='100%' cellpadding='0' cellspacing='0' style='background:#1a1a1a;padding:40px 0;font-family:Arial;'>
  <tr>
    <td align='center'>

      <table width='600' style='background:#111;color:#fff;border-radius:12px;overflow:hidden;'>

        <tr>
          <td style='background:#f4b942;padding:20px;text-align:center;color:#111;font-size:24px;font-weight:bold;'>
            Cantinho Deolinda
          </td>
        </tr>

        <tr>
          <td style='padding:30px;font-size:16px;line-height:1.6;color:#ddd;'>
            <p><strong>Nome:</strong> {$nome}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Assunto:</strong> {$assunto}</p>

            <div style='margin-top:20px;padding:15px;background:#1f1f1f;border-left:4px solid #f4b942;'>
              {$mensagem}
            </div>
          </td>
        </tr>

        <tr>
          <td style='background:#000;padding:15px;text-align:center;font-size:13px;color:#f4b942;'>
            © " . date("Y") . " Cantinho Deolinda
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>
";

    $mail->send();
} catch (Exception $e) {
    file_put_contents(
        __DIR__ . "/debug_email.txt",
        "ERRO SMTP: " . $mail->ErrorInfo . "\n",
        FILE_APPEND
    );
}
