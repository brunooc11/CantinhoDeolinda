<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$env = parse_ini_file("Seguranca/config.env");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = htmlspecialchars($_POST['nome']);
    $email = htmlspecialchars($_POST['email']);
    $assunto = htmlspecialchars($_POST['assunto']);
    $mensagem = nl2br(htmlspecialchars($_POST['mensagem']));

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $env['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $env['SMTP_USER'];  // GMAIL DA TUA CONTA
        $mail->Password = $env['SMTP_PASS'];  // APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // REMETENTE TEM DE SER O TEU GMAIL — OBRIGATÓRIO
        $mail->setFrom($env['SMTP_USER'], "Cantinho Deolinda");

        // O email do cliente vai em Reply-To (para poderes responder)
        $mail->addReplyTo($email, $nome);

        // Destino — o teu Gmail
        $mail->addAddress($env['SMTP_USER']);

        $mail->isHTML(true);
        $mail->Subject = "Novo contacto: $assunto";
        $mail->Body = $mail->Body = $mail->Body = $mail->Body = "

<table width='100%' cellpadding='0' cellspacing='0' style='font-family:Poppins,Arial,sans-serif;background:#1a1a1a;padding:40px 0;'>
  <tr>
    <td align='center'>

      <table width='600' cellpadding='0' cellspacing='0' style='background:#111;border-radius:14px;overflow:hidden;color:#fff;box-shadow:0 0 20px rgba(0,0,0,0.4);'>

        <!-- HEADER -->
        <tr>
          <td style='background:#f4b942;padding:25px;text-align:center;'>
            <img src='cid:logo_cantinho' 
                 alt='Cantinho Deolinda'
                 style='width:90px;height:auto;margin-bottom:10px;'>
            <div style='font-size:26px;font-weight:700;color:#111;'>Cantinho Deolinda</div>
          </td>
        </tr>

        <tr>
          <td style='padding:30px;padding-bottom:10px;'>
            <h2 style='margin:0;color:#f4b942;font-size:24px;text-align:center;font-weight:600;'>
              📩 Nova Mensagem de Contacto
            </h2>
          </td>
        </tr>

        <tr>
          <td style='padding:20px 35px;font-size:16px;line-height:1.8;color:#ddd;'>

            <p><strong style='color:#f4b942;'>Nome:</strong> {$nome}</p>
            <p><strong style='color:#f4b942;'>Email:</strong> {$email}</p>
            <p><strong style='color:#f4b942;'>Assunto:</strong> {$assunto}</p>

            <div style='margin-top:25px;padding:20px;background:#1f1f1f;border-left:5px solid #f4b942;border-radius:10px;'>
              <strong style='color:#f4b942;'>Mensagem:</strong><br><br>
              <span style='color:#ccc;'>{$mensagem}</span>
            </div>

          </td>
        </tr>

        <tr>
          <td style='background:#000;padding:20px;text-align:center;font-size:14px;color:#f4b942;'>
            © " . date("Y") . " Cantinho Deolinda • Mensagem enviada através do website
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

";


        $mail->send();
        echo "OK";
        exit;

    } catch (Exception $e) {
        echo "ERRO";
        exit;
    }
}

echo "ERRO";
