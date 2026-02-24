<?php
session_start();

if (!isset($_SESSION['permissoes']) || $_SESSION['permissoes'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require("Bd/ligar.php");

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

function esc($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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

function cd_human_action($acao)
{
    $map = [
        'bloquear_utilizador' => 'Bloquear utilizador',
        'desbloquear_utilizador' => 'Desbloquear utilizador',
        'alterar_role' => 'Alterar permissões',
        'reset_faltas' => 'Resetar faltas',
        'marcar_presenca' => 'Marcar presença',
        'estado_site' => 'Alterar estado do site'
    ];
    if (isset($map[$acao])) {
        return $map[$acao];
    }
    return ucwords(str_replace('_', ' ', (string)$acao));
}

function cd_human_target($tipo)
{
    $map = [
        'cliente' => 'Cliente',
        'reserva' => 'Reserva',
        'estado_site' => 'Estado do site'
    ];
    return $map[$tipo] ?? ucwords(str_replace('_', ' ', (string)$tipo));
}

function cd_human_details($detalhes)
{
    $raw = trim((string)$detalhes);
    if ($raw === '') {
        return '-';
    }
    if (strpos($raw, ';') === false || strpos($raw, '=') === false) {
        return $raw;
    }

    $pairs = explode(';', $raw);
    $pretty = [];
    foreach ($pairs as $pair) {
        $part = trim($pair);
        if ($part === '') {
            continue;
        }
        $bits = explode('=', $part, 2);
        if (count($bits) !== 2) {
            $pretty[] = $part;
            continue;
        }
        $key = trim($bits[0]);
        $val = trim($bits[1]);
        $label = ucwords(str_replace('_', ' ', $key));
        $pretty[] = $label . ': ' . $val;
    }
    return count($pretty) > 0 ? implode(' | ', $pretty) : $raw;
}

function cd_bind_params($stmt, $types, array &$params)
{
    if ($types === '' || count($params) === 0) {
        return;
    }
    $bind = [$types];
    foreach ($params as $k => $v) {
        $bind[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}

function cd_fetch_scalar($con, $sql, $types = '', array $params = [])
{
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return 0;
    }
    cd_bind_params($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $value = 0;
    if ($res) {
        $row = mysqli_fetch_row($res);
        $value = (int)($row[0] ?? 0);
    }
    mysqli_stmt_close($stmt);
    return $value;
}

function cd_fetch_rows($con, $sql, $types = '', array $params = [])
{
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return [];
    }
    cd_bind_params($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
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

$q = trim((string)($_GET['q'] ?? ''));
$adminNome = trim((string)($_GET['admin_nome'] ?? ''));
$acao = trim((string)($_GET['acao'] ?? ''));
$alvoTipo = trim((string)($_GET['alvo_tipo'] ?? ''));
$dataFrom = trim((string)($_GET['data_from'] ?? ''));
$dataTo = trim((string)($_GET['data_to'] ?? ''));
$perPage = (int)($_GET['per_page'] ?? 50);
$page = max(1, (int)($_GET['page'] ?? 1));

$allowedPerPage = [25, 50, 100, 200];
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 50;
}

$where = [];
$types = '';
$params = [];

if ($q !== '') {
    $where[] = "(l.admin_nome LIKE ? OR l.acao LIKE ? OR l.alvo_tipo LIKE ? OR l.detalhes LIKE ? OR COALESCE(c_direct.nome, c_reserva.nome, '') LIKE ? OR COALESCE(c_direct.email, c_reserva.email, '') LIKE ?)";
    $like = '%' . $q . '%';
    $types .= 'ssssss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($adminNome !== '') {
    $where[] = "l.admin_nome = ?";
    $types .= 's';
    $params[] = $adminNome;
}

if ($acao !== '') {
    $where[] = "l.acao = ?";
    $types .= 's';
    $params[] = $acao;
}

if ($alvoTipo !== '') {
    $where[] = "l.alvo_tipo = ?";
    $types .= 's';
    $params[] = $alvoTipo;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFrom)) {
    $where[] = "DATE(l.criado_em) >= ?";
    $types .= 's';
    $params[] = $dataFrom;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataTo)) {
    $where[] = "DATE(l.criado_em) <= ?";
    $types .= 's';
    $params[] = $dataTo;
}

$whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

$fromSql = "
    FROM admin_audit_log l
    LEFT JOIN Cliente c_direct
        ON l.alvo_tipo = 'cliente' AND l.alvo_id = c_direct.id
    LEFT JOIN reservas r_alvo
        ON l.alvo_tipo = 'reserva' AND l.alvo_id = r_alvo.id
    LEFT JOIN Cliente c_reserva
        ON r_alvo.cliente_id = c_reserva.id
";

$total = cd_fetch_scalar(
    $con,
    "SELECT COUNT(*) $fromSql $whereSql",
    $types,
    $params
);

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$rows = cd_fetch_rows(
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
     $fromSql
     $whereSql
     ORDER BY l.id DESC
     LIMIT $perPage OFFSET $offset",
    $types,
    $params
);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvRows = cd_fetch_rows(
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
         $fromSql
         $whereSql
         ORDER BY l.id DESC",
        $types,
        $params
    );

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="admin_logs_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Data/Hora', 'Admin', 'Ação', 'Tipo Alvo', 'ID Alvo', 'Nome', 'Email', 'Detalhes'], ';');

    foreach ($csvRows as $row) {
        $adminLabel = $row['admin_nome'] . ' (#' . (int)$row['admin_id'] . ')';
        $afetadoNome = trim((string)($row['afetado_nome'] ?? ''));
        $afetadoEmail = trim((string)($row['afetado_email'] ?? ''));
        $alvoTipo = cd_human_target((string)$row['alvo_tipo']);
        $alvoId = isset($row['alvo_id']) && $row['alvo_id'] !== null ? (int)$row['alvo_id'] : '-';

        fputcsv(
            $out,
            [
                (int)$row['id'],
                cd_fmt_datetime($row['criado_em'] ?? null),
                $adminLabel,
                cd_human_action((string)$row['acao']),
                $alvoTipo,
                $alvoId,
                $afetadoNome !== '' ? $afetadoNome : '-',
                $afetadoEmail !== '' ? $afetadoEmail : '-',
                cd_human_details((string)($row['detalhes'] ?? '-'))
            ],
            ';'
        );
    }

    fclose($out);
    exit();
}

$admins = cd_fetch_rows($con, "SELECT DISTINCT admin_nome FROM admin_audit_log ORDER BY admin_nome ASC");
$acoes = cd_fetch_rows($con, "SELECT DISTINCT acao FROM admin_audit_log ORDER BY acao ASC");
$alvos = cd_fetch_rows($con, "SELECT DISTINCT alvo_tipo FROM admin_audit_log ORDER BY alvo_tipo ASC");

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
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Auditoria</title>
    <link rel="stylesheet" href="Css/admin.css">
    <link rel="stylesheet" href="Css/admin_logs.css">
    <link rel="stylesheet" href="Css/bttlogin.css">
</head>
<body class="cdol-admin cdol-logs">
    <div class="container">
        <div class="admin-hero">
            <div>
                <h2>Logs de Auditoria</h2>
                <p>Histórico completo das ações administrativas.</p>
            </div>
            <div class="admin-kpis">
                <div class="admin-kpi-card">
                    <span>Total de registos</span>
                    <strong><?php echo (int)$total; ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Página</span>
                    <strong><?php echo (int)$page . '/' . (int)$totalPages; ?></strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Por página</span>
                    <strong><?php echo (int)$perPage; ?></strong>
                </div>
            </div>
        </div>

        <section class="admin-section logs-filters-section">
            <h3>Filtros</h3>
            <p class="logs-subtle">Use combinações de filtros para investigar alterações específicas.</p>
            <p class="logs-subtle">
                Dica: os dois campos de data filtram a Data da Reserva (início e fim).
            </p>
            <div class="quick-date-buttons">
                <button type="button" class="btn quick-date-btn" id="logsQuickHoje">Hoje</button>
                <button type="button" class="btn quick-date-btn" id="logsQuick7">Últimos 7 dias</button>
                <button type="button" class="btn quick-date-btn" id="logsQuick30">Últimos 30 dias</button>
            </div>
            <form method="get" class="admin-filter-bar logs-filters-grid">
                <input type="text" name="q" value="<?php echo esc($q); ?>" placeholder="Pesquisar em admin, ação, alvo e detalhes">

                <select name="admin_nome">
                    <option value="">Admin: Todos</option>
                    <?php foreach ($admins as $a): ?>
                        <option value="<?php echo esc($a['admin_nome']); ?>" <?php echo $adminNome === $a['admin_nome'] ? 'selected' : ''; ?>>
                            <?php echo esc($a['admin_nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="acao">
                    <option value="">Ação: Todas</option>
                    <?php foreach ($acoes as $a): ?>
                        <option value="<?php echo esc($a['acao']); ?>" <?php echo $acao === $a['acao'] ? 'selected' : ''; ?>>
                            <?php echo esc($a['acao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="alvo_tipo">
                    <option value="">Alvo: Todos</option>
                    <?php foreach ($alvos as $a): ?>
                        <option value="<?php echo esc($a['alvo_tipo']); ?>" <?php echo $alvoTipo === $a['alvo_tipo'] ? 'selected' : ''; ?>>
                            <?php echo esc($a['alvo_tipo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="data_from" value="<?php echo esc($dataFrom); ?>" aria-label="Data inicial">
                <input type="date" name="data_to" value="<?php echo esc($dataTo); ?>" aria-label="Data final">

                <select name="per_page">
                    <?php foreach ($allowedPerPage as $n): ?>
                        <option value="<?php echo $n; ?>" <?php echo $perPage === $n ? 'selected' : ''; ?>>
                            <?php echo $n; ?>/página
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="logs-filter-actions">
                    <button type="submit" class="btn logs-btn">Aplicar filtros</button>
                    <button type="submit" class="btn logs-btn" name="export" value="csv">Exportar CSV</button>
                    <a class="logs-reset-link" href="admin_logs.php">Limpar filtros</a>
                </div>
            </form>
        </section>

        <section class="admin-section logs-results-section">
            <h3>Registos</h3>
            <div class="admin-table-wrap logs-table-wrap">
                <table class="admin-table logs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data/Hora</th>
                            <th>Admin</th>
                            <th>Ação</th>
                            <th>Alvo</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows) === 0): ?>
                            <tr>
                                <td colspan="8"><span class="status-chip neutral">Sem registos para os filtros selecionados</span></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php
                                $alvo = $row['alvo_tipo'] . (isset($row['alvo_id']) && $row['alvo_id'] !== null ? ' #' . (int)$row['alvo_id'] : '');
                                $adminLabel = $row['admin_nome'] . ' (#' . (int)$row['admin_id'] . ')';
                                $afetadoNome = trim((string)($row['afetado_nome'] ?? ''));
                                $afetadoEmail = trim((string)($row['afetado_email'] ?? ''));
                                ?>
                                <tr>
                                    <td><?php echo (int)$row['id']; ?></td>
                                    <td><?php echo esc(cd_fmt_datetime($row['criado_em'] ?? null)); ?></td>
                                    <td><?php echo esc($adminLabel); ?></td>
                                    <td><span class="status-chip warn"><?php echo esc($row['acao']); ?></span></td>
                                    <td><?php echo esc($alvo); ?></td>
                                    <td><?php echo esc($afetadoNome !== '' ? $afetadoNome : '-'); ?></td>
                                    <td>
                                        <?php if ($afetadoEmail !== ''): ?>
                                            <span class="logs-email-text" title="<?php echo esc($afetadoEmail); ?>">
                                                <?php echo esc($afetadoEmail); ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="logs-detail-cell"><?php echo esc((string)($row['detalhes'] ?? '-')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>

        <div class="botoesNav" id="navFim">
            <a href="admin.php" id="btnDashboard" class="btt-padrao-login">&larr; Voltar ao Admin</a>
            <a href="index.php" id="btnInicio" class="btt-padrao-login">&larr; Início</a>
        </div>
    </div>
    <script src="Js/admin_logs.js"></script>
</body>
</html>
