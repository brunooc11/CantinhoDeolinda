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
    <title>Serviços - Cantinho Deolinda</title>
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
                <span class="institution-kicker">Oferta da casa</span>
                <h1>Serviços</h1>
                <p>Reunimos aqui as principais formas de atendimento e os tipos de experiência que o restaurante pode disponibilizar aos seus clientes.</p>
            </div>
            <div class="institution-hero-card">
                <span class="institution-hero-label">Experiência</span>
                <strong>Serviços pensados para combinar conforto, organização e boa hospitalidade.</strong>
                <p>O objetivo é facilitar a relação entre o cliente e o restaurante em cada momento.</p>
            </div>
        </section>

        <div class="institution-layout">
            <aside class="institution-side-card">
                <span class="institution-side-label">Nesta página</span>
                <ul class="institution-side-list">
                    <li><a href="#servicos-reservas">Reservas</a></li>
                    <li><a href="#servicos-sala">Atendimento em sala</a></li>
                    <li><a href="#servicos-grupos">Grupos e ocasiões</a></li>
                    <li><a href="#servicos-digital">Apoio digital</a></li>
                </ul>
            </aside>

            <div class="institution-grid">
                <article class="institution-card" id="servicos-reservas">
                    <span class="institution-card-index">01</span>
                    <h2>Reservas</h2>
                    <p>O site permite organizar reservas com mais rapidez, ajudando a equipa a gerir melhor os pedidos e a preparar a receção dos clientes.</p>
                </article>

                <article class="institution-card" id="servicos-sala">
                    <span class="institution-card-index">02</span>
                    <h2>Atendimento em sala</h2>
                    <p>O foco do restaurante está no serviço presencial, com atenção ao ambiente, à experiência da mesa e à qualidade do acompanhamento dado durante a refeição.</p>
                </article>

                <article class="institution-card" id="servicos-grupos">
                    <span class="institution-card-index">03</span>
                    <h2>Grupos e ocasiões</h2>
                    <p>Quando aplicável, o restaurante pode acolher pedidos para grupos, pequenos eventos ou momentos especiais, sempre sujeitos à disponibilidade e organização interna.</p>
                </article>

                <article class="institution-card" id="servicos-digital">
                    <span class="institution-card-index">04</span>
                    <h2>Apoio digital</h2>
                    <p>O canal online funciona como apoio complementar, simplificando reservas, pedidos de contacto e o acesso a informação útil sobre o restaurante.</p>
                </article>
            </div>
        </div>
    </div>
    <?php cd_render_theme_script('../', dirname(__DIR__)); ?>
</body>
</html>
