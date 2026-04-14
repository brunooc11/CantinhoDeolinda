<?php
session_start();

if (!isset($_SESSION['permissoes']) || $_SESSION['permissoes'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require("Bd/ligar.php");
require_once("Bd/popup_helper.php");
require_once("Bd/mesa_status_helper.php");
require_once(__DIR__ . "/theme.php");

function esc($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

cd_sync_mesa_states($con);

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
        cd_popup('Pedido inválido (CSRF). Atualiza a página e tenta novamente.', 'error', 'admin_reservas.php');
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

if (isset($_POST['presenca']) && isset($_POST['reserva'])) {
    cd_verify_csrf_or_fail();
    $idReserva = intval($_POST['reserva']);
    $estado = $_POST['presenca'] === 'compareceu' ? 'compareceu' : 'nao_compareceu';

    cd_execute($con, "UPDATE reservas SET estado = ? WHERE id = ?", "si", $estado, $idReserva);
    $estadoMesa = $estado === 'compareceu' ? 'ocupada' : 'livre';
    cd_execute(
        $con,
        "UPDATE mesas m
         JOIN reserva_mesas rm ON rm.mesa_id = m.id
         SET m.estado = ?
         WHERE rm.reserva_id = ?",
        "si",
        $estadoMesa,
        $idReserva
    );

    if ($estado === 'nao_compareceu') {
        $cli = cd_fetch_one($con, "SELECT cliente_id FROM reservas WHERE id = ?", "i", $idReserva);
        $clienteID = (int)($cli['cliente_id'] ?? 0);

        $faltasRow = cd_fetch_one(
            $con,
            "SELECT COUNT(*) AS faltas FROM reservas WHERE cliente_id = ? AND estado = 'nao_compareceu'",
            "i",
            $clienteID
        );
        $faltas = (int)($faltasRow['faltas'] ?? 0);

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

    header("Location: admin_reservas.php");
    exit();
}

if (isset($_POST['libertar_mesa']) && isset($_POST['reserva'])) {
    cd_verify_csrf_or_fail();
    $idReserva = intval($_POST['reserva']);

    mysqli_begin_transaction($con);
    try {
        cd_execute(
            $con,
            "UPDATE mesas m
             JOIN reserva_mesas rm ON rm.mesa_id = m.id
             SET m.estado = 'livre'
             WHERE rm.reserva_id = ?",
            "i",
            $idReserva
        );
        cd_execute($con, "DELETE FROM reserva_mesas WHERE reserva_id = ?", "i", $idReserva);
        mysqli_commit($con);
    } catch (Throwable $e) {
        mysqli_rollback($con);
    }

    cd_admin_audit($con, 'libertar_mesa', 'reserva', $idReserva, 'libertacao_manual=1');
    header("Location: admin_reservas.php");
    exit();
}

$reservasRows = cd_fetch_all(
    $con,
    "SELECT r.*, c.nome, c.email, c.telefone, r.criado_em AS criada_em_admin,
            (SELECT COUNT(*) FROM reserva_mesas rm WHERE rm.reserva_id = r.id) AS mesas_associadas
     FROM reservas r
     JOIN Cliente c ON r.cliente_id = c.id
     ORDER BY r.data_reserva DESC, r.hora_reserva DESC"
);

$totalReservas = (int)(cd_fetch_one($con, "SELECT COUNT(*) AS total FROM reservas")['total'] ?? 0);
$paginaAtual = 1;
$totalPaginas = 1;
$porPaginaLabel = 'Todas';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="Imagens/logo_atual.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todas as Reservas - Admin</title>
    <link rel="stylesheet" href="Css/admin.css?v=<?php echo filemtime(__DIR__ . '/Css/admin.css'); ?>">
    <link rel="stylesheet" href="Css/bttlogin.css">
    <?php cd_render_theme_head('', __DIR__); ?>
</head>
<body class="cdol-admin cdol-admin-home">
<?php cd_render_theme_toggle(''); ?>
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
        <a href="admin.php">
            <span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 11.2 12 4l8 7.2V20a1 1 0 0 1-1 1h-4.8v-5.5H9.8V21H5a1 1 0 0 1-1-1z"/></svg></span>
            <span class="admin-home-link-copy"><strong>Visão geral</strong><small>Painel principal</small></span>
        </a>
        <a href="Bd/confirmar_reservas.php">
            <span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 12.5 10.2 16 17 8.8"/><rect x="4" y="4" width="16" height="16" rx="4"/></svg></span>
            <span class="admin-home-link-copy"><strong>Confirmar reservas</strong><small>Entradas pendentes</small></span>
        </a>
        <a href="admin_reservas.php" class="is-active" aria-current="page">
            <span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="5" width="6" height="6" rx="1.5"/><rect x="14" y="5" width="6" height="6" rx="1.5"/><rect x="4" y="13" width="6" height="6" rx="1.5"/><rect x="14" y="13" width="6" height="6" rx="1.5"/></svg></span>
            <span class="admin-home-link-copy"><strong>Todas as reservas</strong><small>Lista completa</small></span>
        </a>
        <a href="admin_logs.php">
            <span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 7h10M7 12h10M7 17h10"/><rect x="4" y="4" width="16" height="16" rx="4"/></svg></span>
            <span class="admin-home-link-copy"><strong>Logs</strong><small>Atividade do sistema</small></span>
        </a>
        <a href="admin_mapa.php">
            <span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M8 6.5 4.5 8v10L8 16.5l4 1.5 3.5-1.5L19.5 18V8l-4 1.5L12 8 8 9.5z"/><path d="M8 6.5v10M12 8v10M15.5 9.5v10"/></svg></span>
            <span class="admin-home-link-copy"><strong>Mapa de mesas</strong><small>Disposição da sala</small></span>
        </a>
        <a href="admin_feedback.php">
            <span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17.5 4.5 20V7a2 2 0 0 1 2-2h11A2.5 2.5 0 0 1 20 7.5v7a2.5 2.5 0 0 1-2.5 2.5z"/><path d="M8 10h8M8 13h5"/></svg></span>
            <span class="admin-home-link-copy"><strong>Feedback</strong><small>Opiniões dos clientes</small></span>
        </a>
    </nav>
    <div class="admin-home-sidebar-footer">
        <a href="dashboard.php">
            <span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 19V9.5L12 5l7 4.5V19z"/><path d="M9 19v-5h6v5"/></svg></span>
            <span class="admin-home-link-copy"><strong>Dashboard</strong><small>Vista do utilizador</small></span>
        </a>
        <a href="index.php">
            <span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M10 7 5 12l5 5"/><path d="M6 12h9a4 4 0 1 0 0 8"/></svg></span>
            <span class="admin-home-link-copy"><strong>Voltar ao site</strong><small>Regressar à homepage</small></span>
        </a>
    </div>
</aside>
<div class="container">
    <div class="admin-hero">
        <div>
            <h2>Todas as Reservas</h2>
            <p>Histórico completo das reservas do sistema.</p>
        </div>
        <div class="admin-kpis">
            <div class="admin-kpi-card">
                <span>Total de registos</span>
                <strong><?php echo $totalReservas; ?></strong>
            </div>
            <div class="admin-kpi-card">
                <span>Página</span>
                <strong><?php echo $paginaAtual; ?>/<?php echo $totalPaginas; ?></strong>
            </div>
            <div class="admin-kpi-card">
                <span>Por Página</span>
                <strong><?php echo esc($porPaginaLabel); ?></strong>
            </div>
        </div>
    </div>

    <section class="admin-section">
        <h3>Gestão Completa de Reservas</h3>
        

        <div class="admin-search-bar">
            <input type="text" id="adminReservasSearchInput" placeholder="Procurar reserva (id, cliente, data, hora...)">
            <button type="button" class="btn" id="adminReservasSearchBtn">Procurar</button>
        </div>
        <div class="admin-filter-bar">
            <select id="adminReservasConfirmacaoFilter">
                <option value="">Confirmação: Todas</option>
                <option value="confirmada">Confirmada</option>
                <option value="pendente">Pendente</option>
                <option value="recusada">Recusada</option>
            </select>
            <select id="adminReservasEstadoFilter">
                <option value="">Estado: Todos</option>
                <option value="pendente">Pendente</option>
                <option value="recusada">Recusada</option>
                <option value="compareceu">Compareceu</option>
                <option value="nao_compareceu">Não compareceu</option>
                <option value="perdoado(reset)">Perdoado (reset)</option>
            </select>
            <select id="adminReservasCriacaoPeriodoFilter">
                <option value="">Criada em: Todos</option>
                <option value="hoje">Hoje</option>
                <option value="7">Últimos 7 dias</option>
                <option value="30">Últimos 30 dias</option>
                <option value="90">Últimos 90 dias</option>
            </select>
            <input type="date" id="adminReservasDataFromFilter" aria-label="Data reserva inicio">
            <input type="date" id="adminReservasDataToFilter" aria-label="Data reserva fim">
            <button type="button" class="btn admin-clear-btn" id="adminReservasClearBtn">Limpar</button>
        </div>

        <p class="admin-filter-help">
            Dica: os dois campos de data filtram a <strong>Data da Reserva</strong> (início e fim).
            O filtro "Criada em" usa a data em que a reserva foi registada no sistema.
        </p>

        <div class="quick-date-buttons">
            <button type="button" class="btn quick-date-btn" id="adminReservasQuickHoje">Hoje</button>
            <button type="button" class="btn quick-date-btn" id="adminReservasQuick7">Últimos 7 dias</button>
            <button type="button" class="btn quick-date-btn" id="adminReservasQuick30">Últimos 30 dias</button>
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
                    <th>Confirmação</th>
                    <th>Estado</th>
                    <th>Ação</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reservasRows as $r): ?>
                    <?php
                    if ($r['confirmado'] == 1) {
                        $confLabel = 'Confirmada';
                    } elseif ($r['confirmado'] == -1) {
                        $confLabel = 'Recusada';
                    } else {
                        $confLabel = 'Pendente';
                    }

                    if ($r['estado'] === 'compareceu') {
                        $estadoLabel = 'Compareceu';
                    } elseif ($r['estado'] === 'recusada') {
                        $estadoLabel = 'Recusada';
                    } elseif ($r['estado'] === 'nao_compareceu') {
                        $estadoLabel = 'Não compareceu';
                    } elseif ($r['estado'] === 'perdoado(reset)') {
                        $estadoLabel = 'Perdoado (reset)';
                    } else {
                        $estadoLabel = 'Pendente';
                    }

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
                    ?>
                    <tr
                        data-admin-search="<?php echo esc($searchReservaText); ?>"
                        data-confirmacao="<?php echo esc(strtolower($confLabel)); ?>"
                        data-estado="<?php echo esc(strtolower($r['estado'])); ?>"
                        data-criada-em="<?php echo esc(substr((string)$r['criada_em_admin'], 0, 10)); ?>"
                        data-data-reserva="<?php echo esc((string)$r['data_reserva']); ?>"
                    >
                        <td><?php echo esc($r['id']); ?></td>
                        <td><?php echo esc($r['nome']); ?></td>
                        <td><span class="admin-email-text" title="<?php echo esc((string)($r['email'] ?? '')); ?>"><?php echo esc((string)($r['email'] ?? '')); ?></span></td>
                        <td><?php echo esc($r['telefone']); ?></td>
                        <td><?php echo esc($r['data_reserva']); ?></td>
                        <td><?php echo esc($r['hora_reserva']); ?></td>
                        <td><?php echo esc($r['numero_pessoas']); ?></td>
                        <td><?php echo cd_fmt_datetime($r['criada_em_admin'] ?? null); ?></td>
                        <?php $confClass = $r['confirmado'] == 1 ? 'ok' : ($r['confirmado'] == -1 ? 'bad' : 'warn'); ?>
                        <td><span class="status-chip <?php echo $confClass; ?>"><?php echo esc($confLabel); ?></span></td>
                        <?php $estadoClass = ($r['estado'] === 'compareceu') ? 'ok' : (($r['estado'] === 'nao_compareceu' || $r['estado'] === 'recusada') ? 'bad' : 'warn'); ?>
                        <td><span class="status-chip <?php echo $estadoClass; ?>"><?php echo esc($estadoLabel); ?></span></td>
                        <td class="admin-actions-cell">
                            <?php if ($r['confirmado'] == 1 && $r['estado'] === 'pendente'): ?>
                                <form method="post" class="admin-inline-form">
                                    <?php echo cd_csrf_input(); ?>
                                    <input type="hidden" name="reserva" value="<?php echo (int)$r['id']; ?>">
                                    <button type="submit" class="action-btn green-btn" name="presenca" value="compareceu">Compareceu</button>
                                </form>
                                <form method="post" class="admin-inline-form">
                                    <?php echo cd_csrf_input(); ?>
                                    <input type="hidden" name="reserva" value="<?php echo (int)$r['id']; ?>">
                                    <button type="submit" class="action-btn danger" name="presenca" value="nao_compareceu">Não compareceu</button>
                                </form>
                            <?php else: ?>
                                <span class="status-chip neutral">Sem ações</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p id="adminReservasSearchEmpty" class="admin-search-empty">Sem resultados para reservas.</p>
    </section>
</div>
<script src="Js/popup_alert.js"></script>
<script src="Js/admin_search.js"></script>
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
<?php cd_render_theme_script('', __DIR__); ?>
</body>
</html>


