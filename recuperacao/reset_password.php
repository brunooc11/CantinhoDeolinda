<?php
require("../config.php");  
require("../Bd/ligar.php");

$erro = "";
$sucesso = false;

if (!isset($_GET['token']) && !isset($_POST['token'])) {
    die("Token inválido.");
}

$token = isset($_GET['token']) ? $_GET['token'] : $_POST['token'];

if (isset($_POST['alterar'])) {

    $nova = $_POST['password'];
    $hash = password_hash($nova, PASSWORD_DEFAULT);

    $sql = sprintf("SELECT * FROM password_resets WHERE token='%s' AND expires_at > NOW()", $token);
    $res = mysqli_query($con, $sql);

    if (mysqli_num_rows($res) == 1) {

        $dados = mysqli_fetch_assoc($res);
        $email = $dados['email'];

        $sql_update = sprintf("UPDATE Cliente SET password='%s' WHERE email='%s'", $hash, $email);
        mysqli_query($con, $sql_update);

        mysqli_query($con, sprintf("DELETE FROM password_resets WHERE token='%s'", $token));

        $sucesso = true;

    } else {
        $erro = "Token inválido ou expirado.";
    }
}
?>
<link rel="stylesheet" href="../Css/login.css">

<a href="../login.php" class="btn-voltar">← Voltar</a>
<h2>Nova Password</h2>

<div class="container" style="max-width:450px; padding:40px; margin-top:20px;">
        <?php if ($sucesso): ?>
            <form style="text-align:center;">
                <h1 style="color:#f5b631;">Password alterada!</h1>
                <p>Pode agora iniciar sessão.</p>
                <button type="button" class="btn-voltar" id="btn-centro" onclick="window.location.href='../login.php'">
                    Fazer Login
                </button>
            </form>

        <?php else: ?>
            <form method="POST">
                <h1>Nova Password</h1>

                <input type="hidden" name="token" value="<?php echo $token; ?>">
                <input type="password" name="password" placeholder="Nova password" required minlength="5">

                <?php if ($erro): ?>
                    <p style="color:#ff5757;"><?php echo $erro; ?></p>
                <?php endif; ?>

                <button type="submit" name="alterar" class="btn-voltar" id="btn-centro">
                    Confirmar
                </button>
            </form>
        <?php endif; ?>
</div>
