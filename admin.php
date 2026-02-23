<?php
session_start();

// Se nao estiver logado
if (!isset($_SESSION['permissoes'])) {
    header("Location: login.php");
    exit();
}

// Se nao for admin
if ($_SESSION['permissoes'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require("Bd/ligar.php");
require_once("Bd/popup_helper.php");

function esc($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


// --- MARCAR COMPARECEU / NAO COMPARECEU ---
if (isset($_GET['presenca']) && isset($_GET['reserva'])) {
    $idReserva = intval($_GET['reserva']);
    $estado = $_GET['presenca'] === 'compareceu' ? 'compareceu' : 'nao_compareceu';

    // Atualiza o estado da reserva
    mysqli_query($con, "UPDATE reservas SET estado = '$estado' WHERE id = $idReserva");

    // Se NAO compareceu -> contar faltas
    if ($estado === 'nao_compareceu') {

        // Buscar o cliente da reserva
        $sqlCli = mysqli_query($con, "SELECT cliente_id FROM reservas WHERE id = $idReserva");
        $cli = mysqli_fetch_assoc($sqlCli);
        $clienteID = $cli['cliente_id'];

        // Contar faltas deste cliente
        $faltasSQL = mysqli_query(
            $con,
            "SELECT COUNT(*) AS faltas 
             FROM reservas 
             WHERE cliente_id = $clienteID AND estado = 'nao_compareceu'"
        );
        $faltas = mysqli_fetch_assoc($faltasSQL)['faltas'];

        // BLOQUEAR O USER PARA NAO PODER RESERVAR, AUTOMATICO APOS 2 FALTAS
        if ($faltas >= 2) {
            mysqli_query($con, "UPDATE Cliente SET lista_negra = 1 WHERE id = $clienteID");
        }
    }

    header("Location: admin.php");
    exit();
}


// BLOQUEAR UTILIZADOR
if (isset($_GET['bloquear'])) {
    $id = intval($_GET['bloquear']);

    // Impedir autobloqueio
    if ($id == $_SESSION['id']) {
        cd_popup('Nao te podes bloquear a ti proprio!', 'error', 'admin.php');
        exit();
    }

    mysqli_query($con, "UPDATE Cliente SET estado = 0 WHERE id = $id");
    cd_popup('Utilizador bloqueado!', 'success', 'admin.php');
    exit();
}

// DESBLOQUEAR UTILIZADOR
if (isset($_GET['desbloquear'])) {
    $id = intval($_GET['desbloquear']);
    mysqli_query($con, "UPDATE Cliente SET estado = 1 WHERE id = $id");
    cd_popup('Utilizador desbloqueado!', 'success', 'admin.php');
    exit();
}

// TORNAR ADMIN
if (isset($_GET['role_admin'])) {
    $id = intval($_GET['role_admin']);
    mysqli_query($con, "UPDATE Cliente SET permissoes = 'admin' WHERE id = $id");
    cd_popup('Utilizador agora e admin!', 'success', 'admin.php');
    exit();
}

// TORNAR CLIENTE
if (isset($_GET['role_user'])) {
    $id = intval($_GET['role_user']);

    //impefir o admin de se autodespromover 
    if ($id == $_SESSION['id']) {
        cd_popup('Nao te podes remover a ti proprio como admin!', 'error', 'admin.php');
        exit();
    }

    $check = mysqli_query($con, "SELECT COUNT(*) AS total FROM Cliente WHERE permissoes = 'admin'");
    $row = mysqli_fetch_assoc($check);

    if ($row['total'] <= 1) {
        cd_popup('Nao podes remover o ultimo admin!', 'error', 'admin.php');
        exit();
    }

    mysqli_query($con, "UPDATE Cliente SET permissoes = 'cliente' WHERE id = $id");
    cd_popup('Utilizador agora e cliente!', 'success', 'admin.php');
    exit();
}

// ESTADO DO SITE
$sql = "SELECT bloqueado FROM estado_site LIMIT 1";
$res = mysqli_query($con, $sql);
$row = mysqli_fetch_assoc($res);
$bloqueado = $row['bloqueado'];

if (isset($_POST['bloquear_site'])) {
    mysqli_query($con, "UPDATE estado_site SET bloqueado = 1");
    cd_popup('Site bloqueado!', 'success', 'admin.php');
    exit();
}

if (isset($_POST['ativar_site'])) {
    mysqli_query($con, "UPDATE estado_site SET bloqueado = 0");
    cd_popup('Site ativado!', 'success', 'admin.php');
    exit();
}

// RESETAR FALTAS DO UTILIZADOR
if (isset($_GET['reset_faltas'])) {
    $id = intval($_GET['reset_faltas']);

    // Atualizar reservas removendo faltas
    mysqli_query(
        $con,
        "UPDATE reservas 
         SET estado = 'perdoado(reset)' 
         WHERE cliente_id = $id 
           AND estado = 'nao_compareceu'"
    );

    mysqli_query($con, "UPDATE Cliente SET lista_negra = 0 WHERE id = $id");

    cd_popup('Faltas resetadas e utilizador removido da lista negra.', 'success', 'admin.php');
    exit();
}

// KPI simples para topo do painel
$kpiTotalUsers = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM Cliente"))['total'] ?? 0);
$kpiUsersAtivos = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM Cliente WHERE estado = 1"))['total'] ?? 0);
$kpiListaNegra = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM Cliente WHERE lista_negra = 1"))['total'] ?? 0);
$kpiReservasPendentes = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM reservas WHERE confirmado = 0"))['total'] ?? 0);
$kpiReservasHoje = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM reservas WHERE data_reserva = CURDATE()"))['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Painel de Administracao</title>
    <link rel="stylesheet" href="Css/admin.css">
    <link rel="stylesheet" href="Css/bttlogin.css">
</head>

<body class="cdol-admin">

    <div class="container">
        <div class="admin-hero">
            <div>
                <h2>Painel de Administracao</h2>
                <p>Gestao central de clientes, reservas e estado do site.</p>
            </div>
            <div class="admin-kpis">
                <div class="admin-kpi-card">
                    <span>Total clientes</span>
                    <strong><?php echo $kpiTotalUsers; ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Clientes ativos</span>
                    <strong><?php echo $kpiUsersAtivos; ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Lista negra</span>
                    <strong><?php echo $kpiListaNegra; ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Reservas pendentes</span>
                    <strong><?php echo $kpiReservasPendentes; ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Reservas hoje</span>
                    <strong><?php echo $kpiReservasHoje; ?></strong>
                </div>
            </div>
        </div>

        <section class="admin-section">
        <h3>Estado do Site</h3>
        <form method="post" class="admin-site-state">
            <?php if ($bloqueado == 0): ?>
                <button type="submit" name="bloquear_site" class="btn danger">Bloquear Site</button>
            <?php else: ?>
                <button type="submit" name="ativar_site" class="btn success">Ativar Site</button>
            <?php endif; ?>
        </form>
        </section>

        <section class="admin-section">
        <h3>Utilizadores</h3>
        <div class="admin-search-bar">
            <input type="text" id="adminUsersSearchInput" placeholder="Procurar utilizador (nome, email, telefone...)">
            <button type="button" class="btn" id="adminUsersSearchBtn">Procurar</button>
        </div>
        <div class="admin-filter-bar">
            <select id="adminUsersEstadoFilter">
                <option value="">Estado: Todos</option>
                <option value="ativo">Ativo</option>
                <option value="bloqueado">Bloqueado</option>
            </select>
            <select id="adminUsersTipoFilter">
                <option value="">Tipo: Todos</option>
                <option value="admin">Admin</option>
                <option value="cliente">Cliente</option>
            </select>
            <select id="adminUsersListaNegraFilter">
                <option value="">Lista Negra: Todos</option>
                <option value="sim">Sim</option>
                <option value="nao">Nao</option>
            </select>
            <button type="button" class="btn admin-clear-btn" id="adminUsersClearBtn">Limpar</button>
        </div>

        <div class="admin-table-wrap">
        <table id="adminUsersTable" class="admin-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Estado</th>
                    <th>Tipo</th>
                    <th>Faltas</th>
                    <th>Lista Negra</th>
                    <th>Reset</th>
                    <th>Acao</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>

            <?php
            $sql = "SELECT 
                        c.id, c.nome, c.email, c.telefone, c.estado, c.permissoes, c.lista_negra,
                        COALESCE(f.faltas, 0) AS faltas
                    FROM Cliente c
                    LEFT JOIN (
                        SELECT cliente_id, COUNT(*) AS faltas
                        FROM reservas
                        WHERE estado = 'nao_compareceu'
                        GROUP BY cliente_id
                    ) f ON f.cliente_id = c.id
                    ORDER BY c.nome ASC";
            $res = mysqli_query($con, $sql);

            while ($user = mysqli_fetch_assoc($res)) {
                $estadoUserTexto = $user['estado'] == 1 ? 'ativo' : 'bloqueado';
                $listaNegraTexto = $user['lista_negra'] == 1 ? 'sim' : 'nao';
                $searchUserText = strtolower(trim(
                    $user['nome'] . ' ' .
                    $user['email'] . ' ' .
                    $user['telefone'] . ' ' .
                    $estadoUserTexto . ' ' .
                    $user['permissoes'] . ' ' .
                    $listaNegraTexto
                ));

                echo "<tr 
                        data-admin-search='" . esc($searchUserText) . "'
                        data-estado='" . esc($estadoUserTexto) . "'
                        data-tipo='" . esc(strtolower($user['permissoes'])) . "'
                        data-lista-negra='" . esc($listaNegraTexto) . "'>";
                echo "<td>" . esc($user['nome']) . "</td>";
                echo "<td>" . esc($user['email']) . "</td>";
                echo "<td>" . esc($user['telefone']) . "</td>";

                echo $user['estado'] == 1
                    ? "<td><span class='status-chip ok'>Ativo</span></td>"
                    : "<td><span class='status-chip bad'>Bloqueado</span></td>";

                $roleClass = $user['permissoes'] === 'admin' ? 'warn' : 'neutral';
                echo "<td><span class='status-chip $roleClass'>" . esc(strtoupper($user['permissoes'])) . "</span></td>";

                $faltasUser = (int)$user['faltas'];

                // Cor das faltas 
                $classeFalta = $faltasUser >= 2 ? 'bad' : 'neutral';
                echo "<td><span class='status-chip $classeFalta'>$faltasUser</span></td>";

                // Lista negra
                if ($user['lista_negra'] == 1) {
                    echo "<td><span class='status-chip bad'>Sim</span></td>";
                } else {
                    echo "<td><span class='status-chip ok'>Nao</span></td>";
                }

                // Mostrar botao de reset se houver faltas
                echo "<td class='admin-actions-cell'>";
                if ($faltasUser > 0) {
                    echo "<a class='action-btn blue-btn' 
                        href='admin.php?reset_faltas={$user['id']}'>
                        Resetar Faltas
                        </a>";
                } else {
                    echo "<span class='status-chip neutral'>Sem faltas</span>";
                }
                echo "</td>";

                //Btt de bloquear/Desbloquear
                echo "<td class='admin-actions-cell'>";
                if ($user['estado'] == 1) {
                    echo "<a class='action-btn' href='admin.php?bloquear={$user['id']}'>Bloquear</a>";
                } else {
                    echo "<a class='action-btn green-btn' href='admin.php?desbloquear={$user['id']}'>Desbloquear</a>";
                }
                echo "</td>";


                echo "<td class='admin-actions-cell'>";
                if ($user['permissoes'] === 'admin') {
                    echo "<a class='action-btn blue-btn' href='admin.php?role_user={$user['id']}'>Tornar Cliente</a>";
                } else {
                    echo "<a class='action-btn blue-btn' href='admin.php?role_admin={$user['id']}'>Tornar Admin</a>";
                }
                echo "</td>";

                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
        </div>
        <p id="adminUsersSearchEmpty" class="admin-search-empty">Sem resultados para utilizadores.</p>
        </section>

        <section class="admin-section">
        <h3>Lista Negra</h3>
        <div class="admin-table-wrap">
        <table id="adminBlacklistTable" class="admin-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Faltas</th>
                    <th>Ultima Falta</th>
                    <th>Acao</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sqlListaNegra = "
                SELECT
                    c.id,
                    c.nome,
                    c.email,
                    c.telefone,
                    COALESCE(f.faltas, 0) AS faltas,
                    f.ultima_falta
                FROM Cliente c
                LEFT JOIN (
                    SELECT
                        cliente_id,
                        COUNT(*) AS faltas,
                        MAX(data_reserva) AS ultima_falta
                    FROM reservas
                    WHERE estado = 'nao_compareceu'
                    GROUP BY cliente_id
                ) f ON f.cliente_id = c.id
                WHERE c.lista_negra = 1
                ORDER BY f.ultima_falta DESC, c.nome ASC
            ";

            $resListaNegra = mysqli_query($con, $sqlListaNegra);

            if ($resListaNegra && mysqli_num_rows($resListaNegra) > 0) {
                while ($ln = mysqli_fetch_assoc($resListaNegra)) {
                    $ultimaFalta = !empty($ln['ultima_falta']) ? esc($ln['ultima_falta']) : '-';
                    echo "<tr>";
                    echo "<td>" . esc($ln['nome']) . "</td>";
                    echo "<td>" . esc($ln['email']) . "</td>";
                    echo "<td>" . esc($ln['telefone']) . "</td>";
                    echo "<td><span class='status-chip bad'>" . (int)$ln['faltas'] . "</span></td>";
                    echo "<td>" . $ultimaFalta . "</td>";
                    echo "<td class='admin-actions-cell'>
                            <a class='action-btn blue-btn' href='admin.php?reset_faltas=" . (int)$ln['id'] . "'>
                                Remover da Lista Negra
                            </a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'><span class='status-chip ok'>Sem utilizadores na lista negra</span></td></tr>";
            }
            ?>
            </tbody>
        </table>
        </div>
        </section>

        <section class="admin-section">
        <h3>Gestao de Reservas</h3>
        <div class="admin-search-bar">
            <input type="text" id="adminReservasSearchInput" placeholder="Procurar reserva (id, cliente, data, hora...)">
            <button type="button" class="btn" id="adminReservasSearchBtn">Procurar</button>
        </div>
        <div class="admin-filter-bar">
            <select id="adminReservasConfirmacaoFilter">
                <option value="">Confirmacao: Todas</option>
                <option value="confirmada">Confirmada</option>
                <option value="pendente">Pendente</option>
                <option value="recusada">Recusada</option>
            </select>
            <select id="adminReservasEstadoFilter">
                <option value="">Estado: Todos</option>
                <option value="pendente">Pendente</option>
                <option value="compareceu">Compareceu</option>
                <option value="nao_compareceu">Nao compareceu</option>
                <option value="perdoado(reset)">Perdoado (reset)</option>
            </select>
            <button type="button" class="btn admin-clear-btn" id="adminReservasClearBtn">Limpar</button>
        </div>

        <div class="admin-table-wrap">
        <table id="adminReservasTable" class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Pessoas</th>
                    <th>Confirmacao</th>
                    <th>Estado</th>
                    <th>Acao</th>
                </tr>
            </thead>
            <tbody>

            <?php
            // Buscar todas as reservas com dados do cliente
            $reservas = mysqli_query(
                $con,
                "SELECT r.*, c.nome 
                FROM reservas r
                JOIN Cliente c ON r.cliente_id = c.id
                ORDER BY r.data_reserva DESC, r.hora_reserva DESC"
            );

            while ($r = mysqli_fetch_assoc($reservas)) {
                /* confirmacao da reserva */
                if ($r['confirmado'] == 1) {
                    $confLabel = "Confirmada";
                } elseif ($r['confirmado'] == -1) {
                    $confLabel = "Recusada";
                } else {
                    $confLabel = "Pendente";
                }



                /* tabela */
                $searchReservaText = strtolower(trim(
                    $r['id'] . ' ' .
                    $r['nome'] . ' ' .
                    $r['data_reserva'] . ' ' .
                    $r['hora_reserva'] . ' ' .
                    $r['numero_pessoas'] . ' ' .
                    $confLabel . ' ' .
                    $r['estado']
                ));
                if ($r['estado'] === 'compareceu') {
                    $estadoLabel = 'Compareceu';
                } elseif ($r['estado'] === 'nao_compareceu') {
                    $estadoLabel = 'Nao compareceu';
                } elseif ($r['estado'] === 'perdoado(reset)') {
                    $estadoLabel = 'Perdoado (reset)';
                } else {
                    $estadoLabel = 'Pendente';
                }
                echo "<tr 
                        data-admin-search='" . esc($searchReservaText) . "'
                        data-confirmacao='" . esc(strtolower($confLabel)) . "'
                        data-estado='" . esc(strtolower($r['estado'])) . "'>";

                echo "<td>" . esc($r['id']) . "</td>";
                echo "<td>" . esc($r['nome']) . "</td>";
                echo "<td>" . esc($r['data_reserva']) . "</td>";
                echo "<td>" . esc($r['hora_reserva']) . "</td>";
                echo "<td>" . esc($r['numero_pessoas']) . "</td>";

                // Confirmacao
                $confClass = $r['confirmado'] == 1 ? 'ok' : ($r['confirmado'] == -1 ? 'bad' : 'warn');
                echo "<td><span class='status-chip $confClass'>" . esc($confLabel) . "</span></td>";

                // Estado
                $estadoClass = $r['estado'] === 'compareceu' ? 'ok' : (($r['estado'] === 'nao_compareceu') ? 'bad' : 'warn');
                echo "<td><span class='status-chip $estadoClass'>" . esc($estadoLabel) . "</span></td>";


                /* btts */
                echo "<td class='admin-actions-cell'>";

                $estadoAtual = $r['estado'];

                // Mostrar botoes APENAS se:
                // - Reserva esta confirmada
                // - Estado ainda e pendente
                if ($r['confirmado'] == 1 && $estadoAtual === 'pendente') {

                    echo "<a class='action-btn green-btn' 
                    href='admin.php?presenca=compareceu&reserva={$r['id']}'>
                    Compareceu
                    </a>";

                    echo "<a class='action-btn danger' 
                    href='admin.php?presenca=nao_compareceu&reserva={$r['id']}'>
                    Nao Compareceu
                    </a>";
                } else {

                    // Estado final -> Nao mostrar botoes
                    echo "<span class='status-chip neutral'>Sem acoes</span>";
                }

                echo "</td>";


                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
        </div>
        <p id="adminReservasSearchEmpty" class="admin-search-empty">Sem resultados para reservas.</p>
        </section>


        <div class="botoesNav">
            <a href="index.php" id="btnInicio">&larr; Inicio</a>
            <a href="dashboard.php" id="btnDashboard">&larr; Dashboard</a>
            <a href="Bd/confirmar_reservas.php" id="btnConfirmarReservas">&larr; Confirmar Reservas</a>
        </div>

    </div>
    <script src="Js/popup_alert.js"></script>
    <script src="Js/admin_search.js"></script>
</body>

</html>


