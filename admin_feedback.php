<?php
session_start();

if (!isset($_SESSION['permissoes']) || $_SESSION['permissoes'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require("Bd/ligar.php");
require_once("Bd/popup_helper.php");
require_once("Bd/feedback_helper.php");

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
        cd_popup('Pedido inválido (CSRF). Atualiza a página e tenta novamente.', 'error', 'admin_feedback.php');
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

function cd_fmt_datetime_precise($value)
{
    $raw = trim((string)$value);
    if ($raw === '' || $raw === '0000-00-00 00:00:00' || $raw === '0000-00-00') {
        return '-';
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return esc($raw);
    }

    return date('d/m/Y H:i:s', $ts);
}

function cd_feedback_relative_time($value)
{
    $raw = trim((string)$value);
    if ($raw === '' || $raw === '0000-00-00 00:00:00' || $raw === '0000-00-00') {
        return '-';
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return '-';
    }

    $diff = time() - $ts;
    if ($diff < 60) {
        return 'agora mesmo';
    }

    $units = [
        86400 => 'dia',
        3600 => 'hora',
        60 => 'minuto'
    ];

    foreach ($units as $seconds => $label) {
        if ($diff >= $seconds) {
            $count = (int)floor($diff / $seconds);
            return 'há ' . $count . ' ' . $label . ($count === 1 ? '' : 's');
        }
    }

    return '-';
}

function cd_feedback_age_class($value)
{
    $ts = strtotime((string)$value);
    if ($ts === false) {
        return '';
    }

    $days = (time() - $ts) / 86400;
    if ($days >= 7) {
        return 'is-critical';
    }
    if ($days >= 2) {
        return 'is-warn';
    }
    return 'is-fresh';
}

function cd_feedback_audit_label($acao, $detalhes)
{
    $detalhes = (string)$detalhes;
    if ($acao === 'feedback_nota') {
        return 'Nota interna atualizada';
    }
    if ($acao === 'feedback_estado') {
        if (strpos($detalhes, 'estado=lido') !== false) {
            return 'Marcado como lido';
        }
        if (strpos($detalhes, 'estado=respondido') !== false) {
            return 'Marcado como respondido';
        }
        if (strpos($detalhes, 'estado=arquivado') !== false) {
            return 'Arquivado';
        }
    }
    return 'Ação administrativa';
}

function cd_feedback_status_label($status)
{
    $map = [
        'novo' => 'Novo',
        'lido' => 'Lido',
        'respondido' => 'Respondido',
        'arquivado' => 'Arquivado'
    ];

    return $map[$status] ?? 'Novo';
}

function cd_feedback_status_class($status)
{
    $map = [
        'novo' => 'warn',
        'lido' => 'neutral',
        'respondido' => 'ok',
        'arquivado' => 'bad'
    ];

    return $map[$status] ?? 'neutral';
}

function cd_feedback_subject_class($subject)
{
    $subject = strtolower(trim((string)$subject));
    if (strpos($subject, 'reclam') === 0) {
        return 'subject-badge danger';
    }
    if (strpos($subject, 'sugest') === 0) {
        return 'subject-badge idea';
    }
    if ($subject === 'reclamacao' || $subject === 'reclamação') {
        return 'subject-badge danger';
    }
    if ($subject === 'sugestao' || $subject === 'sugestão') {
        return 'subject-badge idea';
    }
    if ($subject === 'atendimento') {
        return 'subject-badge service';
    }
    if ($subject === 'qualidade da comida') {
        return 'subject-badge food';
    }
    return 'subject-badge';
}

function cd_feedback_excerpt($text, $length = 110)
{
    $text = trim(preg_replace('/\s+/', ' ', (string)$text));
    if ($text === '') {
        return '-';
    }
    if (strlen($text) <= $length) {
        return $text;
    }
    return rtrim(substr($text, 0, $length - 1)) . '...';
}

function cd_qs(array $overrides = [])
{
    $base = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($base[$k]);
        } else {
            $base[$k] = $v;
        }
    }
    return http_build_query($base);
}

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

$feedbackPk = cd_feedback_ensure_schema($con);
if (!preg_match('/^[A-Za-z0-9_]+$/', $feedbackPk)) {
    $feedbackPk = 'id';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_action'], $_POST['feedback_id'])) {
    cd_verify_csrf_or_fail();

    $feedbackId = (int)$_POST['feedback_id'];
    $action = trim((string)$_POST['feedback_action']);
    $adminNota = trim((string)($_POST['admin_nota'] ?? ''));
    $redirectOpen = $feedbackId > 0 ? $feedbackId : null;

    if ($feedbackId > 0) {
        if ($action === 'save_note') {
            cd_execute(
                $con,
                "UPDATE contactos
                 SET admin_nota = ?,
                     estado = CASE WHEN estado = 'novo' THEN 'lido' ELSE estado END,
                     lido_em = COALESCE(lido_em, NOW())
                 WHERE `$feedbackPk` = ?",
                "si",
                $adminNota,
                $feedbackId
            );
            cd_admin_audit($con, 'feedback_nota', 'feedback', $feedbackId, 'nota_atualizada=1;auto_lido=1');
        } elseif ($action === 'mark_lido') {
            cd_execute(
                $con,
                "UPDATE contactos
                 SET estado = 'lido',
                     admin_nota = ?,
                     lido_em = COALESCE(lido_em, NOW())
                 WHERE `$feedbackPk` = ?",
                "si",
                $adminNota,
                $feedbackId
            );
            cd_admin_audit($con, 'feedback_estado', 'feedback', $feedbackId, 'estado=lido');
        } elseif ($action === 'mark_respondido') {
            cd_execute(
                $con,
                "UPDATE contactos
                 SET estado = 'respondido',
                     admin_nota = ?,
                     lido_em = COALESCE(lido_em, NOW()),
                     respondido_em = COALESCE(respondido_em, NOW())
                 WHERE `$feedbackPk` = ?",
                "si",
                $adminNota,
                $feedbackId
            );
            cd_admin_audit($con, 'feedback_estado', 'feedback', $feedbackId, 'estado=respondido');
        } elseif ($action === 'mark_arquivado') {
            cd_execute(
                $con,
                "UPDATE contactos
                 SET estado = 'arquivado',
                     admin_nota = ?,
                     lido_em = COALESCE(lido_em, NOW()),
                     arquivado_em = COALESCE(arquivado_em, NOW())
                 WHERE `$feedbackPk` = ?",
                "si",
                $adminNota,
                $feedbackId
            );
            cd_admin_audit($con, 'feedback_estado', 'feedback', $feedbackId, 'estado=arquivado');
        }
    }

    $redirectQs = cd_qs(['open' => $redirectOpen]);
    header('Location: admin_feedback.php' . ($redirectQs !== '' ? ('?' . $redirectQs) : ''));
    exit();
}

$q = trim((string)($_GET['q'] ?? ''));
$estado = trim((string)($_GET['estado'] ?? ''));
$assunto = trim((string)($_GET['assunto'] ?? ''));
$dataFrom = trim((string)($_GET['data_from'] ?? ''));
$dataTo = trim((string)($_GET['data_to'] ?? ''));
$searchIn = trim((string)($_GET['search_in'] ?? 'general'));
$sort = trim((string)($_GET['sort'] ?? 'priority'));
$quickFilter = trim((string)($_GET['quick'] ?? ''));
$perPage = (int)($_GET['per_page'] ?? 10);
$page = max(1, (int)($_GET['page'] ?? 1));
$openId = max(0, (int)($_GET['open'] ?? 0));

$allowedStatuses = ['novo', 'lido', 'respondido', 'arquivado'];
$allowedPerPage = [10, 25, 50, 100];
$allowedSearchIn = ['general', 'email'];
$allowedSort = ['priority', 'newest', 'oldest'];
$allowedQuickFilters = ['novos', 'nao_tratados', 'reclamacoes', 'ultimos_7_dias'];
if (!in_array($estado, $allowedStatuses, true)) {
    $estado = '';
}
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 25;
}
if (!in_array($searchIn, $allowedSearchIn, true)) {
    $searchIn = 'general';
}
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'priority';
}
if (!in_array($quickFilter, $allowedQuickFilters, true)) {
    $quickFilter = '';
}

$where = [];
$types = '';
$params = [];

if ($q !== '') {
    $like = '%' . $q . '%';
    if ($searchIn === 'email') {
        $where[] = "email LIKE ?";
        $types .= 's';
        $params[] = $like;
    } else {
        $where[] = "(nome LIKE ? OR email LIKE ? OR assunto LIKE ? OR mensagem LIKE ? OR COALESCE(admin_nota, '') LIKE ?)";
        $types .= 'sssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
}

if ($estado !== '') {
    $where[] = "estado = ?";
    $types .= 's';
    $params[] = $estado;
}

if ($assunto !== '') {
    $where[] = "assunto = ?";
    $types .= 's';
    $params[] = $assunto;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFrom)) {
    $where[] = "DATE(criado_em) >= ?";
    $types .= 's';
    $params[] = $dataFrom;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataTo)) {
    $where[] = "DATE(criado_em) <= ?";
    $types .= 's';
    $params[] = $dataTo;
}

if ($quickFilter === 'novos') {
    $where[] = "estado = 'novo'";
} elseif ($quickFilter === 'nao_tratados') {
    $where[] = "estado IN ('novo', 'lido')";
} elseif ($quickFilter === 'reclamacoes') {
    $where[] = "LOWER(assunto) LIKE 'reclam%'";
} elseif ($quickFilter === 'ultimos_7_dias') {
    $where[] = "DATE(criado_em) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
}

$whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

$orderSql = "CASE estado
        WHEN 'novo' THEN 0
        WHEN 'lido' THEN 1
        WHEN 'respondido' THEN 2
        WHEN 'arquivado' THEN 3
        ELSE 4
    END,
    criado_em DESC";

if ($sort === 'newest') {
    $orderSql = "criado_em DESC";
} elseif ($sort === 'oldest') {
    $orderSql = "criado_em ASC";
}

$countRow = cd_fetch_one(
    $con,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN estado = 'novo' THEN 1 ELSE 0 END) AS novos,
        SUM(CASE WHEN estado IN ('lido', 'respondido', 'arquivado') OR lido_em IS NOT NULL THEN 1 ELSE 0 END) AS lidos,
        SUM(CASE WHEN estado IN ('novo', 'lido') THEN 1 ELSE 0 END) AS nao_tratados,
        SUM(CASE WHEN estado = 'respondido' OR respondido_em IS NOT NULL THEN 1 ELSE 0 END) AS respondidos,
        SUM(CASE WHEN LOWER(assunto) LIKE 'reclam%' THEN 1 ELSE 0 END) AS reclamacoes,
        SUM(CASE WHEN LOWER(assunto) LIKE 'sugest%' THEN 1 ELSE 0 END) AS sugestoes
     FROM contactos"
);

$assuntos = cd_fetch_all($con, "SELECT DISTINCT assunto FROM contactos WHERE assunto <> '' ORDER BY assunto ASC");
$total = (int)(cd_fetch_one($con, "SELECT COUNT(*) AS total FROM contactos $whereSql", $types, ...$params)['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$rows = cd_fetch_all(
    $con,
    "SELECT
        `$feedbackPk` AS feedback_id,
        nome,
        email,
        assunto,
        mensagem,
        estado,
        admin_nota,
        criado_em,
        lido_em,
        respondido_em,
        arquivado_em
     FROM contactos
     $whereSql
     ORDER BY $orderSql
     LIMIT $perPage OFFSET $offset",
    $types,
    ...$params
);

$selected = null;
if ($openId > 0) {
    $selected = cd_fetch_one(
        $con,
        "SELECT
            `$feedbackPk` AS feedback_id,
            nome,
            email,
            assunto,
            mensagem,
            estado,
            admin_nota,
            criado_em,
            lido_em,
            respondido_em,
            arquivado_em
         FROM contactos
         WHERE `$feedbackPk` = ?",
        "i",
        $openId
    );
}

if ($selected === null && count($rows) > 0) {
    $selected = cd_fetch_one(
        $con,
        "SELECT
            `$feedbackPk` AS feedback_id,
            nome,
            email,
            assunto,
            mensagem,
            estado,
            admin_nota,
            criado_em,
            lido_em,
            respondido_em,
            arquivado_em
         FROM contactos
         WHERE `$feedbackPk` = ?",
        "i",
        (int)$rows[0]['feedback_id']
    );
}

$selectedAuditRows = [];
if ($selected !== null) {
    $selectedAuditRows = cd_fetch_all(
        $con,
        "SELECT admin_nome, acao, detalhes, criado_em
         FROM admin_audit_log
         WHERE alvo_tipo = 'feedback' AND alvo_id = ?
         ORDER BY criado_em DESC, id DESC
         LIMIT 8",
        "i",
        (int)$selected['feedback_id']
    );
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="Imagens/logo_atual.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Admin</title>
    <link rel="stylesheet" href="Css/admin.css?v=<?php echo filemtime(__DIR__ . '/Css/admin.css'); ?>">
    <link rel="stylesheet" href="Css/admin_feedback.css?v=<?php echo filemtime(__DIR__ . '/Css/admin_feedback.css'); ?>">
    <link rel="stylesheet" href="Css/bttlogin.css">
    <style>
        .cdol-feedback .feedback-workspace {
            align-items: start;
        }

        .cdol-feedback .feedback-inbox-panel {
            display: flex;
            flex-direction: column;
        }

        .cdol-feedback .feedback-inbox-list {
            flex: 0 0 auto;
        }

        .cdol-feedback .feedback-pagination {
            margin-top: auto;
        }

        .cdol-feedback .feedback-inbox-panel[data-inbox-mode="compact"] .feedback-inbox-list {
            flex: 0 0 auto;
            overflow: visible;
            max-height: none;
        }

        .cdol-feedback .feedback-inbox-panel[data-inbox-mode="scroll"] .feedback-inbox-list {
            flex: 1 1 auto;
            overflow: auto;
        }
    </style>
</head>
<body class="cdol-admin cdol-admin-home cdol-feedback">
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
            <a href="admin_feedback.php" class="is-active" aria-current="page"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17.5 4.5 20V7a2 2 0 0 1 2-2h11A2.5 2.5 0 0 1 20 7.5v7a2.5 2.5 0 0 1-2.5 2.5z"/><path d="M8 10h8M8 13h5"/></svg></span><span class="admin-home-link-copy"><strong>Feedback</strong><small>Opiniões dos clientes</small></span></a>
        </nav>
        <div class="admin-home-sidebar-footer">
            <a href="dashboard.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 19V9.5L12 5l7 4.5V19z"/><path d="M9 19v-5h6v5"/></svg></span><span class="admin-home-link-copy"><strong>Dashboard</strong><small>Vista do utilizador</small></span></a>
            <a href="index.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M10 7 5 12l5 5"/><path d="M6 12h9a4 4 0 1 0 0 8"/></svg></span><span class="admin-home-link-copy"><strong>Voltar ao site</strong><small>Regressar à homepage</small></span></a>
        </div>
    </aside>
    <div class="container">
        <div class="admin-hero feedback-hero">
            <div>
                <span class="feedback-eyebrow">Centro de Feedback</span>
                <h2>Feedback de Clientes</h2>
                <p>Área dedicada para tratar sugestões, reclamações e mensagens sem depender do email.</p>
            </div>
            <div class="admin-kpis">
                <div class="admin-kpi-card">
                    <span>Total de feedback</span>
                    <strong><?php echo (int)($countRow['total'] ?? 0); ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Novos</span>
                    <strong><?php echo (int)($countRow['novos'] ?? 0); ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Lidos</span>
                    <strong><?php echo (int)($countRow['lidos'] ?? 0); ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Respondidos</span>
                    <strong><?php echo (int)($countRow['respondidos'] ?? 0); ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Reclamações</span>
                    <strong><?php echo (int)($countRow['reclamacoes'] ?? 0); ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Sugestões</span>
                    <strong><?php echo (int)($countRow['sugestoes'] ?? 0); ?></strong>
                </div>
            </div>
        </div>

        <section class="admin-section feedback-filter-panel">
            <h3>Filtros</h3>
            <p class="feedback-subtle">Pesquisa por remetente, email, assunto, mensagem ou nota interna.</p>
            <div class="feedback-quick-filters">
                <a class="feedback-quick-pill <?php echo $quickFilter === 'novos' ? 'is-active' : ''; ?>" href="admin_feedback.php?<?php echo esc(cd_qs(['quick' => 'novos', 'page' => 1, 'open' => null])); ?>">Novos</a>
                <a class="feedback-quick-pill <?php echo $quickFilter === 'nao_tratados' ? 'is-active' : ''; ?>" href="admin_feedback.php?<?php echo esc(cd_qs(['quick' => 'nao_tratados', 'page' => 1, 'open' => null])); ?>">Não tratados</a>
                <a class="feedback-quick-pill <?php echo $quickFilter === 'reclamacoes' ? 'is-active' : ''; ?>" href="admin_feedback.php?<?php echo esc(cd_qs(['quick' => 'reclamacoes', 'page' => 1, 'open' => null])); ?>">Reclamações</a>
                <a class="feedback-quick-pill <?php echo $quickFilter === 'ultimos_7_dias' ? 'is-active' : ''; ?>" href="admin_feedback.php?<?php echo esc(cd_qs(['quick' => 'ultimos_7_dias', 'page' => 1, 'open' => null])); ?>">Últimos 7 dias</a>
            </div>
            <form method="get" class="admin-filter-bar feedback-filters-grid">
                <input type="text" name="q" value="<?php echo esc($q); ?>" placeholder="Pesquisar feedback">

                <select name="search_in">
                    <option value="general" <?php echo $searchIn === 'general' ? 'selected' : ''; ?>>Pesquisa geral</option>
                    <option value="email" <?php echo $searchIn === 'email' ? 'selected' : ''; ?>>Só por email</option>
                </select>

                <select name="estado">
                    <option value="">Estado: Todos</option>
                    <?php foreach ($allowedStatuses as $allowedStatus): ?>
                        <option value="<?php echo esc($allowedStatus); ?>" <?php echo $estado === $allowedStatus ? 'selected' : ''; ?>>
                            <?php echo esc(cd_feedback_status_label($allowedStatus)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="assunto">
                    <option value="">Assunto: Todos</option>
                    <?php foreach ($assuntos as $assuntoRow): ?>
                        <?php $assuntoValue = trim((string)($assuntoRow['assunto'] ?? '')); ?>
                        <?php if ($assuntoValue === '') { continue; } ?>
                        <option value="<?php echo esc($assuntoValue); ?>" <?php echo $assunto === $assuntoValue ? 'selected' : ''; ?>>
                            <?php echo esc($assuntoValue); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="data_from" value="<?php echo esc($dataFrom); ?>" aria-label="Data inicial">
                <input type="date" name="data_to" value="<?php echo esc($dataTo); ?>" aria-label="Data final">

                <select name="sort">
                    <option value="priority" <?php echo $sort === 'priority' ? 'selected' : ''; ?>>Ordenar: Prioridade</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mais recente</option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Mais antigo</option>
                </select>

                <select name="per_page">
                    <?php foreach ($allowedPerPage as $n): ?>
                        <option value="<?php echo $n; ?>" <?php echo $perPage === $n ? 'selected' : ''; ?>>
                            <?php echo $n; ?>/página
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="feedback-filter-actions">
                    <button type="submit" class="btn">Aplicar filtros</button>
                    <a class="admin-clear-btn" href="admin_feedback.php">Limpar</a>
                </div>
            </form>
        </section>

        <section class="feedback-workspace">
            <aside class="feedback-inbox-panel">
                <div class="feedback-panel-head">
                    <div>
                        <h3>Caixa de Entrada</h3>
                        <p><?php echo (int)$total; ?> feedback(s) encontrado(s)</p>
                    </div>
                    <div class="feedback-mini-stats">
                        <span class="feedback-mini-pill warn"><?php echo (int)($countRow['novos'] ?? 0); ?> novos</span>
                        <span class="feedback-mini-pill alert"><?php echo (int)($countRow['nao_tratados'] ?? 0); ?> não tratados</span>
                        <?php if ((int)($countRow['reclamacoes'] ?? 0) > 0): ?>
                            <span class="feedback-mini-pill"><?php echo (int)($countRow['reclamacoes'] ?? 0); ?> recl.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="feedback-inbox-list">
                    <?php if (count($rows) === 0): ?>
                        <div class="feedback-empty-state">
                            <span class="status-chip neutral">Sem feedback para os filtros selecionados</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <a class="feedback-list-card <?php echo ((int)$row['feedback_id'] === (int)($selected['feedback_id'] ?? 0)) ? 'is-active' : ''; ?> feedback-card-<?php echo esc((string)($row['estado'] ?? 'novo')); ?>" href="admin_feedback.php?<?php echo esc(cd_qs(['open' => (int)$row['feedback_id'], 'page' => $page])); ?>">
                                <div class="feedback-list-top">
                                    <span class="<?php echo esc(cd_feedback_subject_class($row['assunto'] ?? '')); ?>">
                                        <?php echo esc((string)($row['assunto'] ?? 'Sem assunto')); ?>
                                    </span>
                                    <span class="feedback-status-group">
                                        <?php if (!empty($row['lido_em']) || in_array((string)($row['estado'] ?? ''), ['lido', 'respondido', 'arquivado'], true)): ?>
                                            <span class="status-chip neutral">Lido</span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['respondido_em']) && (string)($row['estado'] ?? '') !== 'respondido'): ?>
                                            <span class="status-chip ok">Respondido</span>
                                        <?php endif; ?>
                                        <span class="status-chip <?php echo esc(cd_feedback_status_class((string)($row['estado'] ?? 'novo'))); ?>">
                                            <?php echo esc(cd_feedback_status_label((string)($row['estado'] ?? 'novo'))); ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="feedback-list-name"><?php echo esc((string)($row['nome'] ?? '-')); ?></div>
                                <div class="feedback-list-email"><?php echo esc((string)($row['email'] ?? '-')); ?></div>
                                <div class="feedback-list-meta">
                                    <span>#<?php echo (int)$row['feedback_id']; ?></span>
                                    <span class="feedback-age-pill <?php echo esc(cd_feedback_age_class($row['criado_em'] ?? null)); ?>"><?php echo esc(cd_feedback_relative_time($row['criado_em'] ?? null)); ?></span>
                                    <span><?php echo esc(cd_fmt_datetime($row['criado_em'] ?? null)); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="admin-pagination feedback-pagination">
                    <a class="btn" href="admin_feedback.php?<?php echo esc(cd_qs(['page' => max(1, $page - 1)])); ?>" <?php echo $page <= 1 ? 'aria-disabled="true"' : ''; ?>>
                        Página anterior
                    </a>
                    <span>Página <?php echo (int)$page; ?> de <?php echo (int)$totalPages; ?></span>
                    <a class="btn" href="admin_feedback.php?<?php echo esc(cd_qs(['page' => min($totalPages, $page + 1)])); ?>" <?php echo $page >= $totalPages ? 'aria-disabled="true"' : ''; ?>>
                        Página seguinte
                    </a>
                </div>
            </aside>

            <section class="feedback-reader-panel">
                <div class="feedback-panel-head">
                    <div>
                        <h3>Leitura</h3>
                        <p>Detalhe completo e tratamento interno do feedback selecionado.</p>
                    </div>
                </div>

                <?php if ($selected === null): ?>
                    <div class="feedback-empty-state">
                        <span class="status-chip neutral">Sem feedback para mostrar</span>
                    </div>
                <?php else: ?>
                    <div class="feedback-reader-card">
                        <div class="feedback-reader-hero">
                            <div>
                                <div class="feedback-reader-badges">
                                    <span class="<?php echo esc(cd_feedback_subject_class($selected['assunto'] ?? '')); ?>">
                                        <?php echo esc((string)($selected['assunto'] ?? 'Sem assunto')); ?>
                                    </span>
                                    <?php if (!empty($selected['respondido_em']) && (string)($selected['estado'] ?? '') !== 'respondido'): ?>
                                        <span class="status-chip ok">Respondido anteriormente</span>
                                    <?php endif; ?>
                                    <span class="status-chip <?php echo esc(cd_feedback_status_class((string)($selected['estado'] ?? 'novo'))); ?>">
                                        <?php echo esc(cd_feedback_status_label((string)($selected['estado'] ?? 'novo'))); ?>
                                    </span>
                                </div>
                                <h3 class="feedback-reader-title"><?php echo esc((string)($selected['nome'] ?? '-')); ?></h3>
                                <div class="feedback-contact-actions">
                                    <a class="feedback-contact-line" href="#" data-copy-email="<?php echo esc((string)($selected['email'] ?? '')); ?>" title="Clique para copiar o email">
                                        <?php echo esc((string)($selected['email'] ?? '-')); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="feedback-reader-id">Feedback #<?php echo (int)$selected['feedback_id']; ?></div>
                        </div>

                        <div class="feedback-meta-grid">
                            <article class="feedback-meta-card">
                                <span>Recebido</span>
                                <strong><?php echo esc(cd_fmt_datetime($selected['criado_em'] ?? null)); ?></strong>
                            </article>
                            <article class="feedback-meta-card">
                                <span>Lido</span>
                                <strong><?php echo esc(cd_fmt_datetime($selected['lido_em'] ?? null)); ?></strong>
                            </article>
                            <article class="feedback-meta-card">
                                <span>Respondido</span>
                                <strong><?php echo esc(cd_fmt_datetime($selected['respondido_em'] ?? null)); ?></strong>
                            </article>
                            <article class="feedback-meta-card">
                                <span>Arquivado</span>
                                <strong><?php echo esc(cd_fmt_datetime($selected['arquivado_em'] ?? null)); ?></strong>
                            </article>
                            <article class="feedback-meta-card">
                                <span>Tempo em espera</span>
                                <strong class="feedback-age-pill <?php echo esc(cd_feedback_age_class($selected['criado_em'] ?? null)); ?>"><?php echo esc(cd_feedback_relative_time($selected['criado_em'] ?? null)); ?></strong>
                            </article>
                        </div>

                        <div class="feedback-reader-body">
                            <div class="feedback-message-box">
                                <div class="feedback-message-title">Mensagem recebida</div>
                                <?php echo nl2br(esc((string)($selected['mensagem'] ?? ''))); ?>
                            </div>

                            <form method="post" class="feedback-note-form">
                                <?php echo cd_csrf_input(); ?>
                                <input type="hidden" name="feedback_id" value="<?php echo (int)$selected['feedback_id']; ?>">

                                <label for="feedbackAdminNota">Nota interna</label>
                                <textarea id="feedbackAdminNota" name="admin_nota" placeholder="Regista contexto, acompanhamento ou resposta interna..."><?php echo esc((string)($selected['admin_nota'] ?? '')); ?></textarea>

                                <div class="feedback-actions-grid">
                                    <button type="submit" class="action-btn blue-btn" name="feedback_action" value="save_note">Guardar nota</button>
                                    <button type="submit" class="action-btn" name="feedback_action" value="mark_lido">Marcar lido</button>
                                    <button type="submit" class="action-btn green-btn" name="feedback_action" value="mark_respondido">Marcar respondido</button>
                                    <button type="submit" class="action-btn danger" name="feedback_action" value="mark_arquivado">Arquivar</button>
                                </div>
                            </form>

                            <section class="feedback-history-box is-collapsed" data-collapsible>
                                <div class="feedback-history-head">
                                    <div class="feedback-message-title">Histórico administrativo</div>
                                    <button type="button" class="feedback-collapse-btn" data-collapse-toggle aria-expanded="false">+</button>
                                </div>
                                <div class="feedback-history-content" data-collapse-content>
                                    <?php if (count($selectedAuditRows) === 0): ?>
                                        <div class="feedback-history-empty">Ainda não existem ações registadas para este feedback.</div>
                                    <?php else: ?>
                                        <div class="feedback-history-list">
                                            <?php foreach ($selectedAuditRows as $auditRow): ?>
                                                <article class="feedback-history-item">
                                                    <div class="feedback-history-top">
                                                        <strong><?php echo esc(cd_feedback_audit_label((string)($auditRow['acao'] ?? ''), (string)($auditRow['detalhes'] ?? ''))); ?></strong>
                                                        <span><?php echo esc(cd_fmt_datetime_precise($auditRow['criado_em'] ?? null)); ?></span>
                                                    </div>
                                                    <div class="feedback-history-meta">
                                                        <span>por <?php echo esc((string)($auditRow['admin_nome'] ?? 'admin')); ?></span>
                                                        <span><?php echo esc(cd_feedback_relative_time($auditRow['criado_em'] ?? null)); ?></span>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </section>

    </div>
        </div>
    </div>

    <script src="Js/popup_alert.js"></script>
    <script>
        (function () {
            var body = document.body;
            var toggle = document.querySelector('.admin-home-menu-toggle');
            var overlay = document.querySelector('.admin-home-menu-overlay');
            var sidebar = document.querySelector('.admin-home-sidebar');
            if (toggle && overlay && sidebar) {
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
            }
        })();

        document.querySelectorAll('[data-copy-email]').forEach(function (element) {
            element.addEventListener('click', function (event) {
                event.preventDefault();
                var email = element.getAttribute('data-copy-email') || '';
                if (!email || !navigator.clipboard) {
                    return;
                }

                navigator.clipboard.writeText(email).then(function () {
                    var originalText = element.textContent;
                    element.textContent = 'Copiado';
                    window.setTimeout(function () {
                        element.textContent = originalText;
                    }, 1400);
                });
            });
        });

        document.querySelectorAll('[data-collapsible]').forEach(function (section) {
            var toggle = section.querySelector('[data-collapse-toggle]');
            var content = section.querySelector('[data-collapse-content]');
            if (!toggle || !content) return;

            toggle.addEventListener('click', function () {
                var isClosed = section.classList.toggle('is-collapsed');
                toggle.setAttribute('aria-expanded', isClosed ? 'false' : 'true');
                toggle.textContent = isClosed ? '+' : '-';
            });
        });

        (function () {
            var inbox = document.querySelector('.feedback-inbox-panel');
            var reader = document.querySelector('.feedback-reader-panel');
            var inboxList = document.querySelector('.feedback-inbox-list');

            function updateInboxMode() {
                if (!inbox || !inboxList) return;
                var cardCount = inboxList.querySelectorAll('.feedback-list-card').length;
                inbox.setAttribute('data-inbox-mode', cardCount > 4 ? 'scroll' : 'compact');
            }

            function syncInboxHeight() {
                if (!inbox || !reader) return;

                if (window.innerWidth <= 1180) {
                    inbox.style.minHeight = '';
                    return;
                }

                inbox.style.minHeight = '';
                inbox.style.minHeight = reader.offsetHeight + 'px';
            }

            updateInboxMode();
            syncInboxHeight();
            window.addEventListener('load', syncInboxHeight);
            window.addEventListener('resize', function () {
                updateInboxMode();
                syncInboxHeight();
            });

            if (reader && window.MutationObserver) {
                var observer = new MutationObserver(syncInboxHeight);
                observer.observe(reader, {
                    childList: true,
                    subtree: true,
                    attributes: true
                });
            }

            document.querySelectorAll('[data-collapse-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    window.setTimeout(syncInboxHeight, 0);
                });
            });
        })();

    </script>
</body>
</html>




