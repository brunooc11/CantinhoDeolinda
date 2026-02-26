<?php
require("../config.php");  

// Se existir página anterior (login, index, menu, etc.) volta para lá.
// Caso a página seja aberta diretamente, volta por defeito para o login.
$voltar = $_SERVER['HTTP_REFERER'] ?? '../login.php';

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../Imagens/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso - Cantinho_Deolinda</title>
    <link rel="stylesheet" href="../Css/home.css">
    <link rel="stylesheet" href="../Css/bttlogin.css">
    <link rel="stylesheet" href="../Css/termos.css">
</head>
<body class="pagina-termos">
    <div class="container-termos">
        <h1>Termos de Uso</h1>

        <p>Bem-vindo ao Cantinho_Deolinda! Ao acessar e utilizar nosso site, você concorda com os termos e condições descritos abaixo. Caso não concorde, por favor, não utilize nosso site.</p>

        <h2>1. Uso do Site</h2>
        <p>O conteúdo disponibilizado neste site é destinado apenas para fins informativos e para realizar pedidos em nosso restaurante. Você concorda em utilizar o site de forma legal e responsável, sem violar quaisquer leis aplicáveis.</p>

        <h2>2. Cadastro e Conta</h2>
        <p>Para realizar pedidos ou acessar funcionalidades exclusivas, é necessário criar uma conta. Você é responsável por manter a confidencialidade de suas informações de login e por todas as atividades realizadas em sua conta.</p>

        <h2>3. Responsabilidade do Usuário</h2>
        <p>O usuário se compromete a fornecer informações verdadeiras e precisas. Não é permitido enviar conteúdo ilegal, ofensivo ou que viole direitos de terceiros.</p>

        <h2>4. Propriedade Intelectual</h2>
        <p>Todo o conteúdo do site, incluindo textos, imagens, logotipos e design, é de propriedade do Cantinho_Deolinda ou de terceiros licenciantes e está protegido por direitos autorais.</p>

        <h2>5. Modificações</h2>
        <p>O Cantinho_Deolinda reserva-se o direito de alterar estes termos de uso a qualquer momento. Alterações serão publicadas neste site e entrarão em vigor imediatamente.</p>

        <h2>6. Limitação de Responsabilidade</h2>
        <p>O Cantinho_Deolinda não se responsabiliza por danos diretos ou indiretos decorrentes do uso do site, falhas técnicas ou informações incorretas.</p>

        <h2>7. Contato</h2>
        <p>Para dúvidas sobre os termos de uso, entre em contato conosco através do e-mail: cantinhodeolina@gmail.com.</p>

        <!-- regressa à página anterior do utilizador -->
        <a href="<?php echo $voltar; ?>" id="btt-termos" class="btt-padrao-login">Voltar</a>
    </div>
</body>
</html>
