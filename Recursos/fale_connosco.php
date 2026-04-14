<?php
require("../config.php");
require_once(__DIR__ . "/../theme.php");
$voltar = $_SERVER['HTTP_REFERER'] ?? '../index.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../Imagens/logo_atual.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fale Connosco - Cantinho Deolinda</title>
    <link rel="stylesheet" href="../Css/home.css">
    <link rel="stylesheet" href="../Css/bttlogin.css">
    <link rel="stylesheet" href="../Css/recursos_institucional.css?v=<?php echo filemtime(__DIR__ . '/../Css/recursos_institucional.css'); ?>">
    <?php cd_render_theme_head('../', dirname(__DIR__)); ?>
</head>
<body class="pagina-institucional">
    <?php cd_render_theme_toggle('../'); ?>
    <div class="institution-shell">
        <a href="<?php echo htmlspecialchars($voltar, ENT_QUOTES, 'UTF-8'); ?>" class="btt-padrao-login institution-back">Voltar</a>

        <section class="institution-hero">
            <div class="institution-hero-copy">
                <span class="institution-kicker">Contacto</span>
                <h1>Fale Connosco</h1>
                <p>Se precisares de falar com o restaurante, esta página resume as formas mais adequadas de contacto e o tipo de assunto que pode ser tratado.</p>
            </div>
            <div class="institution-hero-card">
                <span class="institution-hero-label">Estamos disponíveis</span>
                <strong>O contacto deve ser simples, cordial e orientado para ajudar o cliente da forma mais direta possível.</strong>
                <p>Podes utilizar os canais mais adequados conforme o tipo de questão.</p>
            </div>
        </section>

        <div class="institution-layout">
            <aside class="institution-side-card">
                <span class="institution-side-label">Nesta página</span>
                <ul class="institution-side-list">
                    <li><a href="#contacto-reservas">Questões sobre reservas</a></li>
                    <li><a href="#contacto-atendimento">Atendimento geral</a></li>
                    <li><a href="#contacto-feedback">Sugestões e feedback</a></li>
                    <li><a href="#contacto-canais">Canais de contacto</a></li>
                </ul>
            </aside>

            <div class="institution-grid">
                <article class="institution-card" id="contacto-reservas">
                    <span class="institution-card-index">01</span>
                    <h2>Questões sobre reservas</h2>
                    <p>Para dúvidas sobre reservas, alterações ou confirmações, o ideal é usar os canais oficiais do restaurante e indicar os dados essenciais do pedido.</p>
                </article>

                <article class="institution-card" id="contacto-atendimento">
                    <span class="institution-card-index">02</span>
                    <h2>Atendimento geral</h2>
                    <p>Questões sobre horários, funcionamento, disponibilidade ou experiência no restaurante devem ser comunicadas de forma clara para facilitar uma resposta útil.</p>
                </article>

                <article class="institution-card" id="contacto-feedback">
                    <span class="institution-card-index">03</span>
                    <h2>Sugestões e feedback</h2>
                    <p>O restaurante valoriza opiniões construtivas. Comentários e sugestões ajudam a melhorar o serviço, a experiência e a relação com os clientes.</p>
                </article>

                <article class="institution-card" id="contacto-canais">
                    <span class="institution-card-index">04</span>
                    <h2>Canais de contacto</h2>
                    <p>Quando necessário, utiliza os contactos disponíveis no site ou o email <strong>cantinhodeolinda@gmail.com</strong> para assuntos que exijam acompanhamento mais direto.</p>
                </article>
            </div>
        </div>
    </div>
    <?php cd_render_theme_script('../', dirname(__DIR__)); ?>
</body>
</html>
