<?php
require("../config.php");

$voltar = $_SERVER['HTTP_REFERER'] ?? '../login.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../Imagens/logo_atual.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - Cantinho Deolinda</title>
    <link rel="stylesheet" href="../Css/home.css">
    <link rel="stylesheet" href="../Css/bttlogin.css">
    <link rel="stylesheet" href="../Css/politica.css?v=<?php echo filemtime(__DIR__ . '/../Css/politica.css'); ?>">
</head>
<body class="pagina-politica">
    <div class="privacy-shell">
        <a href="<?php echo htmlspecialchars($voltar, ENT_QUOTES, 'UTF-8'); ?>" id="btt-politica" class="btt-padrao-login">Voltar</a>

        <section class="privacy-hero">
            <div class="privacy-hero-copy">
                <span class="privacy-kicker">Proteção de dados</span>
                <h1>Política de Privacidade</h1>
                <p>Explicamos aqui, de forma clara, como a informação do utilizador pode ser utilizada no site e quais os princípios que orientam esse tratamento.</p>
            </div>
            <div class="privacy-hero-card">
                <span class="privacy-hero-label">Compromisso</span>
                <strong>Clareza, segurança e utilização responsável dos dados associados à conta.</strong>
                <p>Os dados devem servir a experiência digital do restaurante e o contacto com o utilizador.</p>
            </div>
        </section>

        <div class="privacy-layout">
            <aside class="privacy-side-card">
                <span class="privacy-side-label">Política de Privacidade</span>
                <ul class="privacy-side-list">
                    <li><a href="#politica-recolha">Informações recolhidas</a></li>
                    <li><a href="#politica-uso">Uso das informações</a></li>
                    <li><a href="#politica-protecao">Proteção de dados</a></li>
                    <li><a href="#politica-partilha">Partilha de informações e cookies</a></li>
                    <li><a href="#politica-final">Direitos, alterações e contacto</a></li>
                </ul>
            </aside>

            <div class="privacy-content">
                <div class="privacy-grid">
                    <article class="privacy-card" id="politica-recolha">
                        <span class="privacy-card-index">01</span>
                        <h2>Informações Recolhidas</h2>
                        <p>Podem ser tratados dados fornecidos pelo utilizador ao criar conta, iniciar sessão, reservar, contactar o restaurante ou usar funcionalidades do site, como nome, email, telefone e informação necessária ao serviço.</p>
                    </article>

                    <article class="privacy-card" id="politica-uso">
                        <span class="privacy-card-index">02</span>
                        <h2>Uso das Informações</h2>
                        <p>Os dados recolhidos podem ser utilizados para gerir a conta, processar pedidos, apoiar reservas, responder a solicitações, melhorar a plataforma e prestar apoio ao utilizador.</p>
                    </article>

                    <article class="privacy-card" id="politica-protecao">
                        <span class="privacy-card-index">03</span>
                        <h2>Proteção de Dados</h2>
                        <p>O Cantinho Deolinda procura adotar medidas adequadas para proteger a informação contra acessos indevidos, alterações não autorizadas, divulgação incorreta ou utilização abusiva.</p>
                    </article>

                    <article class="privacy-card" id="politica-partilha">
                        <span class="privacy-card-index">04</span>
                        <h2>Partilha de Informações</h2>
                        <p>As informações pessoais não devem ser vendidas nem partilhadas sem fundamento legítimo, exceto quando isso seja necessário para cumprir obrigações legais ou suportar o funcionamento do serviço.<span class="privacy-paragraph-break"><strong id="politica-cookies">Cookies:</strong> o site pode utilizar mecanismos técnicos como cookies ou preferências locais para melhorar a experiência, lembrar configurações e apoiar o funcionamento normal da navegação.</span></p>
                    </article>

                    <article class="privacy-card" id="politica-direitos">
                        <span class="privacy-card-index">05</span>
                        <h2>Direitos do Utilizador</h2>
                        <p>O utilizador pode solicitar esclarecimentos, correções ou apoio relativamente aos dados associados à sua conta, sempre que aplicável, através dos canais de contacto do restaurante.</p>
                    </article>

                    <article class="privacy-card privacy-card-wide" id="politica-final">
                        <span class="privacy-card-index">06</span>
                        <h2>Alterações e Contacto</h2>
                        <p>Esta política pode ser atualizada ao longo do tempo. Sempre que necessário, a versão mais recente ficará disponível nesta página. Para qualquer questão relacionada com privacidade, utiliza o email <strong>cantinhodeolinda@gmail.com</strong>.</p>
                    </article>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
