<?php
require("config.php");
require_once("Bd/popup_helper.php");

if (isset($_GET['erro']) && $_GET['erro'] === 'lista_negra') {
  cd_popup('Não pode efetuar reservas devido a faltas anteriores.', 'error');
}
?>



<!DOCTYPE html>
<html lang="pt">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cantinho_Deolinda</title>

  <link rel="stylesheet" href="Css/loader.css">
  <link rel="stylesheet" href="Css/ModoEscuro.css">
  <link rel="stylesheet" href="Css/navbar.css">
  <link rel="stylesheet" href="Css/home.css">
  <link rel="stylesheet" href="Css/carrosel.css">
  <link rel="stylesheet" href="Css/menu.css">
  <link rel="stylesheet" href="Css/banner.css">
  <link rel="stylesheet" href="Css/eventos.css" />
  <link rel="stylesheet" href="Css/bttlogin.css">
  <link rel="stylesheet" href="Css/conta.css">
  <link rel="stylesheet" href="Css/modal_reservas.css">
  <link rel="stylesheet" href="Css/contacto.css">
  <link rel="stylesheet" href="Css/info_adicionais.css">
  <link rel="stylesheet" href="Css/backhome.css">
  <link rel="stylesheet" href="Css/footer.css">
  <link rel="stylesheet" href="Css/prefooter.css">
  <link rel="stylesheet" href="Css/chatbot.css">
  <link rel="stylesheet" href="Css/btts.css">
  <link rel="stylesheet" href="Css/cta.css">
  <link rel="stylesheet" href="Css/scroll_reveal.css">


  <link rel="icon" type="image/png" href="Imagens/logo.png">

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@400;500;600&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">


  <link
    rel="stylesheet"
    href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body data-logged-in="<?php echo isset($_SESSION['id']) ? '1' : '0'; ?>">

  <!-- Loader -->
  <div class="loader-wrapper" id="loaderWrapper">
    <div class="container">
      <div class="frigideira">
        <div class="pega"></div>
        <div class="fumo"></div>
        <div class="fumo"></div>
        <div class="fumo"></div>
      </div>
      <div class="ovo"></div>
      <div class="texto"><i class="fa-solid fa-utensils" aria-hidden="true"></i> A Cozinha esta a Trabalhar <i class="fa-solid fa-utensils" aria-hidden="true"></i></div>
    </div>
  </div>

  <!-- Conteúdo principal -->
  <main class="site-content" id="mainContent">

    <!-- Controle de tema -->
    <div class="controlo">
      <button class="tema-btn active" id="claro-btn" aria-label="Modo claro">
        <img src="Icons/sol.png" alt="Claro">
      </button>
      <button class="tema-btn" id="escuro-btn" aria-label="Modo escuro">
        <img src="Icons/Lua.png" alt="Escuro">
      </button>
    </div>

    <?php if (isset($_SESSION['id'])): ?>
      <?php if ($_SESSION['permissoes'] === 'admin'): ?>
        <a href="admin.php" class="btt-padrao-login"><i class="fa-solid fa-gear" aria-hidden="true"></i> Admin</a>
      <?php else: ?>
        <a href="dashboard.php" class="btt-padrao-login"><i class="fa-solid fa-gear" aria-hidden="true"></i> Conta</a>
      <?php endif; ?>

      <!-- Modal de Reserva -->
      <div id="reservaModal" class="reserva-modal">
        <div class="reserva-modal-content">

          <!-- Header -->
          <div class="reserva-modal-header">
            <h2>Faz Uma Reserva</h2>
            <button type="button" id="closeReserva" class="reserva-close" aria-label="Fechar modal">&times;</button>
          </div>

          <!-- Body -->
          <form class="reserva-modal-body" action="Bd/processar_reservas.php" method="POST">

            <!-- Linha 1: Time e Data -->
            <div class="reserva-form-group grid-2">
              <!-- Input de Hora e Minutos como uma única caixa -->
              <input type="text" name="hora_reserva" class="reserva-input" id="horaMinInput" placeholder="HR:MN" maxlength="5" required>

              <!-- Data -->
              <div class="date-container">
                <input type="date" name="data_reserva" id="dateInput" class="reserva-input" required>
                <svg class="date-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" onclick="document.getElementById('dateInput').showPicker()">
                  <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 18H5V9h14v13z" />
                </svg>
              </div>
            </div>


            <!-- Linha 2: Nome e Telefone (apenas visual) -->
            <div class="reserva-form-group grid-2">
              <input type="text" class="reserva-input" placeholder="Name" value="<?php echo $_SESSION['nome'] ?? ''; ?>" disabled>
              <input type="tel" class="reserva-input" placeholder="Phone" value="<?php echo $_SESSION['telefone'] ?? ''; ?>" disabled>
              <!-- Hidden fields para enviar ao processar_reservas.php -->
              <input type="hidden" name="nome_cliente" value="<?php echo $_SESSION['nome'] ?? ''; ?>">
              <input type="hidden" name="telefone_cliente" value="<?php echo $_SESSION['telefone'] ?? ''; ?>">
            </div>


            <!-- Linha 3: Número de Pessoas -->
            <div class="reserva-form-group">
              <input
                type="number"
                name="numero_pessoas"
                id="numero_pessoas"
                class="reserva-input"
                placeholder="Número de pessoas"
                min="1"
                required>
              <small id="avisoPessoas" style="color:red; display:none; font-size:14px; margin-top:4px;">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Máximo permitido: 30 pessoas. Para grupos maiores, contacte o restaurante.
              </small> 
            </div> 


            <!-- Linha 4: Email (apenas visual) -->
            <input type="email" class="reserva-input" placeholder="Email" value="<?php echo $_SESSION['email'] ?? ''; ?>" disabled>

            <!-- Footer -->
            <div class="reserva-modal-footer">
              <p>
                Reserve a sua mesa e deixe o resto connosco. Uma experiência pensada para si.
              </p>
              <button type="submit" id="confirmBtn2">Confirmar Reserva</button>
            </div>
          </form>
        </div>
      </div>

    <?php else: ?>
      <a href="login.php" class="btt-padrao-login">Login</a>
    <?php endif; ?>

    <!-- Navbar -->
    <div class="navbar">
      <div class="logo">
        <span class="logo-circle">C</span>
        <span class="logo-text">antinho_Deolinda</span>
      </div>

      <ul class="menu">
        <li><a href="#home">Início</a></li>
        <li><a href="#menu_carrosel">Menu</a></li>
        <li><a href="#eventos">Eventos</a></li>
        <li><a href="#localizacao">Localização</a></li>
        <li><a href="#contacto">Experiencia</a></li>
      </ul>

      <!--Se o login tiver feito ele abre a dashboard na aba reservas -->
      <a href="<?php echo isset($_SESSION['id']) ? 'dashboard.php?tab=Reservas' : 'login.php'; ?>" class="btn-reserva">Reservas</a>
    </div>

    <!-- Landing Page -->
    <section id="home">
      <div class="landing-page">
        <svg class="gold-shape" viewBox="0 0 600 1000" preserveAspectRatio="none">
          <path d="M0,0 L400,0 C460,120 480,300 450,500 C420,700 480,850 450,1000 L0,1000 Z" fill="#f4b942" />
        </svg>

        <div class="content">
          <div class="left">
            <img src="Imagens/plate.png" alt="Prato de comida">
          </div>
          <div class="right">
            <h1>
              Cada momento à mesa é <strong>realmente inesquecível</strong>.
            </h1>
            <p>
              No Cantinho Deolinda, procuramos criar momentos únicos à mesa, juntando
              tradição, qualidade e um ambiente acolhedor, pensado para quem aprecia
              boa comida e boas memórias.
            </p>

            <div class="buttons">

              <?php if (isset($_SESSION['id'])): ?>
                <button class="btn filled" id="openReservaModal">Reserva Agora</button>
              <?php else: ?>
                <a href="login.php" class="btn filled">Reserva Agora</a>
              <?php endif; ?>

              <button class="btn" onclick="window.location.href='#localizacao'">Localização</button>
            </div>
          </div>
        </div>
      </div>
    </section>


    <!-- Menu com carrossel -->
    <section id="menu_carrosel" class="menu-section">
      <h3>Menu</h3>
      <h1>Prove os nossos pratos e desfrute</h1>

      <div class="carousel-container">
        <div class="carousel" id="carousel">

          <div class="card">
            <img src="https://images.unsplash.com/photo-1504754524776-8f4f37790ca0" alt="Iogurte com frutas">
            <div class="overlay"><i class="fa-solid fa-seedling" aria-hidden="true"></i> Iogurte com Frutas</div>
          </div>

          <div class="card">
            <img src="https://images.unsplash.com/photo-1603133872878-684f208fb84b" alt="Prato de massa">
            <div class="overlay"><i class="fa-solid fa-bowl-food" aria-hidden="true"></i> Massa Especial</div>
          </div>

          <div class="card">
            <img src="https://images.unsplash.com/photo-1504754524776-8f4f37790ca0" alt="Iogurte com frutas">
            <div class="overlay"><i class="fa-solid fa-leaf" aria-hidden="true"></i> Salada Fresca</div>
          </div>

          <div class="card">
            <img src="https://images.unsplash.com/photo-1603133872878-684f208fb84b" alt="Prato de massa">
            <div class="overlay"><i class="fa-solid fa-drumstick-bite" aria-hidden="true"></i> Bife Grelhado</div>
          </div>

          <div class="card">
            <img src="https://images.unsplash.com/photo-1504754524776-8f4f37790ca0" alt="Iogurte com frutas">
            <div class="overlay"><i class="fa-solid fa-cake-candles" aria-hidden="true"></i> Sobremesa Doce</div>
          </div>

          <div class="card">
            <img src="https://images.unsplash.com/photo-1603133872878-684f208fb84b" alt="Prato de massa">
            <div class="overlay"><i class="fa-solid fa-wine-glass" aria-hidden="true"></i> Vinho da Casa</div>
          </div>

        </div>

        <button class="btn prev" onclick="moveSlide(-1)">&#10094;</button>
        <button class="btn next" onclick="moveSlide(1)">&#10095;</button>
      </div>
    </section>

    <!-- Menu Interativo -->
    <section class="menu-categorias">
      <h2>O Nosso Menu</h2>
      <p>Escolhe uma categoria para explorares os nossos pratos.</p>

      <div class="menu-tabs">
        <button class="tab-btn active" data-target="especialidades">Especialidades</button>
        <button class="tab-btn" data-target="menu-estudante">Menu Estudante</button>
        <button class="tab-btn" data-target="sopas">Sopas</button>
        <button class="tab-btn" data-target="bebidas">Bebidas</button>
      </div>

      <div class="menu-content active" id="especialidades">
        <div class="menu-grid">
          <div class="item">
            <h3>Peixe</h3>
            <ul class="menu-list">
              <li><span>Bacalhau a Casa</span><strong>18.50&euro;</strong></li>
              <li><span>Bacalhau a Lagareiro</span><strong>18.50&euro;</strong></li>
              <li><span>Açorda de Bacalhau com Gambas (no pão)</span><strong>18.50&euro;</strong></li>
              <li><span>Polvo a Lagareiro</span><strong>18.50&euro;</strong></li>
            </ul>
            <p>Nota: Açorda de bacalhau com gambas só por encomenda.</p>
          </div>
          <div class="item">
            <h3>Carne</h3>
            <ul class="menu-list">
              <li><span>Bife a Casa</span><strong>18.50&euro;</strong></li>
              <li><span>Espetadas de Porco Preto</span><strong>18.50&euro;</strong></li>
              <li><span>Picanha</span><strong>18.50&euro;</strong></li>
              <li><span>Costeleta de Novilho</span><strong>18.50&euro;</strong></li>
              <li><span>Secretos</span><strong>18.50&euro;</strong></li>
              <li><span>Cozido a Portuguesa</span><strong>18.50&euro;</strong></li>
            </ul>
            <p>Nota: Cozido a Portuguesa apenas quintas-feiras e domingos.</p>
          </div>
        </div>
      </div>

      <div class="menu-content" id="menu-estudante">
        <div class="menu-grid">
          <div class="item">
            <h3>Mini-prato + bebida <span>7.50&euro;</span></h3>
            <p>Apenas durante o periodo escolar, de segunda a sexta-feira.</p>
          </div>
        </div>
      </div>

      <div class="menu-content" id="sopas">
        <div class="menu-grid">
          <div class="item"><h3>Sopa de Legumes <span>1.50&euro;</span></h3></div>
          <div class="item"><h3>Sopa de Peixe <span>2.50&euro;</span></h3></div>
        </div>
      </div>

      <div class="menu-content" id="bebidas">
        <div class="menu-grid">
          <div class="item"><h3>Vinho da Casa <span>3.50&euro;</span></h3><p>Nao faz parte do menu.</p></div>
          <div class="item"><h3>Sumo Natural <span>2.50&euro;</span></h3></div>
        </div>
      </div>
    </section>

    <!-- Novo Banner (batatas fritas) -->
    <section class="banner" id="banner">
      <img src="Imagens/batata.png" alt="French Fries">
      <div class="banner-text">
        <h4>Fome?</h4>
        <h2>Nós tratamos disso</h2>
        <?php if (isset($_SESSION['id'])): ?>
          <button id="batatas-btn" class="btt-padrao-login" onclick="document.getElementById('openReservaModal').click()">
            Reserva Agora
          </button>
        <?php else: ?>
          <button id="batatas-btn" class="btt-padrao-login" onclick="window.location.href='login.php'">
            Reserva Agora
          </button>
          <?php endif; ?>
      </div>
    </section>

    <!-- Novo bloco: Catering -->
    <section class="catering" id="eventos">
      <div class="catering-wrapper">
        <div class="catering-info">
          <h5>Catering</h5>
          <h2>O seu evento, nas melhores mãos</h2>
          <p>
            Criamos a experiência perfeita para almoços/jantares de grupo.
            Acrescentamos sabor a reuniões de empresa, casamentos ou festas.
            Disponibilizamos um serviço completo e flexível, adaptado às necessidades de cada evento.
          </p>

        </div>

        <div class="catering-box">
          <img src="https://cdn-icons-png.flaticon.com/512/1046/1046784.png" alt="Catering Image">
          <p>Criamos experiências gastronómicas para eventos especiais e empresariais</p>
          <button onclick="window.location.href='#localizacao'">Fale Connosco</button>
        </div>
      </div>
    </section>

    <!-- info Adicionais -->

    <section id="localizacao">
      <div class="info_adicionais-wrapper">

        <div class="info_adicionais-title">Contactos/Localização</div>

        <p class="contact-desc">
          Estamos disponíveis para ajudar. Contacte-nos ou visite-nos no nosso espaço físico.
        </p>

        <div class="contact-section">

          <div class="contact-left">

            <div class="info_adicionais-card">
              <div class="info_adicionais-card-title">Horas de Serviço</div>
              <p><strong>Segunda-Sábado</strong><br>8:00 - 24:00</p>
              <p><strong>Domingo</strong><br>8:00 - 17:00</p>
            </div>

            <div class="info_adicionais-card">
              <div class="info_adicionais-card-title">Suporte</div>
              <p><strong>Segunda - Domingo</strong></p>
              <p class="contact-line">
                <span class="contact-icon"><i class="fa-solid fa-phone"></i></span>
                <a id="support-phone-link" class="contact-link-animated" href="tel:+351966545510" target="_blank" rel="noopener noreferrer">+351 966 545 510</a>
              </p>
              <p class="contact-line">
                <span class="contact-icon"><i class="fa-solid fa-envelope"></i></span>
                <a class="contact-link-animated" href="https://mail.google.com/mail/?extsrc=mailto&url=mailto%3Acantinhodeolinda%40gmail.com" target="_blank" rel="noopener noreferrer">cantinhodeolinda@gmail.com</a>
              </p>
              <p class="contact-line">
                <span class="contact-icon"><i class="fa-solid fa-location-dot"></i></span>
                <a class="contact-link-animated" href="https://maps.google.com/?q=Rua+Carlos+Alberto+Martins+Vicente,+2580-355+Alenquer" target="_blank" rel="noopener noreferrer">Rua Carlos Alberto Martins Vicente, 2580-355 Alenquer</a>
              </p>

              <?php if (isset($_SESSION['id'])): ?>
                <button id="info_adicionais-btn" class="btt-padrao-login" onclick="document.getElementById('openReservaModal').click()">
                  Reserva Agora
                </button>
              <?php else: ?>
                <button id="info_adicionais-btn" class="btt-padrao-login" onclick="window.location.href='login.php'">
                  Reserva Agora
                </button>
              <?php endif; ?>
            </div>

          </div>

          <!-- com o loading lazy, o mapa carrega apenas quando aparece no ecrã. -->
          <div class="info_adicionais-card contact-map">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3111.627227445397!2d-9.006716523513655!3d39.05388597170578!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd18f6cd5b0cf3ef%3A0x7a3d7a0f54818651!2sR.%20Carlos%20Alberto%20Martins%20Vicente%202580-355%20Alenquer!5e0!3m2!1spt-PT!2spt!4v1733340000000!5m2!1spt-PT!2spt"
              width="100%"
              height="100%"
              style="border:0;"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>
          </div>

        </div>

      </div>
    </section>


    <section class="cd-trust-strip">
      <div class="cd-trust-strip-inner">

        <section class="cd-trust">
          <h2 class="cd-trust-title">Porque escolher o Cantinho Deolinda?</h2>

          <div class="cd-trust-grid">
            <div class="cd-trust-card">
              <i class="cd-trust-icon fa-solid fa-star" aria-hidden="true"></i>
              <h3 class="cd-trust-heading">4.1 no Google</h3>
              <p class="cd-trust-text">Clientes satisfeitos todos os dias</p>
            </div>

            <div class="cd-trust-card">
              <i class="cd-trust-icon fa-solid fa-utensils" aria-hidden="true"></i>
              <h3 class="cd-trust-heading">Cozinha Tradicional</h3>
              <p class="cd-trust-text">Receitas portuguesas autênticas</p>
            </div>

            <div class="cd-trust-card">
              <i class="cd-trust-icon fa-solid fa-mug-hot" aria-hidden="true"></i>
              <h3 class="cd-trust-heading">Ambiente Acolhedor</h3>
              <p class="cd-trust-text">Perfeito para família e amigos</p>
            </div>

            <div class="cd-trust-card">
              <i class="cd-trust-icon fa-solid fa-wine-glass"></i>
              <h3 class="cd-trust-heading">Ideal para Ocasiões</h3>
              <p class="cd-trust-text">
                Perfeito para jantares em grupo e celebrações.
              </p>
            </div>

          </div>
        </section>

      </div>
    </section>


    <!-- Contacto -->
    <section id="contacto">
      <div class="contact-wrapper">

        <div class="contact-card">
          <h1>Feedback</h1>
          <p class="subtitle">Envia-nos uma mensagem e responderemos assim que possível.</p>

          <form class="form-grid" id="contactForm">

            <!-- COLUNA ESQUERDA -->
            <div class="left-col">
              <div class="form-group">
                <label>Nome</label>
                <input type="text" name="nome" placeholder="O teu nome" value="<?php echo htmlspecialchars($_SESSION['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo isset($_SESSION['id']) ? 'readonly' : ''; ?> required>
              </div>

              <div class="form-group">
                <label>Assunto</label>
                <select name="assunto" required>
                  <option value="">Seleciona um assunto</option>
                  <option value="Reserva">Reserva</option>
                  <option value="Experiência no restaurante">Experiência no restaurante</option>
                  <option value="Qualidade da comida">Qualidade da comida</option>
                  <option value="Atendimento">Atendimento</option>
                  <option value="Sugestão">Sugestão</option>
                  <option value="Reclamação">Reclamação</option>
                  <option value="Outro">Outro</option>
                </select>
              </div>

              <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@exemplo.com" value="<?php echo htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo isset($_SESSION['id']) ? 'readonly' : ''; ?> required>
              </div>
            </div>

            <!-- COLUNA DIREITA -->
            <div class="right-col">
              <div class="form-group">
                <label>Mensagem</label>
                <textarea name="mensagem" placeholder="A tua mensagem..." required></textarea>
              </div>

              <button type="submit" class="btt-padrao-login" id="btt-contact-pos">
                Enviar Mensagem
              </button>
            </div>

          </form>

        </div>

      </div>

    </section>

    <!-- BOTÃO CHAT -->
    <div class="back-home-btn show" id="btnChat" title="Falar com o suporte"><i class="fa-solid fa-comments" aria-hidden="true"></i></div>

    <!-- CHAT -->
    <div class="chat-box hidden" id="chatBox">

      <div class="chat-header">
        <div class="chat-header-main">
          <i class="fa-solid fa-robot" aria-hidden="true"></i>
          <div class="chat-header-copy">
            <span class="chat-title">Assistente Cantinho Deolinda</span>
            <span class="chat-status">Online</span>
          </div>
        </div>
        <span id="closeChat" aria-label="Fechar chat"><i class="fa-solid fa-xmark" aria-hidden="true"></i></span>
      </div>

      <div class="chat-messages">
        <div class="msg bot">
          Ola. Posso ajudar com reservas, horarios ou duvidas sobre o restaurante.
        </div>

        <div class="quick-btns">
          <button data-message="Reservas"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Reservas</button>
          <button data-message="Menu"><i class="fa-solid fa-utensils" aria-hidden="true"></i> Menu</button>
          <button data-message="Localizacao"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Localizacao</button>
          <button data-message="Contactos"><i class="fa-solid fa-phone" aria-hidden="true"></i> Contactos</button>
          <button data-message="Outro"><i class="fa-solid fa-circle-question" aria-hidden="true"></i> Outro/a</button>
        </div>
      </div>

      <div class="chat-input">
        <input type="text" placeholder="Escreve a tua mensagem..." aria-label="Mensagem para o assistente">
        <button type="button" id="sendChat" aria-label="Enviar mensagem">></button>
      </div>

    </div>

    <!-- Botão para voltar ao Home -->
    <a href="#home" id="backHomeBtn" class="back-home-btn"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></a>

    <!-- Pre-Footer -->
    <section class="prefooter">
      <div class="prefooter-container">
        <!-- Coluna ESQUERDA -->
        <div class="prefooter-left">
          <div class="prefooter-text">
            <div class="logo" id="logo-prefooter">
              <span class="logo-circle">C</span>
              <span class="logo-text">antinho_Deolinda</span>
            </div>
            <h3>Queres provar a<br>Nossa Comida?</h3>
            <?php if (isset($_SESSION['id'])): ?>
              <button class="btt-padrao-login" id="prefooter" onclick="document.getElementById('openReservaModal').click()">Reserva Agora</button>
            <?php else: ?>
              <a href="login.php" class="btt-padrao-login" id="prefooter">Reserva Agora</a>
            <?php endif; ?>
          </div>
        </div>

        <!-- ABOUT -->
        <div class="prefooter-col">
          <h4>Sobre Nós</h4>
          <ul>
            <li><a href="#">Sobre Nós</a></li>
            <li><a href="#">Serviços</a></li>
            <li><a href="#">Carreira</a></li>
            <li><a href="#">Fale Connosco</a></li>
          </ul>
        </div>

        <!-- RESOURCES -->
        <div class="prefooter-col">
          <h4>Recursos</h4>
          <ul>
            <li><a href="Recursos/termos.php">Termos</a></li>
            <li><a href="Recursos/ajuda.php">Ajuda</a></li>
            <li><a href="Recursos/politica.php">Privacidade</a></li>
          </ul>
        </div>

        <!-- CONTACT -->
        <div class="prefooter-col">
          <h4>Contacto</h4>

          <!-- ÍCONES SOCIAIS -->
          <div class="pf-icons">
            <a href="https://web.facebook.com/ocantinhodaddeolinda/?locale=pt_BR&_rdc=1&_rdr#" target="_blank" rel="noopener noreferrer" style="--pf-color:#1877f2" aria-label="Facebook">
              <i class="fab fa-facebook-f"></i>
            </a>

            <a id="whatsapp-link" href="https://wa.me/351966545510" target="_blank" rel="noopener noreferrer" style="--pf-color:#25d366" aria-label="WhatsApp">
              <i class="fab fa-whatsapp"></i>
            </a>

            <a href="https://www.tripadvisor.com.br/Restaurant_Review-g2067688-d14935140-Reviews-O_Cantinho_da_D_Deolinda-Alenquer_Lisbon_District_Central_Portugal.html" target="_blank" rel="noopener noreferrer" style="--pf-color:#34e0a1" aria-label="TripAdvisor">
              <svg
                class="tripadvisor-icon"
                viewBox="0 0 1827.74 1173.72"
                aria-hidden="true">
                <path
                  fill="currentColor"
                  d="M619,679.47c0,89.5-72.57,162.07-162.07,162.07s-162.07-72.57-162.07-162.07,72.57-162.07,162.07-162.07,162.07,72.57,162.07,162.07ZM1370.4,517.4c-89.49,0-162.07,72.57-162.07,162.07s72.58,162.07,162.07,162.07,162.06-72.57,162.06-162.07c-.02-89.45-72.52-161.97-161.97-162.02l-.09-.04ZM1827.34,679.47c0,252.22-204.65,456.69-456.93,456.69-115.09.17-225.95-43.24-310.29-121.55l-146.24,159.11-146.31-159.29c-84.32,78.4-195.24,121.88-310.38,121.73C205.08,1136.16.5,931.68.5,679.47c-.18-128.48,53.93-251.07,148.97-337.52L0,179.34h332.06c351.35-239.11,813.15-239.11,1164.48,0h331.2l-149.43,162.61c95.07,86.43,149.19,209.02,149.03,337.52ZM766.12,679.47c0-170.69-138.39-309.07-309.07-309.07s-309.07,138.39-309.07,309.07,138.39,309.07,309.07,309.07,309.05-138.34,309.07-309.03v-.05ZM1256.82,218.03c-219.53-91.45-466.48-91.45-686.01,0,195.13,74.69,343.06,249.13,343.06,452.52,0-203.37,147.91-377.84,342.98-452.5l-.02-.02ZM1679.54,679.44c0-170.69-138.39-309.07-309.07-309.07s-309.08,138.39-309.08,309.07,138.38,309.07,309.08,309.07,309.07-138.39,309.07-309.07Z" />
              </svg>
            </a>

            <a href="https://github.com/brunooc11/CantinhoDeolinda" target="_blank" rel="noopener noreferrer" style="--pf-color:#ffffff" aria-label="GitHub">
              <i class="fab fa-github"></i>
            </a>
          </div>
        </div>

      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <p>&copy; 2025 Cantinho Deolinda - Todos os direitos reservados</p>
  </footer>

  <script src="Js/popup_alert.js"></script>
  <script src="Js/ModoEscuro.js"></script>
  <script src="Js/whatsapp-link.js"></script>
  <script src="Js/loader.js"></script>
  <script src="Js/carrosel.js?v=20260223b"></script>
  <script src="Js/favoritos.js?v=20260223b"></script>
  <script src="Js/menu.js"></script>
  <script src="Js/navbar.js"></script>
  <script src="Js/Modal_reservas.js"></script>
  <script src="Js/contacto.js"></script>
  <script src="Js/backhome.js"></script>
  <script src="Js/chatbot.js"></script>
  <script src="Js/scroll_reveal.js"></script>

</body>

</html>

