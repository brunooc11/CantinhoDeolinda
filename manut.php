<?php require_once __DIR__ . '/theme.php'; ?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="Imagens/logo_atual.png">
    <title>Site em Manutenção</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Css/manut.css">
    <?php cd_render_theme_head('', __DIR__); ?>
</head>
<body class="pagina-manutencao">
    <?php cd_render_theme_toggle(''); ?>
    <main class="manut-shell">
        <section class="manut-card">
            <div class="manut-accent" aria-hidden="true"></div>
            <div class="manut-orb manut-orb-left" aria-hidden="true"></div>
            <div class="manut-orb manut-orb-right" aria-hidden="true"></div>
            <div class="manut-grid" aria-hidden="true"></div>
            <p class="manut-kicker">Cantinho Deolinda</p>
            <div class="manut-status" aria-hidden="true">
                <span class="manut-status-dot"></span>
                <span>Atualização em curso</span>
            </div>
            <h1>A cozinha está em manutenção</h1>
            <p class="manut-lead">Estamos a preparar melhorias para voltar com uma experiência mais estável, rápida e cuidada.</p>
            <p class="manut-note">O acesso está temporariamente reservado à equipa de gestão.</p>

            <div class="manut-actions">
                <a href="login.php" class="manut-admin-link">Entrar como administrador</a>
            </div>
        </section>
    </main>
    <?php cd_render_theme_script('', __DIR__); ?>
</body>
</html>
