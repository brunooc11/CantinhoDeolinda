<?php
require("../config.php");  

// Se existir página anterior (ex: login, index, menu) volta para lá.
// Caso o utilizador tenha aberto a página diretamente, volta por defeito para o login.
$voltar = $_SERVER['HTTP_REFERER'] ?? '../login.php';

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - Cantinho_Deolinda</title>
    <link rel="stylesheet" href="../Css/home.css">
    <link rel="stylesheet" href="../Css/politica.css">
</head>
<body>
    <div class="container-privacidade">
        <h1>Política de Privacidade</h1>

        <p>No Cantinho_Deolinda, sua privacidade é muito importante. Esta política descreve como coletamos, usamos e protegemos suas informações.</p>

        <h2>1. Informações Coletadas</h2>
        <p>Coletamos informações pessoais fornecidas por você ao criar uma conta, realizar pedidos ou se inscrever em nossos serviços, como nome, e-mail, telefone e endereço.</p>

        <h2>2. Uso das Informações</h2>
        <p>As informações coletadas são utilizadas para processar pedidos, melhorar nossos serviços, enviar atualizações e responder a solicitações de suporte.</p>

        <h2>3. Proteção de Dados</h2>
        <p>Implementamos medidas de segurança para proteger suas informações pessoais contra acesso não autorizado, alteração, divulgação ou destruição.</p>

        <h2>4. Compartilhamento de Informações</h2>
        <p>Não vendemos ou compartilhamos suas informações pessoais com terceiros, exceto quando necessário para cumprir obrigações legais ou fornecer serviços contratados.</p>

        <h2>5. Cookies</h2>
        <p>O site pode utilizar cookies para melhorar a experiência do usuário, lembrar preferências e analisar o tráfego do site.</p>

        <h2>6. Direitos do Usuário</h2>
        <p>Você pode acessar, corrigir ou solicitar a exclusão de suas informações pessoais entrando em contato conosco pelo e-mail: contato@cantinhodeolinda.com.</p>

        <h2>7. Alterações</h2>
        <p>Esta política de privacidade pode ser atualizada periodicamente. As alterações serão publicadas nesta página.</p>

        <a href="<?php echo $voltar; ?>" class="voltar-btn">Voltar</a><!-- Botão do codigo lá de cima: regressa à página anterior do utilizador -->
    </div>
</body>
</html>