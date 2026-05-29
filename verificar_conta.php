<?php
require('Bd/ligar.php');
require("config.php");  
require_once("Bd/popup_helper.php");

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $update = "UPDATE Cliente SET verificado = 1, token_verificacao_conta = NULL WHERE token_verificacao_conta = ? AND verificado = 0";
    $stmt = mysqli_prepare($con, $update);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $afetados = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($afetados > 0) {
        cd_popup('Conta verificada com sucesso! Já pode fazer login.', 'success', 'login.php');
    } else {
        cd_popup('Link inválido ou já usado.', 'error', 'login.php');
    }
} else {
    cd_popup('Token não fornecido.', 'error', 'login.php');
}
?>
