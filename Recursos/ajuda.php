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
    <link rel="icon" type="image/png" href="../Imagens/logo_atual.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajuda - Cantinho Deolinda</title>
    <link rel="stylesheet" href="../Css/home.css">
    <link rel="stylesheet" href="../Css/bttlogin.css">
    <link rel="stylesheet" href="../Css/ajuda.css?v=<?php echo filemtime(__DIR__ . '/../Css/ajuda.css'); ?>">
</head>
<body class="pagina-ajuda">
    <div class="help-shell">
        <a href="<?php echo htmlspecialchars($voltar, ENT_QUOTES, 'UTF-8'); ?>" id="btt-ajuda" class="btt-padrao-login">Voltar</a>

        <section class="help-hero">
            <div class="help-hero-copy">
                <span class="help-kicker">Centro de apoio</span>
                <h1>Ajuda</h1>
                <p>Reunimos aqui respostas diretas para as situações mais comuns do site, desde reservas e conta até apoio técnico.</p>
            </div>
            <div class="help-hero-card">
                <span class="help-hero-label">Apoio rápido</span>
                <strong>Precisas de orientação? Começa pelos temas abaixo.</strong>
                <p>Se não encontrares a resposta certa, podes sempre contactar a equipa.</p>
            </div>
        </section>

        <div class="help-layout">
            <aside class="help-side-card">
                <span class="help-side-label">Ajuda</span>
                <ul class="help-side-list">
                    <li><a href="#ajuda-reservas">Reservas</a></li>
                    <li><a href="#ajuda-conta-login">Conta e login</a></li>
                    <li><a href="#ajuda-pedidos">Pedidos e atendimento</a></li>
                    <li><a href="#ajuda-final">Suporte e contacto</a></li>
                </ul>
            </aside>

        <div class="help-grid">
            <article class="help-card" id="ajuda-reservas">
                <span class="help-card-index">01</span>
                <h2>Reservas</h2>
                <p>Podes fazer a tua reserva diretamente no site. Se houver falha no processo, confirma os dados preenchidos e tenta novamente alguns instantes depois.</p>
            </article>

            <article class="help-card" id="ajuda-conta-login">
                <span class="help-card-index">02</span>
                <h2>Conta e Login</h2>
                <p>Se não conseguires entrar, confirma o email e a palavra-passe. Verifica também se a conta está ativa e se escreveste os dados corretamente.</p>
            </article>

            <article class="help-card" id="ajuda-pedidos">
                <span class="help-card-index">03</span>
                <h2>Pedidos e Atendimento</h2>
                <p>Para questões relacionadas com horários, disponibilidade, eventos ou atendimento, utiliza os contactos disponíveis no site do restaurante.</p>
            </article>

            <article class="help-card" id="ajuda-suporte">
                <span class="help-card-index">04</span>
                <h2>Suporte Técnico</h2>
                <p>Se a página não carregar corretamente, atualiza o navegador, verifica a ligação à internet e tenta novamente. Se o problema persistir, entra em contacto.</p>
            </article>

            <article class="help-card help-card-wide" id="ajuda-final">
                <span class="help-card-index">05</span>
                <h2>Contacto</h2>
                <p>Se precisares de ajuda direta, envia mensagem para <strong>cantinhodeolina@gmail.com</strong>. A equipa poderá orientar-te de forma mais personalizada.</p>
            </article>
        </div>
        </div>
    </div>
</body>
</html>
