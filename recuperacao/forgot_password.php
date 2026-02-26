<?php
require("../config.php");
require("../Bd/ligar.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require("../phpmailer/src/PHPMailer.php");
require("../phpmailer/src/SMTP.php");
require("../phpmailer/src/Exception.php");

$env = parse_ini_file("../Seguranca/config.env");

$msg = "";
$enviado = false;

if (empty($_SESSION['csrf_token_recovery'])) {
    $_SESSION['csrf_token_recovery'] = bin2hex(random_bytes(32));
}

if (isset($_POST['recuperar'])) {
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    $sessionCsrf = (string)($_SESSION['csrf_token_recovery'] ?? '');
    $csrfOk = ($csrfToken !== '' && $sessionCsrf !== '' && hash_equals($sessionCsrf, $csrfToken));

    if ($csrfOk) {
        $email = trim((string)($_POST['email'] ?? ''));
        $clientIp = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);

        mysqli_query(
            $con,
            "CREATE TABLE IF NOT EXISTS password_reset_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                email VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_attempt_created_at (created_at),
                KEY idx_attempt_ip_email (ip_address, email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $windowStart = date("Y-m-d H:i:s", time() - 900); // 15 minutos
        $rateLimited = false;

        $rateStmt = mysqli_prepare(
            $con,
            "SELECT COUNT(*) FROM password_reset_attempts WHERE created_at >= ? AND (ip_address = ? OR email = ?)"
        );
        if ($rateStmt) {
            mysqli_stmt_bind_param($rateStmt, "sss", $windowStart, $clientIp, $email);
            mysqli_stmt_execute($rateStmt);
            mysqli_stmt_bind_result($rateStmt, $attemptCount);
            mysqli_stmt_fetch($rateStmt);
            mysqli_stmt_close($rateStmt);
            $rateLimited = ((int)$attemptCount >= 5);
        }

        $logStmt = mysqli_prepare(
            $con,
            "INSERT INTO password_reset_attempts (ip_address, email) VALUES (?, ?)"
        );
        if ($logStmt) {
            mysqli_stmt_bind_param($logStmt, "ss", $clientIp, $email);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);
        }

        if (!$rateLimited) {
            $stmt = mysqli_prepare($con, "SELECT id FROM Cliente WHERE email = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
            }

            if ($stmt && mysqli_stmt_num_rows($stmt) == 1) {
                $token = bin2hex(random_bytes(32));
                $expires = date("Y-m-d H:i:s", time() + 1800);

                $stmtToken = mysqli_prepare(
                    $con,
                    "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)"
                );
                if ($stmtToken) {
                    mysqli_stmt_bind_param($stmtToken, "sss", $email, $token, $expires);
                    mysqli_stmt_execute($stmtToken);
                    mysqli_stmt_close($stmtToken);
                }

                $link = "https://aluno15696.damiaodegoes.pt/recuperacao/reset_password.php?token=$token";

                $mensagem = "
                <h3>Recuperação de palavra-passe</h3>
                <p>Clique no link abaixo para redefinir a sua palavra-passe:</p>
                <p><a href='$link'>$link</a></p>
                <p>Este link expira em 30 minutos.</p>
                ";

                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $env['SMTP_HOST'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $env['SMTP_USER'];
                    $mail->Password = $env['SMTP_PASS'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom($env['SMTP_FROM'], $env['SMTP_FROM_NAME']);
                    $mail->addAddress($email);

                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';
                    $mail->isHTML(true);
                    $mail->Subject = "Recuperação de palavra-passe";
                    $mail->Body = $mensagem;
                    $mail->AltBody = "Recuperação de palavra-passe. Use este link para redefinir: $link (expira em 30 minutos).";
                    $mail->send();
                } catch (Exception $e) {
                    // Mantém resposta neutra.
                }
            }

            if ($stmt) {
                mysqli_stmt_close($stmt);
            }
        }
    }

    // Resposta neutra: nunca revelar se o e-mail existe.
    $enviado = true;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../Imagens/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Palavra-passe</title>
    <link rel="stylesheet" href="../Css/bttlogin.css">
    <link rel="stylesheet" href="../Css/recovery.css">
</head>
<body>
    <a href="../login.php" id="btnVoltarRecovery" class="btt-padrao-login">&larr; Voltar</a>
    <main class="recovery-shell">
        <section class="recovery-hero">
            <span class="recovery-badge">Conta segura</span>
            <h1 class="recovery-title">Recuperar acesso à sua conta</h1>
            <p class="recovery-copy">
                Introduza o seu e-mail e enviaremos um link para redefinir a palavra-passe de forma rápida e segura.
            </p>
            <ul class="recovery-points">
                <li>Link válido por 30 minutos</li>
                <li>Sem perda de dados na conta</li>
                <li>Processo protegido com token único</li>
            </ul>
        </section>

        <section class="recovery-card">
            <?php if ($enviado): ?>
                <div class="recovery-form is-success">
                    <h2>Email enviado</h2>
                    <p class="recovery-sub">
                        Se o e-mail existir, receberá instruções para redefinir a palavra-passe.
                    </p>
                    <p class="recovery-msg success">
                        Verifique a sua caixa de entrada e também o spam.
                    </p>
                    <a href="../login.php" class="recovery-btn recovery-success-cta">Iniciar sessão</a>
                </div>
            <?php else: ?>
                <form method="POST" class="recovery-form">
                    <h2>Recuperar palavra-passe</h2>
                    <p class="recovery-sub">
                        Introduza o e-mail associado à conta.
                    </p>

                    <div class="recovery-input-wrap">
                        <span class="recovery-input-icon">@</span>
                        <input
                            class="recovery-input"
                            type="email"
                            name="email"
                            placeholder="nome@exemplo.com"
                            autocomplete="email"
                            required
                        >
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token_recovery'], ENT_QUOTES, 'UTF-8'); ?>">

                    <?php if ($msg): ?>
                        <p class="recovery-msg error"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <button type="submit" name="recuperar" class="recovery-btn">Enviar link</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
