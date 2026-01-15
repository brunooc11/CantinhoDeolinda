<?php
require("config.php");
?>


<?php
if (isset($_GET['erro']) && $_GET['erro'] === 'lista_negra') {
  echo "<script>
        alert('⚠ Não pode efetuar reservas devido a faltas anteriores.');
    </script>";
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


  <link rel="icon" type="image/png" href="Imagens/logo.png">

  <link
    rel="stylesheet"
    href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

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
      <div class="texto">🍴 A Cozinha está a Trabalhar 🍴...</div>
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
        <a href="admin.php" class="btt-padrao-login">⚙️ Admin</a>
      <?php else: ?>
        <a href="dashboard.php" class="btt-padrao-login">⚙️ Conta</a>
      <?php endif; ?>

      <!-- Modal de Reserva -->
      <div id="reservaModal" class="reserva-modal">
        <div class="reserva-modal-content">

          <!-- Header -->
          <div class="reserva-modal-header">
            <h2>Make A Reservation</h2>
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
                ⚠️ Máximo permitido: 30 pessoas. Para grupos maiores, contacte o restaurante.
              </small>
            </div>


            <!-- Linha 4: Email (apenas visual) -->
            <input type="email" class="reserva-input" placeholder="Email" value="<?php echo $_SESSION['email'] ?? ''; ?>" disabled>

            <!-- Footer -->
            <div class="reserva-modal-footer">
              <p>
                Apparently we had reached a great height in the atmos, for the sky was a dead black, and the stars had twinkle. By which lifts the light.
              </p>
              <button type="submit" id="confirmBtn2">Submit</button>
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
        <li><a href="#home">Home</a></li>
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
            <h1>The Spectacle <strong>Before Us Was Indeed</strong> Sublime.</h1>
            <p>
              Apparently, we had reached a great height in the atmosphere, for the sky
              was a dead black, and which lifts the horizon of the sea to the level of
              the spectator on a hillside.
            </p>
            <div class="buttons">

              <?php if (isset($_SESSION['id'])): ?>
                <button class="btn filled" id="openReservaModal">Reservar Agora</button>
              <?php else: ?>
                <a href="login.php" class="btn filled">Reservar Agora</a>
              <?php endif; ?>

              <button class="btn" onclick="window.location.href='#localizacao'">Our Location</button>
            </div>
          </div>
        </div>
      </div>
    </section>


    <!-- Menu com carrossel -->
    <section id="menu_carrosel" class="menu-section">
      <h3>Menu</h3>
      <h1>Taste Our Foods & Enjoy</h1>

      <div class="carousel-container">
        <div class="carousel" id="carousel">

          <div class="card">
            <img src="https://images.unsplash.com/photo-1504754524776-8f4f37790ca0" alt="Iogurte com frutas">
            <div class="overlay">🍓 Iogurte com Frutas</div>
          </div>

          <div class="card">
            <img src="https://images.unsplash.com/photo-1603133872878-684f208fb84b" alt="Prato de massa">
            <div class="overlay">🍝 Massa Especial</div>
          </div>

          <div class="card">
            <img src="https://images.unsplash.com/photo-1504754524776-8f4f37790ca0" alt="Iogurte com frutas">
            <div class="overlay">🥗 Salada Fresca</div>
          </div>

          <div class="card">
            <img src="https://images.unsplash.com/photo-1603133872878-684f208fb84b" alt="Prato de massa">
            <div class="overlay">🥩 Bife Grelhado</div>
          </div>

          <div class="card">
            <img src="https://images.unsplash.com/photo-1504754524776-8f4f37790ca0" alt="Iogurte com frutas">
            <div class="overlay">🍰 Sobremesa Doce</div>
          </div>

          <div class="card">
            <img src="https://images.unsplash.com/photo-1603133872878-684f208fb84b" alt="Prato de massa">
            <div class="overlay">🍷 Vinho da Casa</div>
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
        <button class="tab-btn active" data-target="especialidades">🍳 Especialidades</button>
        <button class="tab-btn" data-target="menus-dia">🍽️ Menus do Dia</button>
        <button class="tab-btn" data-target="menu-estudante">🎓 Menu Estudante</button>
        <button class="tab-btn" data-target="sopas">🥣 Sopas</button>
        <button class="tab-btn" data-target="bebidas">🍷 Bebidas</button>
      </div>

      <div class="menu-content active" id="especialidades">
        <div class="menu-grid">
          <div class="item">
            <h3>Bife à Casa <span>€12.50</span></h3>
            <p>Bife grelhado com molho de alho e batatas fritas.</p>
          </div>
          <div class="item">
            <h3>Bacalhau com Natas <span>€11.00</span></h3>
            <p>Receita tradicional portuguesa cremosa e deliciosa.</p>
          </div>
          <div class="item">
            <h3>Arroz de Pato <span>€10.50</span></h3>
            <p>Arroz de forno com pato desfiado e crosta dourada.</p>
          </div>
        </div>
      </div>

      <div class="menu-content" id="menus-dia">
        <div class="menu-grid">
          <div class="item">
            <h3>Menu 1 <span>€8.50</span></h3>
            <p>Prato principal + bebida + sobremesa.</p>
          </div>
          <div class="item">
            <h3>Menu 2 <span>€9.00</span></h3>
            <p>Prato de carne ou peixe com acompanhamento.</p>
          </div>
        </div>
      </div>

      <div class="menu-content" id="menu-estudante">
        <div class="menu-grid">
          <div class="item">
            <h3>Menu Estudante <span>€6.00</span></h3>
            <p>Prato do dia + bebida + sobremesa.</p>
          </div>
          <div class="item">
            <h3>Menu Light <span>€5.50</span></h3>
            <p>Salada + sopa + sumo natural.</p>
          </div>
        </div>
      </div>

      <div class="menu-content" id="sopas">
        <div class="menu-grid">
          <div class="item">
            <h3>Sopa de Legumes <span>€2.00</span></h3>
            <p>Receita caseira com ingredientes frescos.</p>
          </div>
          <div class="item">
            <h3>Caldo Verde <span>€2.20</span></h3>
            <p>Tradicional sopa portuguesa com chouriço.</p>
          </div>
        </div>
      </div>

      <div class="menu-content" id="bebidas">
        <div class="menu-grid">
          <div class="item">
            <h3>Vinho da Casa <span>€3.50</span></h3>
            <p>Tinto ou branco, servido à temperatura ideal.</p>
          </div>
          <div class="item">
            <h3>Sumo Natural <span>€2.50</span></h3>
            <p>Feito com frutas frescas da estação.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 🔥 Novo Banner (batatas fritas) -->
    <section class="banner" id="banner">
      <img src="Imagens/batata.png" alt="French Fries">
      <div class="banner-text">
        <h4>Hungry?</h4>
        <h2>We will home deliver!</h2>
        <?php if (isset($_SESSION['id'])): ?>
          <button id="batatas-btn" class="btt-padrao-login" onclick="document.getElementById('openReservaModal').click()">
            Reservar Agora
          </button>
        <?php else: ?>
          <button id="batatas-btn" class="btt-padrao-login" onclick="window.location.href='login.php'">
            Reservar Agora
          <?php endif; ?>
      </div>
    </section>

    <!-- 🔥 Novo bloco: Catering -->
    <section class="catering" id="eventos">
      <div class="catering-wrapper">
        <div class="catering-info">
          <h5>Catering</h5>
          <h2>We Manage Your Events</h2>
          <p>
            We deliver the perfect private dinner or experience at your home,
            or simply add flavor to your office meeting, wedding, or boat trip.
            Partnering with our sibling location, we can offer a full array of bar
            and beverage selections. Email us to learn more.
          </p>
        </div>

        <div class="catering-box">
          <img src="https://cdn-icons-png.flaticon.com/512/1046/1046784.png" alt="Catering Image">
          <p>We Cater in Weddings, Corporate Functions and Events</p>
          <button>HIRE US NOW</button>
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
              <div class="info_adicionais-card-title">Hours of Service</div>
              <p><strong>Monday to Saturday</strong><br>1:00 pm – 3:00 pm<br>7:00 pm – 11:00 pm</p>
              <p><strong>Sunday</strong><br>12:30 pm – 3:30 pm</p>
            </div>

            <div class="info_adicionais-card">
              <div class="info_adicionais-card-title">Support</div>
              <p><strong>Monday to Saturday</strong></p>
              <p>📞 +511 442-2777</p>
              <p>✉️ mail@restaurantpro.com</p>
              <p>📍 Rua Carlos Alberto Martins Vicente, 2580-355 Alenquer</p>

              <?php if (isset($_SESSION['id'])): ?>
                <button id="info_adicionais-btn" class="btt-padrao-login" onclick="document.getElementById('openReservaModal').click()">
                  Reservar Agora
                </button>
              <?php else: ?>
                <button id="info_adicionais-btn" class="btt-padrao-login" onclick="window.location.href='login.php'">
                  Reservar Agora
                </button>
              <?php endif; ?>

            </div>

          </div>

          <!-- com o loading lazy ,o mapa carrega apenas quando aparece no ecrã. -->
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
                <input type="text" name="nome" placeholder="O teu nome" required>
              </div>

              <div class="form-group">
                <label>Assunto</label>
                <input type="text" name="assunto" placeholder="Assunto" required>
              </div>

              <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@exemplo.com" required>
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
    <div class="back-home-btn show" id="btnChat" title="Falar com o suporte">💬</div>

    <!-- CHAT -->
    <div class="chat-box hidden" id="chatBox">

      <div class="chat-header">
        🤖 Assistente Cantinho Deolinda
        <span id="closeChat">✖</span>
      </div>

      <div class="chat-messages">
        <div class="msg bot">
          Olá 👋 Posso ajudar com reservas, horários ou dúvidas?
        </div>

        <div class="quick-btns">
          <button>📅 Reservas</button>
          <button>🍽️ Menu</button>
          <button>📍 Localização</button>
          <button>📞 Contactos</button>
          <button>❓ Outro/a</button>
        </div>
      </div>

      <div class="chat-input">
        <input type="text" placeholder="Escreve a tua mensagem...">
      </div>

    </div>

    <!-- Botão para voltar ao Home -->
    <a href="#home" id="backHomeBtn" class="back-home-btn">⮝</a>

    <!-- Pre-Footer -->
    <section class="prefooter">
      <div class="prefooter-container">

        <!-- Coluna ESQUERDA -->
        <div class="prefooter-left">
          <img src="Imagens/logo.png" alt="Logo" class="prefooter-logo">
          <h3>Want To Taste<br>Our Food?</h3>
          <button class="btt-padrao-login" id="prefooter">ORDER ONLINE</button>
        </div>

        <!-- ABOUT -->
        <div class="prefooter-col">
          <h4>ABOUT</h4>
          <ul>
            <li><a href="#">About</a></li>
            <li><a href="#">Services</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Hire Us</a></li>
          </ul>
        </div>

        <!-- RESOURCES -->
        <div class="prefooter-col">
          <h4>RESOURCES</h4>
          <ul>
            <li><a href="#">Terms</a></li>
            <li><a href="#">Help</a></li>
            <li><a href="#">Privacy</a></li>
          </ul>
        </div>

        <!-- CONTACT -->
        <div class="prefooter-col">
          <h4>CONTACT</h4>

          <!-- ÍCONES SOCIAIS -->
          <div class="pf-icons">
            <a href="#" style="--pf-color:#1877f2" aria-label="Facebook">
              <i class="fab fa-facebook-f"></i>
            </a>

            <a href="#" style="--pf-color:#25d366" aria-label="WhatsApp">
              <i class="fab fa-whatsapp"></i>
            </a>

            <a href="#" style="--pf-color:#34e0a1" aria-label="TripAdvisor">
              <svg
                class="tripadvisor-icon"
                viewBox="0 0 1827.74 1173.72"
                aria-hidden="true">
                <path
                  fill="currentColor"
                  d="M619,679.47c0,89.5-72.57,162.07-162.07,162.07s-162.07-72.57-162.07-162.07,72.57-162.07,162.07-162.07,162.07,72.57,162.07,162.07ZM1370.4,517.4c-89.49,0-162.07,72.57-162.07,162.07s72.58,162.07,162.07,162.07,162.06-72.57,162.06-162.07c-.02-89.45-72.52-161.97-161.97-162.02l-.09-.04ZM1827.34,679.47c0,252.22-204.65,456.69-456.93,456.69-115.09.17-225.95-43.24-310.29-121.55l-146.24,159.11-146.31-159.29c-84.32,78.4-195.24,121.88-310.38,121.73C205.08,1136.16.5,931.68.5,679.47c-.18-128.48,53.93-251.07,148.97-337.52L0,179.34h332.06c351.35-239.11,813.15-239.11,1164.48,0h331.2l-149.43,162.61c95.07,86.43,149.19,209.02,149.03,337.52ZM766.12,679.47c0-170.69-138.39-309.07-309.07-309.07s-309.07,138.39-309.07,309.07,138.39,309.07,309.07,309.07,309.05-138.34,309.07-309.03v-.05ZM1256.82,218.03c-219.53-91.45-466.48-91.45-686.01,0,195.13,74.69,343.06,249.13,343.06,452.52,0-203.37,147.91-377.84,342.98-452.5l-.02-.02ZM1679.54,679.44c0-170.69-138.39-309.07-309.07-309.07s-309.08,138.39-309.08,309.07,138.38,309.07,309.08,309.07,309.07-138.39,309.07-309.07Z" />
              </svg>
            </a>

            <a href="#" style="--pf-color:#ffffff" aria-label="GitHub">
              <i class="fab fa-github"></i>
            </a>

          </div>
        </div>

      </div>
    </section>


  </main>

  <!-- Footer -->
  <footer class="footer">
    <p>© 2025 Cantinho Deolinda — Todos os direitos reservados</p>
  </footer>

  <script src="Js/ModoEscuro.js"></script>
  <script src="Js/loader.js"></script>
  <script src="Js/carrosel.js"></script>
  <script src="Js/menu.js"></script>
  <script src="Js/Modal_reservas.js"></script>
  <script src="Js/contacto.js"></script>
  <script src="Js/backhome.js"></script>
  <script src="Js/chatbot.js"></script>

</body>

</html>