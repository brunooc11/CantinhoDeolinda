<?php
require("../config.php");
require_once(__DIR__ . "/../theme.php");

// Se existir página anterior (login, index, menu, etc.) volta para lá.
// Caso a página seja aberta diretamente, volta por defeito para o login.
$voltar = $_SERVER['HTTP_REFERER'] ?? '../login.php';

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../Imagens/logo_atual.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Utilização - Cantinho Deolinda</title>
    <link rel="stylesheet" href="../Css/home.css">
    <link rel="stylesheet" href="../Css/bttlogin.css">
    <link rel="stylesheet" href="../Css/termos.css?v=<?php echo filemtime(__DIR__ . '/../Css/termos.css'); ?>">
    <?php cd_render_theme_head('../', dirname(__DIR__)); ?>
</head>
<body class="pagina-termos">
    <?php cd_render_theme_toggle('../'); ?>
    <div class="legal-shell">
        <a href="<?php echo htmlspecialchars($voltar, ENT_QUOTES, 'UTF-8'); ?>" id="btt-termos" class="btt-padrao-login">Voltar</a>

        <section class="legal-hero">
            <div class="legal-hero-copy">
                <span class="legal-kicker">Cantinho Deolinda</span>
                <h1>Termos de Utilização</h1>
                <p>Estas condições definem a forma correta de utilizar o site, a conta do utilizador e os serviços digitais associados ao restaurante.</p>
            </div>
            <div class="legal-hero-card">
                <span class="legal-hero-label">Resumo</span>
                <strong>Uso responsável, conta segura e respeito pelas regras da plataforma.</strong>
                <p>Ao continuar a usar o site, assumes uma utilização correta e alinhada com estes termos.</p>
            </div>
        </section>

        <div class="legal-layout">
            <aside class="legal-side-card">
                <span class="legal-side-label">Termos de Utilização</span>
                <ul class="legal-side-list">
                    <li><a href="#termos-uso-site">Uso do site</a></li>
                    <li><a href="#termos-conta-registo">Conta e registo</a></li>
                    <li><a href="#termos-conduta">Conduta do utilizador</a></li>
                    <li><a href="#termos-propriedade">Conteúdos e propriedade</a></li>
                    <li><a href="#termos-alteracoes">Alterações aos termos</a></li>
                    <li><a href="#termos-final">Limitação e contacto</a></li>
                </ul>
            </aside>

            <main class="legal-content">
                <article class="legal-section" id="termos-uso-site">
                    <span class="legal-section-index">01</span>
                    <h2>Uso do Site</h2>
                    <p>O site do Cantinho Deolinda deve ser utilizado apenas para consulta de informação, gestão de conta, pedidos de reserva e outras funcionalidades disponibilizadas pelo restaurante. Qualquer utilização abusiva, automatizada ou que prejudique o normal funcionamento da plataforma não é permitida.</p>
                </article>

                <article class="legal-section" id="termos-conta-registo">
                    <span class="legal-section-index">02</span>
                    <h2>Conta e Registo</h2>
                    <p>Algumas funcionalidades exigem autenticação. O utilizador é responsável por manter os dados da conta corretos, proteger a sua palavra-passe e não partilhar acessos com terceiros. A atividade realizada após login é considerada associada a essa conta.</p>
                </article>

                <article class="legal-section" id="termos-conduta">
                    <span class="legal-section-index">03</span>
                    <h2>Responsabilidade do Utilizador</h2>
                    <p>Ao utilizar o site, o utilizador compromete-se a fornecer informação verdadeira, agir com boa-fé e respeitar a plataforma, o restaurante e outros utilizadores. Não é permitido introduzir conteúdos ofensivos, fraudulentos, ilegais ou tecnicamente prejudiciais.</p>
                </article>

                <article class="legal-section" id="termos-propriedade">
                    <span class="legal-section-index">04</span>
                    <h2>Propriedade Intelectual</h2>
                    <p>Os textos, identidade visual, imagens, estrutura, elementos gráficos e restantes conteúdos presentes no site pertencem ao Cantinho Deolinda ou são utilizados com autorização. A sua cópia, reutilização ou distribuição sem permissão prévia não é permitida.</p>
                </article>

                <article class="legal-section" id="termos-alteracoes">
                    <span class="legal-section-index">05</span>
                    <h2>Alterações aos Termos</h2>
                    <p>O restaurante pode atualizar estes termos sempre que necessário, por motivos operacionais, legais ou de melhoria do serviço. As alterações passam a produzir efeitos após publicação nesta página.</p>
                </article>

                <article class="legal-section" id="termos-limitacao">
                    <span class="legal-section-index">06</span>
                    <h2>Limitação de Responsabilidade</h2>
                    <p>Apesar do esforço para manter a plataforma funcional e atualizada, podem existir indisponibilidades temporárias, erros técnicos ou atualizações em curso. O Cantinho Deolinda não garante funcionamento ininterrupto do site em todas as circunstâncias.</p>
                </article>

                <article class="legal-section legal-section-contact" id="termos-final">
                    <span class="legal-section-index">07</span>
                    <h2>Contacto</h2>
                    <p>Para esclarecer dúvidas relacionadas com estes termos, podes contactar a equipa através do email <strong>cantinhodeolina@gmail.com</strong>.</p>
                </article>
            </main>
        </div>
    </div>
    <?php cd_render_theme_script('../', dirname(__DIR__)); ?>
</body>
</html>
