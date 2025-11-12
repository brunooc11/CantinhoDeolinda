<?php
// Inicia a sessão
session_start();

// Apaga todas as variáveis de sessão
$_SESSION = [];

// Destroi a sessão
session_destroy();

// Apaga o cookie de sessão do navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 3600,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redireciona para o index
header("Location: index.php");
exit();
?>
