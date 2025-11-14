<?php
session_start();
require("../Bd/ligar.php");

// PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require("../phpmailer/src/PHPMailer.php");
require("../phpmailer/src/SMTP.php");
require("../phpmailer/src/Exception.php");

$env = parse_ini_file("../Seguranca/config.env");   

$msg = "";
$enviado = false;

if (isset($_POST['recuperar'])) {

    $email = mysqli_real_escape_string($con, $_POST['email']);

    $sql = sprintf("SELECT * FROM Cliente WHERE email='%s'", $email);
    $res = mysqli_query($con, $sql);

    if (mysqli_num_rows($res) == 1) {

        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 1800);

        $sql_token = sprintf(
            "INSERT INTO password_resets (email, token, expires_at)
            VALUES ('%s','%s','%s')",
            $email,
            $token,
            $expires
        );
        mysqli_query($con, $sql_token);

        $link = "http://aluno15696.damiaodegoes.pt/recuperacao/reset_password.php?token=$token";

        $mensagem = "
        <h3>Recuperação de Password</h3>
        Clique no link abaixo para redefinir a sua password:<br><br>
        <a href='$link'>$link</a><br><br>
        Este link expira em 30 minutos.
        ";

        // CONFIGURAR PHPMailer
        $mail = new PHPMailer(true);
/*
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html'; // debug adicional
*/
        $mail->isSMTP();
        $mail->Host = $env['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $env['SMTP_USER'];
        $mail->Password = $env['SMTP_PASS']; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($env['SMTP_FROM'], $env['SMTP_FROM_NAME']);
        $mail->addAddress($email);

        $mail->IsHTML(true);
        $mail->Subject = "Recuperacao de Password";
        $mail->Body = $mensagem;

        if ($mail->send()) {
            $enviado = true;
        } else {
            $msg = "Erro ao enviar email: " . $mail->ErrorInfo;
        }
    } else {
        $msg = "Email não encontrado.";
    }
}
?>
<link rel="stylesheet" href="../Css/login.css">

<a href="../login.php" class="btn-voltar">← Voltar</a>
<h2>Recuperar Password</h2>

<div class="container" style="max-width:450px; padding:40px; margin-top:20px;">
        <?php if ($enviado): ?>
            <form style="text-align:center;">
                <h1 style="color:#f5b631;">Email enviado!</h1>
                <p>Verifique o seu email para continuar.</p>
                <button type="button" class="btn-voltar" id="btn-centro" onclick="window.location.href='../login.php'">
                    Fazer Login
                </button>
            </form>

        <?php else: ?>
            <form method="POST">
                <h1>Recuperar</h1>
                <input type="email" name="email" placeholder="Email" required>

                <?php if ($msg): ?>
                    <p style="color:#ff5757;"><?php echo $msg; ?></p>
                <?php endif; ?>

                <button type="submit" name="recuperar" class="btn-voltar" id="btn-centro">
                    Enviar Link
                </button>
            </form>
        <?php endif; ?>
</div>