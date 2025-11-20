<?php
require('Bd/ligar.php');
require("config.php");  

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $sql = "SELECT id FROM Cliente WHERE token_verificacao_conta = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) > 0) {
        // Atualiza conta para verificada
        $update = "UPDATE Cliente SET verificado = 1, token_verificacao_conta = NULL WHERE token_verificacao_conta = ?";
        $stmt2 = mysqli_prepare($con, $update);
        mysqli_stmt_bind_param($stmt2, "s", $token);
        mysqli_stmt_execute($stmt2);

        echo "<script>
                alert('✔ Conta verificada com sucesso! Já pode fazer login.');
                window.location.href = 'login.php';
              </script>";
    } else {
        echo "<script>
                alert('❌ Link inválido ou já usado.');
                window.location.href = 'login.php';
              </script>";
    }
} else {
    echo "<script>
            alert('❌ Token não fornecido.');
            window.location.href = 'login.php';
          </script>";
}
?>
