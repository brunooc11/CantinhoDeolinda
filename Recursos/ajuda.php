<?php
require("../config.php");

// Se existir pagina anterior (ex: login, index, menu) volta para la.
// Caso o utilizador tenha aberto a pagina diretamente, volta por defeito para o login.
$voltar = $_SERVER['HTTP_REFERER'] ?? '../login.php';

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajuda - Cantinho_Deolinda</title>
    <link rel="stylesheet" href="../Css/home.css">
    <link rel="stylesheet" href="../Css/bttlogin.css">
    <link rel="stylesheet" href="../Css/ajuda.css">
</head>
<body class="pagina-ajuda">
    <div class="container-ajuda">
        <h1>Centro de Ajuda</h1>

        <p>Esta pagina foi criada para apoiar os utilizadores com as duvidas mais comuns sobre o Cantinho_Deolinda.</p>

        <h2>1. Reservas</h2>
        <p>Pode fazer a sua reserva no site atraves do botao "Reserva Agora". Caso exista algum erro, tente novamente ou contacte-nos.</p>

        <h2>2. Conta e Login</h2>
        <p>Se nao conseguir entrar na conta, verifique e-mail e palavra-passe. Tambem pode usar a opcao de recuperacao de conta.</p>

        <h2>3. Pedidos e Atendimento</h2>
        <p>Para duvidas sobre pedidos, horarios, disponibilidade ou eventos, fale connosco pelos canais de contacto do site.</p>

        <h2>4. Suporte Tecnico</h2>
        <p>Se o site nao carregar corretamente, atualize a pagina e confirme a ligacao a internet. Se continuar, entre em contacto com a equipa.</p>

        <h2>5. Contacto</h2>
        <p>Para ajuda direta, envie mensagem para: cantinhodeolina@gmail.com.</p>

        <a href="<?php echo $voltar; ?>" id="btt-ajuda" class="btt-padrao-login">Voltar</a>
    </div>
</body>
</html>
