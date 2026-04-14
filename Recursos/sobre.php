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
    <title>Sobre Nós - Cantinho Deolinda</title>
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
                <span class="institution-kicker">Cantinho Deolinda</span>
                <h1>Sobre Nós</h1>
                <p>Partilhamos aqui a identidade do restaurante, a forma como recebemos cada cliente e o cuidado com que construímos a experiência à mesa.</p>
            </div>
            <div class="institution-hero-card">
                <span class="institution-hero-label">Essência</span>
                <strong>Uma casa pensada para servir com proximidade, sabor e atenção ao detalhe.</strong>
                <p>Mais do que refeições, procuramos criar memórias acolhedoras num ambiente familiar.</p>
            </div>
        </section>

        <div class="institution-layout">
            <aside class="institution-side-card">
                <span class="institution-side-label">Nesta página</span>
                <ul class="institution-side-list">
                    <li><a href="#sobre-historia">A nossa história</a></li>
                    <li><a href="#sobre-visao">Visão e valores</a></li>
                    <li><a href="#sobre-experiencia">Experiência no restaurante</a></li>
                    <li><a href="#sobre-equipa">Equipa e serviço</a></li>
                </ul>
            </aside>

            <div class="institution-grid">
                <article class="institution-card" id="sobre-historia">
                    <span class="institution-card-index">01</span>
                    <h2>A nossa história</h2>
                    <p>O Cantinho Deolinda nasce da vontade de criar um espaço confortável, com identidade própria e um ambiente onde a comida e o atendimento caminham lado a lado.</p>
                </article>

                <article class="institution-card" id="sobre-visao">
                    <span class="institution-card-index">02</span>
                    <h2>Visão e valores</h2>
                    <p>Valorizamos proximidade, consistência e atenção genuína. Cada detalhe, do acolhimento à apresentação dos pratos, deve refletir cuidado e autenticidade.</p>
                </article>

                <article class="institution-card" id="sobre-experiencia">
                    <span class="institution-card-index">03</span>
                    <h2>Experiência no restaurante</h2>
                    <p>Queremos que cada visita seja simples, agradável e memorável. O objetivo é que o cliente se sinta bem recebido desde a reserva até ao final da refeição.</p>
                </article>

                <article class="institution-card" id="sobre-equipa">
                    <span class="institution-card-index">04</span>
                    <h2>Equipa e serviço</h2>
                    <p>A qualidade da experiência depende de uma equipa presente, atenciosa e alinhada com o espírito da casa. O serviço deve ser caloroso, eficiente e humano.</p>
                </article>
            </div>
        </div>
    </div>
    <?php cd_render_theme_script('../', dirname(__DIR__)); ?>
</body>
</html>
