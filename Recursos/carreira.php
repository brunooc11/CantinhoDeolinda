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
    <title>Carreira - Cantinho Deolinda</title>
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
                <span class="institution-kicker">Oportunidades</span>
                <h1>Carreira</h1>
                <p>Esta página apresenta o tipo de perfil procurado pelo restaurante e a forma como futuras oportunidades podem ser comunicadas.</p>
            </div>
            <div class="institution-hero-card">
                <span class="institution-hero-label">Trabalhar connosco</span>
                <strong>Procuramos pessoas responsáveis, simpáticas e alinhadas com o espírito de serviço da casa.</strong>
                <p>Mais do que experiência, valorizamos atitude, disponibilidade e vontade de contribuir.</p>
            </div>
        </section>

        <div class="institution-layout">
            <aside class="institution-side-card">
                <span class="institution-side-label">Nesta página</span>
                <ul class="institution-side-list">
                    <li><a href="#carreira-perfil">Perfil procurado</a></li>
                    <li><a href="#carreira-ambiente">Ambiente de trabalho</a></li>
                    <li><a href="#carreira-candidatura">Candidatura</a></li>
                    <li><a href="#carreira-contacto">Contacto profissional</a></li>
                </ul>
            </aside>

            <div class="institution-grid">
                <article class="institution-card" id="carreira-perfil">
                    <span class="institution-card-index">01</span>
                    <h2>Perfil procurado</h2>
                    <p>Valorizamos pessoas com boa apresentação, sentido de responsabilidade, simpatia no atendimento e capacidade para trabalhar com consistência em equipa.</p>
                </article>

                <article class="institution-card" id="carreira-ambiente">
                    <span class="institution-card-index">02</span>
                    <h2>Ambiente de trabalho</h2>
                    <p>O objetivo é manter um ambiente colaborativo, profissional e respeitador, onde cada função contribui diretamente para a experiência do cliente.</p>
                </article>

                <article class="institution-card" id="carreira-candidatura">
                    <span class="institution-card-index">03</span>
                    <h2>Como candidatar</h2>
                    <p>Quando existirem vagas, o contacto poderá ser feito através dos canais indicados pelo restaurante. A candidatura deve ser clara, objetiva e atualizada.</p>
                </article>

                <article class="institution-card" id="carreira-contacto">
                    <span class="institution-card-index">04</span>
                    <h2>Contacto profissional</h2>
                    <p>Para manifestações de interesse, o ideal é utilizar uma mensagem cuidada, indicando disponibilidade, experiência relevante e função pretendida.</p>
                </article>
            </div>
        </div>
    </div>
    <?php cd_render_theme_script('../', dirname(__DIR__)); ?>
</body>
</html>
