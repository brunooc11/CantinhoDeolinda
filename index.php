<?php
session_start();
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

  <link rel="icon" type="image/png" href="Imagens/logo.png">
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
      <a href="dashboard.php" class="btt-padrao-login">⚙️ Conta</a>

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
        <li><a href="#">Home</a></li>
        <li><a href="#menu_carrosel">Menu</a></li>
        <li><a href="#">Galeria</a></li>
        <li><a href="#">Localização</a></li>
        <li><a href="#">Experiencia</a></li>
      </ul>

      <!--Se o login tiver feito ele abre a dashboard na aba reservas -->
      <a href="<?php echo isset($_SESSION['id']) ? 'dashboard.php?tab=Reservas' : 'login.php'; ?>" class="btn-reserva">Reservas</a>
    </div>

    <!-- Landing Page -->
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
              <button class="btn filled" id="openReservaModal">Reserva Agora</button>
            <?php else: ?>
              <a href="login.php" class="btn filled">Reserva Agora</a>
            <?php endif; ?>

            <button class="btn">Our Location</button>
          </div>
        </div>
      </div>
    </div>


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
    <section class="banner">
      <img src="Imagens/batata.png" alt="French Fries">
      <div class="banner-text">
        <h4>Hungry?</h4>
        <h2>We will home deliver!</h2>
        <button>MAKE AN ORDER</button>
      </div>
    </section>

    <!-- 🔥 Novo bloco: Catering -->
    <section class="catering">
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

  </main>

  <script src="Js/ModoEscuro.js"></script>
  <script src="Js/loader.js"></script>
  <script src="Js/carrosel.js"></script>
  <script src="Js/menu.js"></script>
  <script src="Js/Modal_reservas.js"></script>

</body>

</html>