<?php
require("../config.php");
require("../Bd/ligar.php");
require_once("../Bd/popup_helper.php");

$erro = "";
$sucesso = false;

if (empty($_SESSION['csrf_token_reset'])) {
    $_SESSION['csrf_token_reset'] = bin2hex(random_bytes(32));
}

if (!isset($_GET['token']) && !isset($_POST['token'])) {
    cd_popup('Token inválido.', 'error', '../login.php');
    exit();
}

$token = isset($_GET['token']) ? (string)$_GET['token'] : (string)$_POST['token'];

if (isset($_POST['alterar'])) {
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    $sessionCsrf = (string)($_SESSION['csrf_token_reset'] ?? '');
    if ($csrfToken === '' || $sessionCsrf === '' || !hash_equals($sessionCsrf, $csrfToken)) {
        $erro = 'Pedido inválido. Atualize a página e tente novamente.';
    } else {
        $nova = (string)($_POST['password'] ?? '');

        $passwordValida = (
            strlen($nova) >= 8 &&
            preg_match('/[A-Z]/', $nova) &&
            preg_match('/[a-z]/', $nova) &&
            preg_match('/[0-9]/', $nova) &&
            preg_match('/[^A-Za-z0-9]/', $nova)
        );

        if (!$passwordValida) {
            $erro = 'A palavra-passe deve ter, no mínimo, 8 caracteres, incluindo maiúscula, minúscula, número e símbolo.';
        } else {
            $hash = password_hash($nova, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare(
                $con,
                "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1"
            );

            if (!$stmt) {
                cd_popup('Erro interno. Tente novamente.', 'error');
                exit();
            }

            mysqli_stmt_bind_param($stmt, "s", $token);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $dados = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if ($dados && isset($dados['email'])) {
                $email = (string)$dados['email'];

                $stmtUpdate = mysqli_prepare($con, "UPDATE Cliente SET password = ? WHERE email = ?");
                if ($stmtUpdate) {
                    mysqli_stmt_bind_param($stmtUpdate, "ss", $hash, $email);
                    mysqli_stmt_execute($stmtUpdate);
                    mysqli_stmt_close($stmtUpdate);
                }

                // Invalida todos os tokens deste e-mail após alteração.
                $stmtDelete = mysqli_prepare($con, "DELETE FROM password_resets WHERE email = ?");
                if ($stmtDelete) {
                    mysqli_stmt_bind_param($stmtDelete, "s", $email);
                    mysqli_stmt_execute($stmtDelete);
                    mysqli_stmt_close($stmtDelete);
                }

                $sucesso = true;
            } else {
                $erro = 'Token inválido ou expirado.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../Imagens/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Palavra-passe</title>
    <link rel="stylesheet" href="../Css/bttlogin.css">
    <link rel="stylesheet" href="../Css/recovery.css">
</head>
<body>
    <a href="../login.php" id="btnVoltarRecovery" class="btt-padrao-login">&larr; Voltar</a>
    <main class="recovery-shell">
        <section class="recovery-hero">
            <span class="recovery-badge">Atualização segura</span>
            <h1 class="recovery-title">Definir nova palavra-passe</h1>
            <p class="recovery-copy">
                Escolha uma palavra-passe forte para proteger a sua conta e continuar a usar a plataforma sem interrupções.
            </p>
            <ul class="recovery-points">
                <li>Mínimo 8 caracteres</li>
                <li>Pelo menos 1 maiúscula, 1 minúscula e 1 número</li>
                <li>Pelo menos 1 símbolo especial</li>
            </ul>
        </section>

        <section class="recovery-card">
            <?php if ($sucesso): ?>
                <div class="recovery-form is-success">
                    <h2>Palavra-passe alterada</h2>
                    <p class="recovery-sub">
                        A sua nova palavra-passe já está ativa.
                    </p>
                    <p class="recovery-msg success">
                        Alteração concluída com sucesso.
                    </p>
                    <a href="../login.php" class="recovery-btn recovery-success-cta">Entrar na conta</a>
                </div>
            <?php else: ?>
                <form method="POST" class="recovery-form">
                    <h2>Nova palavra-passe</h2>
                    <p class="recovery-sub">
                        Introduza a nova palavra-passe para finalizar a recuperação.
                    </p>

                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token_reset'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="recovery-input-wrap">
                        <span class="recovery-input-icon">*</span>
                        <input
                            id="novaPassword"
                            class="recovery-input"
                            type="password"
                            name="password"
                            placeholder="Nova palavra-passe"
                            autocomplete="new-password"
                            required
                            minlength="8"
                            pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                        >
                    </div>
                    <ul class="password-rules" id="passwordRules">
                        <li data-rule="len">Mínimo 8 caracteres</li>
                        <li data-rule="upper">Pelo menos 1 maiúscula</li>
                        <li data-rule="lower">Pelo menos 1 minúscula</li>
                        <li data-rule="number">Pelo menos 1 número</li>
                        <li data-rule="symbol">Pelo menos 1 símbolo</li>
                    </ul>

                    <?php if ($erro): ?>
                        <p class="recovery-msg error"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <button type="submit" name="alterar" class="recovery-btn">Guardar palavra-passe</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
<script>
    (function() {
        const input = document.getElementById('novaPassword');
        const rules = document.getElementById('passwordRules');
        if (!input || !rules) return;

        const items = {
            len: rules.querySelector('[data-rule="len"]'),
            upper: rules.querySelector('[data-rule="upper"]'),
            lower: rules.querySelector('[data-rule="lower"]'),
            number: rules.querySelector('[data-rule="number"]'),
            symbol: rules.querySelector('[data-rule="symbol"]')
        };

        const setValid = (el, ok) => {
            if (!el) return;
            el.classList.toggle('valid', !!ok);
        };

        const check = () => {
            const v = input.value || '';
            setValid(items.len, v.length >= 8);
            setValid(items.upper, /[A-Z]/.test(v));
            setValid(items.lower, /[a-z]/.test(v));
            setValid(items.number, /[0-9]/.test(v));
            setValid(items.symbol, /[^A-Za-z0-9]/.test(v));
        };

        input.addEventListener('input', check);
        check();
    })();
</script>
</html>
