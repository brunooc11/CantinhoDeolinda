<?php
session_start();

// Se não estiver logado
if (!isset($_SESSION['permissoes'])) {
    header("Location: login.php");
    exit();
}

// Se não for admin
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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function cd_csrf_token()
{
    return (string)($_SESSION['csrf_token'] ?? '');
}

function cd_csrf_input()
{
    return '<input type="hidden" name="csrf_token" value="' . esc(cd_csrf_token()) . '">';
}

function cd_verify_csrf_or_fail()
{
    $token = (string)($_POST['csrf_token'] ?? '');
    $sessionToken = cd_csrf_token();
    if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        cd_popup('Pedido inválido (CSRF). Atualiza a página e tenta novamente.', 'error', 'admin.php');
        exit();
    }
}

function cd_stmt_prepare($con, $sql)
{
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        die('Erro ao preparar query SQL.');
    }
    return $stmt;
}

function cd_stmt_exec($con, $sql, $types = '', ...$params)
{
    $stmt = cd_stmt_prepare($con, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}

function cd_fetch_one($con, $sql, $types = '', ...$params)
{
    $stmt = cd_stmt_exec($con, $sql, $types, ...$params);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row;
}

function cd_fetch_all($con, $sql, $types = '', ...$params)
{
    $stmt = cd_stmt_exec($con, $sql, $types, ...$params);
    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function cd_execute($con, $sql, $types = '', ...$params)
{
    $stmt = cd_stmt_exec($con, $sql, $types, ...$params);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected;
}

function cd_has_column($con, $table, $column)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }
    $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $res = mysqli_query($con, $sql);
    return $res && mysqli_num_rows($res) > 0;
}

$hasClienteUltimoLogin = cd_has_column($con, 'Cliente', 'ultimo_login');

mysqli_query(
    $con,
    "CREATE TABLE IF NOT EXISTS admin_audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        admin_nome VARCHAR(255) NOT NULL,
        acao VARCHAR(100) NOT NULL,
        alvo_tipo VARCHAR(100) NOT NULL,
        alvo_id INT NULL,
        detalhes TEXT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

function cd_admin_audit($con, $acao, $alvoTipo, $alvoId = null, $detalhes = null)
{
    $adminId = (int)($_SESSION['id'] ?? 0);
    $adminNome = (string)($_SESSION['nome'] ?? 'admin');
    cd_execute(
        $con,
        "INSERT INTO admin_audit_log (admin_id, admin_nome, acao, alvo_tipo, alvo_id, detalhes) VALUES (?, ?, ?, ?, ?, ?)",
        "isssis",
        $adminId,
        $adminNome,
        $acao,
        $alvoTipo,
        $alvoId,
        $detalhes
    );
}

function cd_fmt_datetime($value)
{
    $raw = trim((string)$value);
    if ($raw === '' || $raw === '0000-00-00 00:00:00' || $raw === '0000-00-00') {
        return '-';
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return esc($raw);
    }

    return date('d/m/Y H:i', $ts);
}


// --- MARCAR COMPARECEU / NÃO COMPARECEU ---
if (isset($_POST['presenca']) && isset($_POST['reserva'])) {
    cd_verify_csrf_or_fail();
    $idReserva = intval($_POST['reserva']);
    $estado = $_POST['presenca'] === 'compareceu' ? 'compareceu' : 'nao_compareceu';

    // Atualiza o estado da reserva
    cd_execute($con, "UPDATE reservas SET estado = ? WHERE id = ?", "si", $estado, $idReserva);

    // Se NÃO compareceu -> contar faltas
    if ($estado === 'nao_compareceu') {

        // Buscar o cliente da reserva
        $cli = cd_fetch_one($con, "SELECT cliente_id FROM reservas WHERE id = ?", "i", $idReserva);
        $clienteID = (int)($cli['cliente_id'] ?? 0);

        // Contar faltas deste cliente
        $faltasRow = cd_fetch_one(
            $con,
            "SELECT COUNT(*) AS faltas FROM reservas WHERE cliente_id = ? AND estado = 'nao_compareceu'",
            "i",
            $clienteID
        );
        $faltas = (int)($faltasRow['faltas'] ?? 0);

        // BLOQUEAR O USER PARA NÃO PODER RESERVAR, AUTOMÁTICO APÓS 2 FALTAS
        if ($clienteID > 0 && $faltas >= 2) {
            cd_execute($con, "UPDATE Cliente SET lista_negra = 1 WHERE id = ?", "i", $clienteID);
        }

        cd_admin_audit(
            $con,
            'marcar_presenca',
            'reserva',
            $idReserva,
            "estado={$estado};cliente_id={$clienteID};faltas={$faltas}"
        );
    } else {
        cd_admin_audit($con, 'marcar_presenca', 'reserva', $idReserva, "estado={$estado}");
    }

    header("Location: admin.php");
    exit();
}


// BLOQUEAR UTILIZADOR
if (isset($_POST['bloquear'])) {
    cd_verify_csrf_or_fail();
    $id = intval($_POST['bloquear']);

    // Impedir autobloqueio
    if ($id == $_SESSION['id']) {
        cd_popup('Não te podes bloquear a ti próprio!', 'error', 'admin.php');
        exit();
    }

    cd_execute($con, "UPDATE Cliente SET estado = 0 WHERE id = ?", "i", $id);
    cd_admin_audit($con, 'bloquear_utilizador', 'cliente', $id, 'estado=0');
    cd_popup('Utilizador bloqueado!', 'success', 'admin.php');
    exit();
}

// DESBLOQUEAR UTILIZADOR
if (isset($_POST['desbloquear'])) {
    cd_verify_csrf_or_fail();
    $id = intval($_POST['desbloquear']);
    cd_execute($con, "UPDATE Cliente SET estado = 1 WHERE id = ?", "i", $id);
    cd_admin_audit($con, 'desbloquear_utilizador', 'cliente', $id, 'estado=1');
    cd_popup('Utilizador desbloqueado!', 'success', 'admin.php');
    exit();
}

// TORNAR ADMIN
if (isset($_POST['role_admin'])) {
    cd_verify_csrf_or_fail();
    $id = intval($_POST['role_admin']);
    cd_execute($con, "UPDATE Cliente SET permissoes = 'admin' WHERE id = ?", "i", $id);
    cd_admin_audit($con, 'alterar_role', 'cliente', $id, 'permissoes=admin');
    cd_popup('Utilizador agora é admin!', 'success', 'admin.php');
    exit();
}

// TORNAR CLIENTE
if (isset($_POST['role_user'])) {
    cd_verify_csrf_or_fail();
    $id = intval($_POST['role_user']);

    // impedir o admin de se autodespromover
    if ($id == $_SESSION['id']) {
        cd_popup('Não te podes remover a ti próprio como admin!', 'error', 'admin.php');
        exit();
    }

    $row = cd_fetch_one($con, "SELECT COUNT(*) AS total FROM Cliente WHERE permissoes = 'admin'");

    if ($row['total'] <= 1) {
        cd_popup('Não podes remover o último admin!', 'error', 'admin.php');
        exit();
    }

    cd_execute($con, "UPDATE Cliente SET permissoes = 'cliente' WHERE id = ?", "i", $id);
    cd_admin_audit($con, 'alterar_role', 'cliente', $id, 'permissoes=cliente');
    cd_popup('Utilizador agora é cliente!', 'success', 'admin.php');
    exit();
}

// ESTADO DO SITE
$row = cd_fetch_one($con, "SELECT bloqueado FROM estado_site LIMIT 1");
$bloqueado = $row['bloqueado'];

if (isset($_POST['bloquear_site'])) {
    cd_verify_csrf_or_fail();
    cd_execute($con, "UPDATE estado_site SET bloqueado = 1");
    cd_admin_audit($con, 'estado_site', 'estado_site', null, 'bloqueado=1');
    cd_popup('Site bloqueado!', 'success', 'admin.php');
    exit();
}

if (isset($_POST['ativar_site'])) {
    cd_verify_csrf_or_fail();
    cd_execute($con, "UPDATE estado_site SET bloqueado = 0");
    cd_admin_audit($con, 'estado_site', 'estado_site', null, 'bloqueado=0');
    cd_popup('Site ativado!', 'success', 'admin.php');
    exit();
}

// RESETAR FALTAS DO UTILIZADOR
if (isset($_POST['reset_faltas'])) {
    cd_verify_csrf_or_fail();
    $id = intval($_POST['reset_faltas']);

    // Atualizar reservas removendo faltas
    cd_execute(
        $con,
        "UPDATE reservas SET estado = 'perdoado(reset)' WHERE cliente_id = ? AND estado = 'nao_compareceu'",
        "i",
        $id
    );

    cd_execute($con, "UPDATE Cliente SET lista_negra = 0 WHERE id = ?", "i", $id);
    cd_admin_audit($con, 'reset_faltas', 'cliente', $id, 'estado=perdoado(reset);lista_negra=0');

    cd_popup('Faltas resetadas e utilizador removido da lista negra.', 'success', 'admin.php');
    exit();
}

// KPI simples para topo do painel
$kpiTotalUsers = (int)(cd_fetch_one($con, "SELECT COUNT(*) AS total FROM Cliente")['total'] ?? 0);
$kpiUsersAtivos = (int)(cd_fetch_one($con, "SELECT COUNT(*) AS total FROM Cliente WHERE estado = 1")['total'] ?? 0);
$kpiListaNegra = (int)(cd_fetch_one($con, "SELECT COUNT(*) AS total FROM Cliente WHERE lista_negra = 1")['total'] ?? 0);
$kpiReservasPendentes = (int)(cd_fetch_one($con, "SELECT COUNT(*) AS total FROM reservas WHERE confirmado = 0")['total'] ?? 0);
$kpiReservasHoje = (int)(cd_fetch_one($con, "SELECT COUNT(*) AS total FROM reservas WHERE data_reserva = CURDATE()")['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="Imagens/logo.png">
    <title>Painel de Administra&ccedil;&atilde;o</title>
    <link rel="stylesheet" href="Css/admin.css">
    <link rel="stylesheet" href="Css/bttlogin.css">
</head>

<body class="cdol-admin">

    <div class="container">
        <div class="admin-hero">
            <div>
                <h2>Painel de Administra&ccedil;&atilde;o</h2>
                <p>Gest&atilde;o central de clientes, reservas e estado do site.</p>
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
            <?php echo cd_csrf_input(); ?>
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
                <option value="nao">N&atilde;o</option>
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
                    <th>Data Registo</th>
                    <th>&Uacute;ltimo Login</th>
                    <th>Estado</th>
                    <th>Tipo</th>
                    <th>Faltas</th>
                    <th>Lista Negra</th>
                    <th>Reset</th>
                    <th>A&ccedil;&atilde;o</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>

            <?php
            $clienteDataSelect = "c.`Data` AS data_registo";
            $clienteUltimoLoginSelect = $hasClienteUltimoLogin ? "c.ultimo_login AS ultimo_login" : "NULL AS ultimo_login";
            $sql = "SELECT 
                        c.id, c.nome, c.email, c.telefone, c.estado, c.permissoes, c.lista_negra,
                        $clienteDataSelect,
                        $clienteUltimoLoginSelect,
                        COALESCE(f.faltas, 0) AS faltas
                    FROM Cliente c
                    LEFT JOIN (
                        SELECT cliente_id, COUNT(*) AS faltas
                        FROM reservas
                        WHERE estado = 'nao_compareceu'
                        GROUP BY cliente_id
                    ) f ON f.cliente_id = c.id
                    ORDER BY c.nome ASC";
            $users = cd_fetch_all($con, $sql);

            foreach ($users as $user) {
                $estadoUserTexto = $user['estado'] == 1 ? 'ativo' : 'bloqueado';
                $listaNegraTexto = $user['lista_negra'] == 1 ? 'sim' : 'nao';
                $searchUserText = strtolower(trim(
                    $user['nome'] . ' ' .
                    $user['email'] . ' ' .
                    $user['telefone'] . ' ' .
                    cd_fmt_datetime($user['data_registo'] ?? null) . ' ' .
                    cd_fmt_datetime($user['ultimo_login'] ?? null) . ' ' .
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
                $userEmail = (string)($user['email'] ?? '');
                echo "<td><span class='admin-email-text' title='" . esc($userEmail) . "'>" . esc($userEmail) . "</span></td>";
                echo "<td>" . esc($user['telefone']) . "</td>";
                echo "<td>" . cd_fmt_datetime($user['data_registo'] ?? null) . "</td>";
                echo "<td>" . cd_fmt_datetime($user['ultimo_login'] ?? null) . "</td>";

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
                    echo "<td><span class='status-chip ok'>N&atilde;o</span></td>";
                }

                // Mostrar botão de reset se houver faltas
                echo "<td class='admin-actions-cell'>";
                if ($faltasUser > 0) {
                    echo "<form method='post' class='admin-inline-form'>";
                    echo cd_csrf_input();
                    echo "<button type='submit' class='action-btn blue-btn' name='reset_faltas' value='" . (int)$user['id'] . "'>Resetar Faltas</button>";
                    echo "</form>";
                } else {
                    echo "<span class='status-chip neutral'>Sem faltas</span>";
                }
                echo "</td>";

                // Botão de bloquear/desbloquear
                echo "<td class='admin-actions-cell'>";
                if ($user['estado'] == 1) {
                    echo "<form method='post' class='admin-inline-form'>";
                    echo cd_csrf_input();
                    echo "<button type='submit' class='action-btn' name='bloquear' value='" . (int)$user['id'] . "'>Bloquear</button>";
                    echo "</form>";
                } else {
                    echo "<form method='post' class='admin-inline-form'>";
                    echo cd_csrf_input();
                    echo "<button type='submit' class='action-btn green-btn' name='desbloquear' value='" . (int)$user['id'] . "'>Desbloquear</button>";
                    echo "</form>";
                }
                echo "</td>";


                echo "<td class='admin-actions-cell'>";
                if ($user['permissoes'] === 'admin') {
                    echo "<form method='post' class='admin-inline-form'>";
                    echo cd_csrf_input();
                    echo "<button type='submit' class='action-btn blue-btn' name='role_user' value='" . (int)$user['id'] . "'>Tornar Cliente</button>";
                    echo "</form>";
                } else {
                    echo "<form method='post' class='admin-inline-form'>";
                    echo cd_csrf_input();
                    echo "<button type='submit' class='action-btn blue-btn' name='role_admin' value='" . (int)$user['id'] . "'>Tornar Admin</button>";
                    echo "</form>";
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
                    <th>&Uacute;ltima Falta</th>
                    <th>A&ccedil;&atilde;o</th>
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

            $listaNegraRows = cd_fetch_all($con, $sqlListaNegra);

            if (count($listaNegraRows) > 0) {
                foreach ($listaNegraRows as $ln) {
                    $ultimaFalta = !empty($ln['ultima_falta']) ? esc($ln['ultima_falta']) : '-';
                    echo "<tr>";
                    echo "<td>" . esc($ln['nome']) . "</td>";
                    $listaNegraEmail = (string)($ln['email'] ?? '');
                    echo "<td><span class='admin-email-text' title='" . esc($listaNegraEmail) . "'>" . esc($listaNegraEmail) . "</span></td>";
                    echo "<td>" . esc($ln['telefone']) . "</td>";
                    echo "<td><span class='status-chip bad'>" . (int)$ln['faltas'] . "</span></td>";
                    echo "<td>" . $ultimaFalta . "</td>";
                    echo "<td class='admin-actions-cell'>";
                    echo "<form method='post' class='admin-inline-form'>";
                    echo cd_csrf_input();
                    echo "<button type='submit' class='action-btn blue-btn' name='reset_faltas' value='" . (int)$ln['id'] . "'>Remover da Lista Negra</button>";
                    echo "</form>";
                    echo "</td>";
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
        <h3>Gest&atilde;o de Reservas (&uacute;ltimas 30)</h3>
        <div class="admin-search-bar">
            <input type="text" id="adminReservasSearchInput" placeholder="Procurar reserva (id, cliente, data, hora...)">
            <button type="button" class="btn" id="adminReservasSearchBtn">Procurar</button>
        </div>
        <div class="admin-filter-bar">
            <select id="adminReservasConfirmacaoFilter">
                <option value="">Confirma&ccedil;&atilde;o: Todas</option>
                <option value="confirmada">Confirmada</option>
                <option value="pendente">Pendente</option>
                <option value="recusada">Recusada</option>
            </select>
            <select id="adminReservasEstadoFilter">
                <option value="">Estado: Todos</option>
                <option value="pendente">Pendente</option>
                <option value="recusada">Recusada</option>
                <option value="compareceu">Compareceu</option>
                <option value="nao_compareceu">N&atilde;o compareceu</option>
                <option value="perdoado(reset)">Perdoado (reset)</option>
            </select>
            <select id="adminReservasCriacaoPeriodoFilter">
                <option value="">Criada em: Todos</option>
                <option value="hoje">Hoje</option>
                <option value="7">&Uacute;ltimos 7 dias</option>
                <option value="30">&Uacute;ltimos 30 dias</option>
                <option value="90">&Uacute;ltimos 90 dias</option>
            </select>
            <input type="date" id="adminReservasDataFromFilter" aria-label="Data reserva inicio">
            <input type="date" id="adminReservasDataToFilter" aria-label="Data reserva fim">
            <button type="button" class="btn admin-clear-btn" id="adminReservasClearBtn">Limpar</button>
        </div>
        <p class="admin-filter-help">
            Dica: os dois campos de data filtram a <strong>Data da Reserva</strong> (in&iacute;cio e fim).
            O filtro "Criada em" usa a data em que a reserva foi registada no sistema.
        </p>
        <div class="quick-date-buttons">
            <button type="button" class="btn quick-date-btn" id="adminReservasQuickHoje">Hoje</button>
            <button type="button" class="btn quick-date-btn" id="adminReservasQuick7">&Uacute;ltimos 7 dias</button>
            <button type="button" class="btn quick-date-btn" id="adminReservasQuick30">&Uacute;ltimos 30 dias</button>
            <button type="button" class="btn quick-date-btn" id="adminReservasExportCsvBtn">Exportar CSV</button>
        </div>

        <div class="admin-table-wrap">
        <table id="adminReservasTable" class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Data Reserva</th>
                    <th>Hora Reserva</th>
                    <th>Pessoas</th>
                    <th>Criada em</th>
                    <th>Confirma&ccedil;&atilde;o</th>
                    <th>Estado</th>
                    <th>A&ccedil;&atilde;o</th>
                </tr>
            </thead>
            <tbody>

            <?php
            // Buscar apenas as últimas 30 reservas com dados do cliente
            $reservasRows = cd_fetch_all(
                $con,
                "SELECT r.*, c.nome, c.email, c.telefone, r.criado_em AS criada_em_admin
                FROM reservas r
                JOIN Cliente c ON r.cliente_id = c.id
                ORDER BY r.criado_em DESC
                LIMIT 30"
            );

            foreach ($reservasRows as $r) {
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
                    $r['email'] . ' ' .
                    $r['telefone'] . ' ' .
                    $r['data_reserva'] . ' ' .
                    $r['hora_reserva'] . ' ' .
                    $r['numero_pessoas'] . ' ' .
                    cd_fmt_datetime($r['criada_em_admin'] ?? null) . ' ' .
                    $confLabel . ' ' .
                    $r['estado']
                ));
                if ($r['estado'] === 'compareceu') {
                    $estadoLabel = 'Compareceu';
                } elseif ($r['estado'] === 'recusada') {
                    $estadoLabel = 'Recusada';
                } elseif ($r['estado'] === 'nao_compareceu') {
                    $estadoLabel = 'N&atilde;o compareceu';
                } elseif ($r['estado'] === 'perdoado(reset)') {
                    $estadoLabel = 'Perdoado (reset)';
                } else {
                    $estadoLabel = 'Pendente';
                }
                echo "<tr 
                        data-admin-search='" . esc($searchReservaText) . "'
                        data-confirmacao='" . esc(strtolower($confLabel)) . "'
                        data-estado='" . esc(strtolower($r['estado'])) . "'
                        data-criada-em='" . esc(substr((string)$r['criada_em_admin'], 0, 10)) . "'
                        data-data-reserva='" . esc((string)$r['data_reserva']) . "'>";

                echo "<td>" . esc($r['id']) . "</td>";
                echo "<td>" . esc($r['nome']) . "</td>";
                $reservaEmail = (string)($r['email'] ?? '');
                echo "<td><span class='admin-email-text' title='" . esc($reservaEmail) . "'>" . esc($reservaEmail) . "</span></td>";
                echo "<td>" . esc($r['telefone']) . "</td>";
                echo "<td>" . esc($r['data_reserva']) . "</td>";
                echo "<td>" . esc($r['hora_reserva']) . "</td>";
                echo "<td>" . esc($r['numero_pessoas']) . "</td>";
                echo "<td>" . cd_fmt_datetime($r['criada_em_admin'] ?? null) . "</td>";

                // Confirmação
                $confClass = $r['confirmado'] == 1 ? 'ok' : ($r['confirmado'] == -1 ? 'bad' : 'warn');
                echo "<td><span class='status-chip $confClass'>" . esc($confLabel) . "</span></td>";

                // Estado
                $estadoClass = ($r['estado'] === 'compareceu')
                    ? 'ok'
                    : (($r['estado'] === 'nao_compareceu' || $r['estado'] === 'recusada') ? 'bad' : 'warn');
                echo "<td><span class='status-chip $estadoClass'>" . esc($estadoLabel) . "</span></td>";


                /* btts */
                echo "<td class='admin-actions-cell'>";

                $estadoAtual = $r['estado'];

                // Mostrar botões APENAS se:
                // - Reserva está confirmada
                // - Estado ainda é pendente
                if ($r['confirmado'] == 1 && $estadoAtual === 'pendente') {

                    echo "<form method='post' class='admin-inline-form'>";
                    echo cd_csrf_input();
                    echo "<input type='hidden' name='reserva' value='" . (int)$r['id'] . "'>";
                    echo "<button type='submit' class='action-btn green-btn' name='presenca' value='compareceu'>Compareceu</button>";
                    echo "</form>";

                    echo "<form method='post' class='admin-inline-form'>";
                    echo cd_csrf_input();
                    echo "<input type='hidden' name='reserva' value='" . (int)$r['id'] . "'>";
                    echo "<button type='submit' class='action-btn danger' name='presenca' value='nao_compareceu'>N&atilde;o Compareceu</button>";
                    echo "</form>";
                } else {

                    // Estado final -> Não mostrar botões
                    echo "<span class='status-chip neutral'>Sem a&ccedil;&otilde;es</span>";
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

        <section class="admin-section">
        <h3>Auditoria Admin (&uacute;ltimas 30)</h3>
        <div class="admin-table-wrap">
        <table id="adminAuditTable" class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data/Hora</th>
                    <th>Admin</th>
                    <th>A&ccedil;&atilde;o</th>
                    <th>Alvo</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $auditRows = cd_fetch_all(
                $con,
                "SELECT
                    l.id,
                    l.criado_em,
                    l.admin_id,
                    l.admin_nome,
                    l.acao,
                    l.alvo_tipo,
                    l.alvo_id,
                    l.detalhes,
                    COALESCE(c_direct.nome, c_reserva.nome) AS afetado_nome,
                    COALESCE(c_direct.email, c_reserva.email) AS afetado_email
                 FROM admin_audit_log l
                 LEFT JOIN Cliente c_direct
                    ON l.alvo_tipo = 'cliente' AND l.alvo_id = c_direct.id
                 LEFT JOIN reservas r_alvo
                    ON l.alvo_tipo = 'reserva' AND l.alvo_id = r_alvo.id
                 LEFT JOIN Cliente c_reserva
                    ON r_alvo.cliente_id = c_reserva.id
                 ORDER BY l.id DESC
                 LIMIT 30"
            );
            if (count($auditRows) === 0) {
                echo "<tr><td colspan='8'><span class='status-chip neutral'>Sem registos de auditoria</span></td></tr>";
            } else {
                foreach ($auditRows as $log) {
                    $adminLabel = (string)$log['admin_nome'] . ' (#' . (int)$log['admin_id'] . ')';
                    $alvo = $log['alvo_tipo'] . (isset($log['alvo_id']) && $log['alvo_id'] !== null ? ' #' . (int)$log['alvo_id'] : '');
                    $afetadoNome = trim((string)($log['afetado_nome'] ?? ''));
                    $afetadoEmail = trim((string)($log['afetado_email'] ?? ''));
                    echo "<tr>";
                    echo "<td>" . (int)$log['id'] . "</td>";
                    echo "<td>" . esc(cd_fmt_datetime($log['criado_em'] ?? null)) . "</td>";
                    echo "<td>" . esc($adminLabel) . "</td>";
                    echo "<td><span class='status-chip warn'>" . esc($log['acao']) . "</span></td>";
                    echo "<td>" . esc($alvo) . "</td>";
                    echo "<td>" . esc($afetadoNome !== '' ? $afetadoNome : '-') . "</td>";
                    if ($afetadoEmail !== '') {
                        echo "<td><span class='admin-email-text' title='" . esc($afetadoEmail) . "'>" . esc($afetadoEmail) . "</span></td>";
                    } else {
                        echo "<td>-</td>";
                    }
                    echo "<td>" . esc((string)($log['detalhes'] ?? '-')) . "</td>";
                    echo "</tr>";
                }
            }
            ?>
            </tbody>
        </table>
        </div>
        </section>


        <div class="botoesNav" id="navFim">
            <a href="index.php" id="btnInicio" class="btt-padrao-login">&larr; In&iacute;cio</a>
            <a href="dashboard.php" id="btnDashboard" class="btt-padrao-login">&larr; Dashboard</a>
            <a href="Bd/confirmar_reservas.php" id="btnConfirmarReservas" class="btt-padrao-login">&larr; Confirmar Reservas</a>
            <a href="admin_reservas.php" id="btnTodasReservas" class="btt-padrao-login">&larr; Todas as Reservas</a>
            <a href="admin_logs.php" id="btnLogs" class="btt-padrao-login">&larr; Logs</a>
            <a href="admin_mapa.php" id="btnMapaMesas" class="btt-padrao-login">&larr; Mapa de Mesas</a>
        </div>

    </div>
    <script src="Js/popup_alert.js"></script>
    <script src="Js/admin_search.js"></script>
</body>

</html>

