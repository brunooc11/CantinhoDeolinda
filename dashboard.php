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
        $msg = "Tem atualizacoes nas suas reservas.\n\n"
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
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Excluir conta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
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
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $mensagem = "Por favor, preencha todos os campos.";
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = "As novas senhas nao coincidem.";
    } else {
        $id = $_SESSION['id'];
        $query = "SELECT password FROM Cliente WHERE id = ?";
        $stmt = mysqli_prepare($con, $query);
        if (!$stmt) {
            die("Erro na preparacao da query: " . mysqli_error($con));
        }

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $senha_bd);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if (!password_verify($senha_atual, $senha_bd)) {
            $mensagem = "A senha atual esta incorreta.";
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
    <title>Dashboard - Cantinho_Deolinda</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Css/dashboard.css">
    <link rel="stylesheet" href="Css/bttlogin.css">
</head>

<body class="cdol-dash">

    <div class="dashboard-header">
        <h1>Ola, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</h1>
        <a href="index.php" id="bttInicio" class="btt-padrao-login">&larr; Voltar ao Inicio</a>
    </div>

    <div class="dashboard-menu">
        <button class="tablink" onclick="openTab('Conta')"><i class="fa-solid fa-user" aria-hidden="true"></i> Conta</button>
        <button class="tablink" onclick="openTab('Reservas')"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Reservas</button>
        <button class="tablink" onclick="openTab('Favoritos')"><i class="fa-solid fa-heart" aria-hidden="true"></i> Favoritos</button>
        <button class="tablink" onclick="openTab('Pedidos')"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> Pedidos</button>
    </div>

    <!-- Abas -->
    <div id="Conta" class="tabcontent">
        <div class="card">
            <h3>Minha Conta</h3>

            <?php if (!empty($mensagem)): ?>
                <p class="dash-alert <?php echo strpos($mensagem, 'sucesso') !== false ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($mensagem); ?>
                </p>
            <?php endif; ?>

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

                <form method="POST" action="?logout=1">
                    <button type="submit" class="btt-sair" id="bttsair">Sair</button>
                </form>

                <form method="POST" onsubmit="return confirmarExclusao();">
                    <button type="submit" name="excluir" class="btn btn-excluir" id="btt_excluir_conta">
                        Excluir Conta
                    </button>
                </form>
            </div>

            <div class="form-lateral-card" id="formSenhaCard">
                <form method="POST" class="senha-form">
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
                                <button type="button" class="senha-toggle" data-target="confirmarSenhaInput" aria-label="Mostrar ou ocultar confirmacao da palavra-passe">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </label>
                    </div>

                    <ul class="senha-checklist" id="senhaChecklist" aria-live="polite">
                        <li id="ruleLength">Minimo 8 caracteres</li>
                        <li id="ruleUpper">Tem letra maiuscula</li>
                        <li id="ruleLower">Tem letra minuscula</li>
                        <li id="ruleNumber">Tem numero</li>
                        <li id="ruleSymbol">Tem simbolo (!@#...)</li>
                        <li id="ruleCurrentMatch">Palavra-passe atual correta</li>
                        <li id="ruleMatch">Confirmacao coincide</li>
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
            <?php
            if (isset($_GET['confirmada']) && $_GET['confirmada'] == 1) {
                echo '<p class="dash-alert success">Reserva confirmada com sucesso.</p>';
            }

            if (isset($_GET['erro']) && $_GET['erro'] == 1) {
                echo '<p class="dash-alert danger">Erro ao confirmar a reserva.</p>';
            }

            if (isset($_GET['erro_cancelamento']) && $_GET['erro_cancelamento'] === 'final_2h') {
                echo '<p class="dash-alert danger">Cancelamento online indisponivel nas 2h finais. Contacte: +351 966 545 510.</p>';
            }

            if (isset($_GET['erro_cancelamento']) && $_GET['erro_cancelamento'] === 'apos_horario') {
                echo '<p class="dash-alert danger">Nao e possivel cancelar apos o horario da reserva.</p>';
            }

            if (isset($_GET['erro_cancelamento']) && $_GET['erro_cancelamento'] === 'prazo') {
                echo '<p class="dash-alert danger">Cancelamento fora do prazo permitido.</p>';
            }

            if (isset($_GET['erro_cancelamento']) && $_GET['erro_cancelamento'] === 'nao_encontrada') {
                echo '<p class="dash-alert danger">Reserva nao encontrada.</p>';
            }
            ?>
            <div class="reservas-header">
                <h3>Reservas</h3>
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
                            <th>Confirmacao</th>
                            <th>Acoes</th>
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
                <p>Ainda nao ha reservas.</p>
            <?php endif; ?>
        </div>
    </div>


    <div id="Favoritos" class="tabcontent">
        <div class="card">
            <div class="favoritos-header">
                <h3>Meus Favoritos</h3>
                <?php if (!empty($favoritos)): ?>
                    <form method="POST" class="favoritos-header-form" onsubmit="return confirm('Tem a certeza que quer remover todos os favoritos?');">
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
                <p>Ainda nao ha favoritos.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="Pedidos" class="tabcontent">
        <div class="card">
            <h3>Meus Pedidos</h3>
            <p>Ainda nao ha pedidos.</p>
        </div>
    </div>


    <script src="Js/popup_alert.js"></script>
    <script src="Js/dashboard.js?v=<?php echo filemtime(__DIR__ . '/Js/dashboard.js'); ?>"></script>
    <script src="Js/alterar_senha.js?v=<?php echo filemtime(__DIR__ . '/Js/alterar_senha.js'); ?>"></script>

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
