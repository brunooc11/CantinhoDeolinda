<?php
session_start();
require("../Bd/ligar.php");

// PHPMailer
require("../phpmailer/class.phpmailer.php");
require("../phpmailer/class..php");

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
            "INSERT INTO _resets (email, token, expires_at)
            VALUES ('%s','%s','%s')",
            $email, $token, $expires
        );
        mysqli_query($con, $sql_token);

        $link = "http://aluno15696.damiaodegoes.pt/recuperacao/reset_.php?token=$token";

        $mensagem = "
        <h3>Recuperação de Password</h3>
        Clique no link abaixo para redefinir a sua :<br><br>
        <a href='$link'>$link</a><br><br>
        Este link expira em 30 minutos.
        ";

        // CONFIGURAR PHPMailer
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->Host = "mail.damiaodegoes.pt";
        $mail->SMTPAuth = true;
        $mail->Username = "aluno15696@damiaodegoes.pt";
        $mail->Password = "slbcarvalho44";   // 🔥 Trocar pela  real
        $mail->SMTPSecure = "tls";
        $mail->Port = 587;

        $mail->From = "aluno15696@damiaodegoes.pt";
        $mail->FromName = "Cantinho Deolinda";
        $mail->AddAddress($email);

        $mail->IsHTML(true);
        $mail->Subject = "Recuperação de Password";
        $mail->Body = $mensagem;

        if ($mail->Send()) {
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
    <div class="form-container" style="width:100%;">

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
</div>
