<?php
require_once("config.php");
require_once("Bd/ligar.php");
require_once("Bd/popup_helper.php");
date_default_timezone_set('Europe/Lisbon');

// Verifica se o usuario esta logado
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function dash_csrf_token(): string
{
    return (string)($_SESSION['csrf_token'] ?? '');
}

function dash_csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(dash_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function dash_verify_csrf_or_fail(): void
{
    $token = (string)($_POST['csrf_token'] ?? '');
    $sessionToken = dash_csrf_token();
    if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        cd_popup('Pedido inválido (CSRF). Atualize a página e tente novamente.', 'error', 'dashboard.php');
        exit();
    }
}


// POPUP de reservas confirmadas / recusadas
$idCliente = $_SESSION['id'];

$sqlPopup = "
    SELECT confirmado
    FROM reservas
    WHERE cliente_id = ?
      AND notificado_reserva = 0
      AND confirmado != 0
";

$stmtPopup = mysqli_prepare($con, $sqlPopup);
mysqli_stmt_bind_param($stmtPopup, "i", $idCliente);
mysqli_stmt_execute($stmtPopup);
$resPopup = mysqli_stmt_get_result($stmtPopup);

$temAceite = false;
$temRecusada = false;

while ($row = mysqli_fetch_assoc($resPopup)) {
    if ($row['confirmado'] == 1) {
        $temAceite = true;
    }
    if ($row['confirmado'] == -1) {
        $temRecusada = true;
    }
}

mysqli_stmt_close($stmtPopup);

if ($temAceite || $temRecusada) {

    if ($temAceite && $temRecusada) {
        $msg = "Tem atualizações nas suas reservas.\n\n"
            . "Algumas reservas foram confirmadas.\n"
            . "Algumas reservas foram recusadas.";
    } elseif ($temAceite) {
        $msg = "A sua reserva foi confirmada pelo restaurante!";
    } else {
        $msg = "A sua reserva foi recusada pelo restaurante.";
    }

    cd_popup($msg, 'info');

    // marcar como notificado
    $sqlUpdate = "
        UPDATE reservas
        SET notificado_reserva = 1
        WHERE cliente_id = ?
          AND notificado_reserva = 0
          AND confirmado != 0
    ";
    $stmtUpdate = mysqli_prepare($con, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "i", $idCliente);
    mysqli_stmt_execute($stmtUpdate);
    mysqli_stmt_close($stmtUpdate);
}


// Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    dash_verify_csrf_or_fail();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Excluir conta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
    dash_verify_csrf_or_fail();
    $id = $_SESSION['id'];
    $query = "DELETE FROM Cliente WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    session_destroy();
    mysqli_close($con);
    header("Location: index.php");
    exit();
}

// Alterar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_senha'])) {
    dash_verify_csrf_or_fail();
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $mensagem = "Por favor, preencha todos os campos.";
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = "As novas palavras-passe não coincidem.";
    } else {
        $id = $_SESSION['id'];
        $query = "SELECT password FROM Cliente WHERE id = ?";
        $stmt = mysqli_prepare($con, $query);
        if (!$stmt) {
            die("Erro na preparação da query: " . mysqli_error($con));
        }

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $senha_bd);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if (!password_verify($senha_atual, $senha_bd)) {
            $mensagem = "A palavra-passe atual está incorreta.";
        } else {
            // Atualiza a password
            $nova_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $query = "UPDATE Cliente SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($con, $query);
            mysqli_stmt_bind_param($stmt, "si", $nova_hash, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Termina a sessao apos alterar a password
            session_destroy();

            // Redireciona para login com aviso
            header("Location: login.php?pw_alterada=1");
            exit();
        }
    }
}

// Buscar reservas do utilizador
$reservas = [];
$id_cliente = $_SESSION['id'];
$query = "SELECT id, data_reserva, hora_reserva, numero_pessoas ,confirmado
        FROM reservas 
        WHERE cliente_id = ? 
        ORDER BY data_reserva DESC, hora_reserva DESC";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $id_cliente);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $id_reserva, $data_reserva, $hora_reserva, $numero_pessoas, $confirmado);
while (mysqli_stmt_fetch($stmt)) {
    $reservas[] = [
        'id' => $id_reserva,
        'data' => $data_reserva,
        'hora' => $hora_reserva,
        'pessoas' => $numero_pessoas,
        'confirmado' => $confirmado
    ];
}
mysqli_stmt_close($stmt);

// Acoes de favoritos na dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_todos_favoritos'])) {
    dash_verify_csrf_or_fail();
    $checkTabelaFavoritos = mysqli_query($con, "SHOW TABLES LIKE 'favoritos'");
    if ($checkTabelaFavoritos && mysqli_num_rows($checkTabelaFavoritos) > 0) {
        $stmtRemoverTodos = mysqli_prepare($con, "DELETE FROM favoritos WHERE cliente_id = ?");
        if ($stmtRemoverTodos) {
            mysqli_stmt_bind_param($stmtRemoverTodos, "i", $id_cliente);
            mysqli_stmt_execute($stmtRemoverTodos);
            mysqli_stmt_close($stmtRemoverTodos);
        }
    }

    header("Location: dashboard.php?tab=Favoritos");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_favorito'])) {
    dash_verify_csrf_or_fail();
    $itemIdRemover = trim($_POST['item_id'] ?? '');
    $checkTabelaFavoritos = mysqli_query($con, "SHOW TABLES LIKE 'favoritos'");

    if ($itemIdRemover !== '' && $checkTabelaFavoritos && mysqli_num_rows($checkTabelaFavoritos) > 0) {
        $sqlRemover = "
            DELETE FROM favoritos
            WHERE cliente_id = ?
              AND (
                  item_id = ?
                  OR item_id LIKE CONCAT(?, '-%')
                  OR item_id LIKE CONCAT('%-', ?)
              )
        ";
        $stmtRemover = mysqli_prepare($con, $sqlRemover);
        if ($stmtRemover) {
            mysqli_stmt_bind_param($stmtRemover, "isss", $id_cliente, $itemIdRemover, $itemIdRemover, $itemIdRemover);
            mysqli_stmt_execute($stmtRemover);
            mysqli_stmt_close($stmtRemover);
        }
    }

    header("Location: dashboard.php?tab=Favoritos");
    exit();
}

// Buscar favoritos do utilizador
$favoritos = [];
$favoritosVistos = [];
$menuItensAtivos = [
    "bacalhau-a-casa" => "Bacalhau a Casa",
    "bacalhau-a-lagareiro" => "Bacalhau a Lagareiro",
    "acorda-de-bacalhau-com-gambas-no-pao" => "Acorda de Bacalhau com Gambas (no pao)",
    "polvo-a-lagareiro" => "Polvo a Lagareiro",
    "bife-a-casa" => "Bife a Casa",
    "espetadas-de-porco-preto" => "Espetadas de Porco Preto",
    "picanha" => "Picanha",
    "costeleta-de-novilho" => "Costeleta de Novilho",
    "secretos" => "Secretos",
    "cozido-a-portuguesa" => "Cozido a Portuguesa",
    "mini-prato-bebida" => "Mini-prato + bebida",
    "sopa-de-legumes" => "Sopa de Legumes",
    "sopa-de-peixe" => "Sopa de Peixe",
    "vinho-da-casa" => "Vinho da Casa",
    "sumo-natural" => "Sumo Natural"
];

function normalizarFavoritoId(string $rawId, array $idsAtivos): ?string
{
    if (isset($idsAtivos[$rawId])) {
        return $rawId;
    }

    foreach ($idsAtivos as $idAtivo => $_nome) {
        $prefixo = $idAtivo . "-";
        $sufixo = "-" . $idAtivo;
        $comecaCom = strpos($rawId, $prefixo) === 0;
        $terminaCom = substr($rawId, -strlen($sufixo)) === $sufixo;

        if ($comecaCom || $terminaCom) {
            return $idAtivo;
        }
    }

    return null;
}

$temTabelaFavoritos = false;
$temColunaNomeFavorito = false;
$tabelaFavoritosQuery = mysqli_query($con, "SHOW TABLES LIKE 'favoritos'");
if ($tabelaFavoritosQuery && mysqli_num_rows($tabelaFavoritosQuery) > 0) {
    $temTabelaFavoritos = true;
}

if ($temTabelaFavoritos) {
    $colunaNomeFavoritoQuery = mysqli_query($con, "SHOW COLUMNS FROM favoritos LIKE 'item_nome'");
    if ($colunaNomeFavoritoQuery && mysqli_num_rows($colunaNomeFavoritoQuery) > 0) {
        $temColunaNomeFavorito = true;
    }
}

if (!empty($mensagem)) {
    cd_popup($mensagem, 'error');
}

if (isset($_GET['confirmada']) && $_GET['confirmada'] == 1) {
    cd_popup('Reserva confirmada com sucesso.', 'success');
}

if (isset($_GET['erro']) && $_GET['erro'] == 1) {
    cd_popup('Erro ao confirmar a reserva.', 'error');
}

if (isset($_GET['erro_cancelamento']) && $_GET['erro_cancelamento'] === 'final_2h') {
    cd_popup('Cancelamento online indisponível nas 2h finais. Contacte: +351 966 545 510.', 'error');
}

if (isset($_GET['erro_cancelamento']) && $_GET['erro_cancelamento'] === 'apos_horario') {
    cd_popup('Não é possível cancelar após o horário da reserva.', 'error');
}

if (isset($_GET['erro_cancelamento']) && $_GET['erro_cancelamento'] === 'prazo') {
    cd_popup('Cancelamento fora do prazo permitido.', 'error');
}

if (isset($_GET['erro_cancelamento']) && $_GET['erro_cancelamento'] === 'nao_encontrada') {
    cd_popup('Reserva não encontrada.', 'error');
}

if ($temTabelaFavoritos && $temColunaNomeFavorito) {
    $queryFavoritos = "SELECT item_id, item_nome, criado_em FROM favoritos WHERE cliente_id = ? ORDER BY criado_em DESC";
    $stmtFavoritos = mysqli_prepare($con, $queryFavoritos);
    if ($stmtFavoritos) {
        mysqli_stmt_bind_param($stmtFavoritos, "i", $id_cliente);
        mysqli_stmt_execute($stmtFavoritos);
        mysqli_stmt_bind_result($stmtFavoritos, $favId, $favNome, $favData);
        while (mysqli_stmt_fetch($stmtFavoritos)) {
            $favNormalizado = normalizarFavoritoId((string) $favId, $menuItensAtivos);
            if ($favNormalizado === null) {
                continue;
            }
            if (isset($favoritosVistos[$favNormalizado])) {
                continue;
            }
            $favoritosVistos[$favNormalizado] = true;

            $favoritos[] = [
                'item_id' => $favNormalizado,
                'nome' => $menuItensAtivos[$favNormalizado],
                'data' => $favData
            ];
        }
        mysqli_stmt_close($stmtFavoritos);
    }
} elseif ($temTabelaFavoritos) {
    $queryFavoritos = "SELECT item_id, criado_em FROM favoritos WHERE cliente_id = ? ORDER BY criado_em DESC";
    $stmtFavoritos = mysqli_prepare($con, $queryFavoritos);
    if ($stmtFavoritos) {
        mysqli_stmt_bind_param($stmtFavoritos, "i", $id_cliente);
        mysqli_stmt_execute($stmtFavoritos);
        mysqli_stmt_bind_result($stmtFavoritos, $favId, $favData);
        while (mysqli_stmt_fetch($stmtFavoritos)) {
            $favNormalizado = normalizarFavoritoId((string) $favId, $menuItensAtivos);
            if ($favNormalizado === null) {
                continue;
            }
            if (isset($favoritosVistos[$favNormalizado])) {
                continue;
            }
            $favoritosVistos[$favNormalizado] = true;

            $favoritos[] = [
                'item_id' => $favNormalizado,
                'nome' => $menuItensAtivos[$favNormalizado],
                'data' => $favData
            ];
        }
        mysqli_stmt_close($stmtFavoritos);
    }
}


// Cancelar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_reserva'])) {
    dash_verify_csrf_or_fail();
    $id_reserva = intval($_POST['id_reserva']);

    $queryData = "SELECT data_reserva, hora_reserva FROM reservas WHERE id = ? AND cliente_id = ? LIMIT 1";
    $stmtData = mysqli_prepare($con, $queryData);
    mysqli_stmt_bind_param($stmtData, "ii", $id_reserva, $_SESSION['id']);
    mysqli_stmt_execute($stmtData);
    mysqli_stmt_bind_result($stmtData, $data_reserva_cancelar, $hora_reserva_cancelar);
    $encontrou = mysqli_stmt_fetch($stmtData);
    mysqli_stmt_close($stmtData);

    if (!$encontrou) {
        header("Location: dashboard.php?tab=Reservas&erro_cancelamento=nao_encontrada");
        exit();
    }

    $timezone = new DateTimeZone('Europe/Lisbon');
    $agora = new DateTime('now', $timezone);
    $reservaDateTime = DateTime::createFromFormat(
        'Y-m-d H:i:s',
        $data_reserva_cancelar . ' ' . $hora_reserva_cancelar,
        $timezone
    );

    if (!$reservaDateTime) {
        $timestampReserva = strtotime($data_reserva_cancelar . ' ' . $hora_reserva_cancelar);
        if ($timestampReserva === false) {
            header("Location: dashboard.php?tab=Reservas&erro_cancelamento=nao_encontrada");
            exit();
        }
        $reservaDateTime = new DateTime('@' . $timestampReserva);
        $reservaDateTime->setTimezone($timezone);
    }

    $limiteCancelamento = clone $reservaDateTime;
    $limiteCancelamento->modify('-2 hours');

    if ($agora >= $reservaDateTime) {
        header("Location: dashboard.php?tab=Reservas&erro_cancelamento=apos_horario");
        exit();
    }

    if ($agora >= $limiteCancelamento) {
        header("Location: dashboard.php?tab=Reservas&erro_cancelamento=final_2h");
        exit();
    }

    $query = "DELETE FROM reservas WHERE id = ? AND cliente_id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id_reserva, $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: dashboard.php?tab=Reservas");
    exit();
}

?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="Imagens/logo_atual.png">
    <title>Dashboard - Cantinho_Deolinda</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Css/admin.css?v=<?php echo filemtime(__DIR__ . '/Css/admin.css'); ?>">
    <link rel="stylesheet" href="Css/dashboard.css">
    <link rel="stylesheet" href="Css/bttlogin.css">
</head>

<body class="cdol-dash<?php echo (($_SESSION['permissoes'] ?? '') === 'admin') ? ' cdol-admin-home' : ''; ?>">

    <?php if (($_SESSION['permissoes'] ?? '') === 'admin'): ?>
        <script>
            document.documentElement.classList.add('admin-home-page');
        </script>
        <button type="button" class="admin-home-menu-toggle" aria-label="Abrir menu admin" aria-expanded="false" aria-controls="adminHomeSidebar">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <button type="button" class="admin-home-menu-overlay" aria-label="Fechar menu admin"></button>
        <aside class="admin-home-sidebar" id="adminHomeSidebar" aria-label="Menu administrativo">
            <div class="admin-home-sidebar-brand">
                <span class="admin-home-kicker">Cantinho Deolinda</span>
                <strong>Painel Admin</strong>
                <p>Centro de gestão do backoffice.</p>
            </div>
            <nav class="admin-home-nav">
                <a href="admin.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 11.2 12 4l8 7.2V20a1 1 0 0 1-1 1h-4.8v-5.5H9.8V21H5a1 1 0 0 1-1-1z"/></svg></span><span class="admin-home-link-copy"><strong>Visão geral</strong><small>Painel principal</small></span></a>
                <a href="Bd/confirmar_reservas.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 12.5 10.2 16 17 8.8"/><rect x="4" y="4" width="16" height="16" rx="4"/></svg></span><span class="admin-home-link-copy"><strong>Confirmar reservas</strong><small>Entradas pendentes</small></span></a>
                <a href="admin_reservas.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="5" width="6" height="6" rx="1.5"/><rect x="14" y="5" width="6" height="6" rx="1.5"/><rect x="4" y="13" width="6" height="6" rx="1.5"/><rect x="14" y="13" width="6" height="6" rx="1.5"/></svg></span><span class="admin-home-link-copy"><strong>Todas as reservas</strong><small>Lista completa</small></span></a>
                <a href="admin_logs.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 7h10M7 12h10M7 17h10"/><rect x="4" y="4" width="16" height="16" rx="4"/></svg></span><span class="admin-home-link-copy"><strong>Logs</strong><small>Atividade do sistema</small></span></a>
                <a href="admin_mapa.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M8 6.5 4.5 8v10L8 16.5l4 1.5 3.5-1.5L19.5 18V8l-4 1.5L12 8 8 9.5z"/><path d="M8 6.5v10M12 8v10M15.5 9.5v10"/></svg></span><span class="admin-home-link-copy"><strong>Mapa de mesas</strong><small>Disposição da sala</small></span></a>
                <a href="admin_feedback.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17.5 4.5 20V7a2 2 0 0 1 2-2h11A2.5 2.5 0 0 1 20 7.5v7a2.5 2.5 0 0 1-2.5 2.5z"/><path d="M8 10h8M8 13h5"/></svg></span><span class="admin-home-link-copy"><strong>Feedback</strong><small>Opiniões dos clientes</small></span></a>
            </nav>
            <div class="admin-home-sidebar-footer">
                <a href="dashboard.php" class="is-active" aria-current="page"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 19V9.5L12 5l7 4.5V19z"/><path d="M9 19v-5h6v5"/></svg></span><span class="admin-home-link-copy"><strong>Dashboard</strong><small>Vista do utilizador</small></span></a>
                <a href="index.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M10 7 5 12l5 5"/><path d="M6 12h9a4 4 0 1 0 0 8"/></svg></span><span class="admin-home-link-copy"><strong>Voltar ao site</strong><small>Regressar à homepage</small></span></a>
            </div>
        </aside>
    <?php endif; ?>

    <div class="dashboard-header">
        <h1>Olá, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</h1>
        <div class="dashboard-header-actions">
            <?php if (($_SESSION['permissoes'] ?? '') !== 'admin'): ?>
                <a href="index.php" id="bttInicio" class="btt-padrao-login">&larr; Voltar ao Início</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-menu">
        <button class="tablink" onclick="openTab('Conta')"><i class="fa-solid fa-user" aria-hidden="true"></i> Conta</button>
        <button class="tablink" onclick="openTab('Reservas')"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Reservas</button>
        <button class="tablink" onclick="openTab('Favoritos')"><i class="fa-solid fa-heart" aria-hidden="true"></i> Favoritos</button>
    </div>

    <!-- Abas -->
    <div id="Conta" class="tabcontent">
        <div class="card">
            <h3><i class="fa-solid fa-user" aria-hidden="true"></i> Minha Conta</h3>

            <div class="conta-info-grid">
                <div class="conta-info-item">
                    <span class="label">Nome</span>
                    <strong><?php echo htmlspecialchars($_SESSION['nome']); ?></strong>
                </div>
                <div class="conta-info-item">
                    <span class="label">Email</span>
                    <strong><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></strong>
                </div>
                <div class="conta-info-item">
                    <span class="label">Conta criada em</span>
                    <strong><?php echo date("d/m/Y H:i", strtotime($_SESSION['data'] ?? '')); ?></strong>
                </div>
            </div>

            <div class="conta-actions">
                <button type="button" class="btt-alterar-senha" onclick="toggleFormSenha()">Alterar Senha</button>

                <form method="POST">
                    <?php echo dash_csrf_input(); ?>
                    <button type="submit" name="logout" class="btt-sair" id="bttsair">Sair</button>
                </form>

                <form method="POST" onsubmit="return confirmarExclusao(event);">
                    <?php echo dash_csrf_input(); ?>
                    <button type="submit" name="excluir" class="btn btn-excluir" id="btt_excluir_conta">
                        Excluir Conta
                    </button>
                </form>
            </div>

            <div class="form-lateral-card" id="formSenhaCard">
                <form method="POST" class="senha-form">
                    <?php echo dash_csrf_input(); ?>
                    <div class="senha-form-head">
                        <h3>Alterar Palavra-passe</h3>
                        <p>Atualize a sua palavra-passe para manter a conta segura.</p>
                    </div>

                    <div class="senha-form-grid">
                        <label class="senha-field">
                            <span>Palavra-passe atual</span>
                            <div class="senha-input-wrap">
                                <input id="senhaAtualInput" type="password" name="senha_atual" placeholder="Digite a palavra-passe atual" required>
                                <button type="button" class="senha-toggle" data-target="senhaAtualInput" aria-label="Mostrar ou ocultar palavra-passe atual">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </label>
                        <label class="senha-field">
                            <span>Nova palavra-passe</span>
                            <div class="senha-input-wrap">
                                <input id="novaSenhaInput" type="password" name="nova_senha" placeholder="Digite a nova palavra-passe" required>
                                <button type="button" class="senha-toggle" data-target="novaSenhaInput" aria-label="Mostrar ou ocultar nova palavra-passe">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </label>
                        <label class="senha-field">
                            <span>Confirmar nova palavra-passe</span>
                            <div class="senha-input-wrap">
                                <input id="confirmarSenhaInput" type="password" name="confirmar_senha" placeholder="Confirme a nova palavra-passe" required>
                                <button type="button" class="senha-toggle" data-target="confirmarSenhaInput" aria-label="Mostrar ou ocultar confirmação da palavra-passe">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </label>
                    </div>

                    <ul class="senha-checklist" id="senhaChecklist" aria-live="polite">
                        <li id="ruleLength">Mínimo 8 caracteres</li>
                        <li id="ruleUpper">Tem letra maiúscula</li>
                        <li id="ruleLower">Tem letra minúscula</li>
                        <li id="ruleNumber">Tem número</li>
                        <li id="ruleSymbol">Tem símbolo (!@#...)</li>
                        <li id="ruleCurrentMatch">Palavra-passe atual correta</li>
                        <li id="ruleMatch">Confirmação coincide</li>
                    </ul>

                    <button type="submit" id="bttConfirmar" name="alterar_senha" class="btt-padrao-login">
                        Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="Reservas" class="tabcontent">
            <div class="card">
            <div class="reservas-header">
                <h3><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Reservas</h3>
                <div class="reservas-acoes">
                    <a href="index.php?abrir_reserva=1" class="btt-padrao-login" id="bttNovaReserva">
                        Fazer nova reserva
                    </a>
                </div>
            </div>

            <?php if (!empty($reservas)): ?>
                <div class="table-wrap">
                    <table class="tabela-reservas">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Pessoas</th>
                            <th>Confirmação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($reservas as $reserva): ?>
                            <tr>
                                <td><?php echo date("d/m/Y", strtotime($reserva['data'])); ?></td>
                                <td><?php echo substr($reserva['hora'], 0, 5); ?></td>
                                <td><?php echo htmlspecialchars($reserva['pessoas']); ?></td>

                                <!-- Coluna de Confirmacao -->
                                <td>
                                    <?php
                                    if ($reserva['confirmado'] == 1) {
                                        echo '<span class="status-badge confirmed">Confirmada</span>';
                                    } elseif ($reserva['confirmado'] == -1) {
                                        echo '<span class="status-badge rejected">Recusada</span>';
                                    } else {
                                        echo '<span class="status-badge pending">Pendente</span>';
                                    }
                                    ?>

                                </td>

                                <td class="acao-col">
                                    <?php
                                    $agoraTs = time();
                                    $reservaTs = strtotime($reserva['data'] . ' ' . $reserva['hora']);
                                    $faltamSegundos = $reservaTs - $agoraTs;
                                    $pode_cancelar = ($reservaTs !== false && $faltamSegundos > 7200);
                                    $apos_horario = ($reservaTs !== false && $faltamSegundos <= 0);
                                    ?>

                                    <?php if ($pode_cancelar): ?>
                                        <form method="POST" class="acao-form">
                                            <?php echo dash_csrf_input(); ?>
                                            <input type="hidden" name="id_reserva" value="<?php echo $reserva['id']; ?>">

                                            <button class="btn-cancelar" name="cancelar_reserva">
                                                <i class="fa-solid fa-xmark"></i> Cancelar
                                            </button>
                                        </form>
                                    <?php elseif ($apos_horario): ?>
                                        <span class="status-badge expired">Prazo expirado</span>
                                    <?php else: ?>
                                        <span class="status-badge pending">Contacte: +351 966 545 510</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                    </table>
                </div>
            <?php else: ?>
                <p>Ainda não há reservas.</p>
            <?php endif; ?>
        </div>
    </div>


    <div id="Favoritos" class="tabcontent">
        <div class="card">
            <div class="favoritos-header">
                <h3>Meus Favoritos</h3>
                <?php if (!empty($favoritos)): ?>
                    <form method="POST" class="favoritos-header-form" onsubmit="return confirmarRemoverTodosFavoritos(event);">
                        <?php echo dash_csrf_input(); ?>
                        <button type="submit" name="remover_todos_favoritos" class="btn-remover-todos-fav">
                            <i class="fa-solid fa-trash-can"></i> Remover todos
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if (!empty($favoritos)): ?>
                <div class="favoritos-lista">
                    <?php foreach ($favoritos as $fav): ?>
                        <div class="favorito-item">
                            <div class="favorito-topo">
                                <form method="POST" class="favorito-remover-form">
                                    <?php echo dash_csrf_input(); ?>
                                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($fav['item_id']); ?>">
                                    <button type="submit" name="remover_favorito" class="btn-remover-fav" title="Remover dos favoritos" aria-label="Remover dos favoritos">
                                        <i class="fa-solid fa-heart"></i>
                                    </button>
                                </form>
                                <strong><?php echo htmlspecialchars($fav['nome']); ?></strong>
                            </div>
                            <small>
                                Guardado em <?php echo date("d/m/Y H:i", strtotime($fav['data'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Ainda não há favoritos.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="Js/popup_alert.js"></script>
    <script src="Js/dashboard.js?v=<?php echo filemtime(__DIR__ . '/Js/dashboard.js'); ?>"></script>
    <script src="Js/alterar_senha.js?v=<?php echo filemtime(__DIR__ . '/Js/alterar_senha.js'); ?>"></script>

    <?php if (($_SESSION['permissoes'] ?? '') === 'admin'): ?>
        <script>
            (function () {
                var body = document.body;
                var toggle = document.querySelector('.admin-home-menu-toggle');
                var overlay = document.querySelector('.admin-home-menu-overlay');
                var sidebar = document.querySelector('.admin-home-sidebar');
                if (!toggle || !overlay || !sidebar) return;
                function closeMenu() {
                    body.classList.remove('admin-home-menu-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
                function openMenu() {
                    body.classList.add('admin-home-menu-open');
                    toggle.setAttribute('aria-expanded', 'true');
                }
                toggle.addEventListener('click', function () {
                    if (body.classList.contains('admin-home-menu-open')) closeMenu();
                    else openMenu();
                });
                overlay.addEventListener('click', closeMenu);
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') closeMenu();
                });
                sidebar.querySelectorAll('a').forEach(function (link) {
                    link.addEventListener('click', closeMenu);
                });
            })();
        </script>
    <?php endif; ?>

    <script>
        window.onload = function() {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab');
            if (tab) {
                openTab(tab);
            } else {
                openTab('Conta');
            }
        };
    </script>
</body>

</html>

