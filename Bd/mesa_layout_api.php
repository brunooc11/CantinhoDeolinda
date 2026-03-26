<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/ligar.php';
require_once __DIR__ . '/mesa_status_helper.php';

if (!isset($_SESSION['permissoes']) || $_SESSION['permissoes'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit();
}

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
if (!is_array($json)) {
    $json = [];
}

$token = (string)($json['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['csrf_token'] ?? '');
if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'invalid_csrf']);
    exit();
}

function cd_mesa_has_column(mysqli $con, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    $sql = "SHOW COLUMNS FROM mesas LIKE '$column'";
    $res = mysqli_query($con, $sql);
    return $res && mysqli_num_rows($res) > 0;
}

function cd_mesa_position_columns(mysqli $con): array
{
    $leftColumn = cd_mesa_has_column($con, 'pos_left') ? 'pos_left' : null;
    $topColumn = cd_mesa_has_column($con, 'pos_top')
        ? 'pos_top'
        : (cd_mesa_has_column($con, 'pos_right') ? 'pos_right' : null);
    $groupColumn = cd_mesa_has_column($con, 'grupo') ? 'grupo' : null;

    return [
        'left' => $leftColumn,
        'top' => $topColumn,
        'group' => $groupColumn,
    ];
}

$action = trim((string)($json['action'] ?? ''));
$columns = cd_mesa_position_columns($con);

if ($action === 'reset_layout') {
    $updates = [];
    if ($columns['left'] !== null) {
        $updates[] = "`{$columns['left']}` = NULL";
    }
    if ($columns['top'] !== null) {
        $updates[] = "`{$columns['top']}` = NULL";
    }
    if ($columns['group'] !== null) {
        $updates[] = "`{$columns['group']}` = NULL";
    }

    if (count($updates) === 0) {
        echo json_encode(['ok' => true]);
        exit();
    }

    $sql = "UPDATE mesas SET " . implode(', ', $updates);
    if (!mysqli_query($con, $sql)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db_query_failed']);
        exit();
    }

    echo json_encode(['ok' => true]);
    exit();
}

$mesaId = trim((string)($json['mesa_id'] ?? ''));
if ($mesaId === '' || !preg_match('/^[A-Za-z0-9_-]{1,50}$/', $mesaId)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'invalid_mesa_id']);
    exit();
}

if ($action === 'save_state') {
    $state = trim((string)($json['state'] ?? ''));
    if (!in_array($state, ['livre', 'reservada', 'ocupada'], true)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'invalid_state']);
        exit();
    }

    cd_sync_mesa_states($con);
    $locks = cd_get_mesa_lock_map($con);
    if (isset($locks[$mesaId])) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'mesa_locked']);
        exit();
    }

    $stmt = mysqli_prepare($con, "UPDATE mesas SET estado = ? WHERE id = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db_prepare_failed']);
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'ss', $state, $mesaId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(['ok' => true]);
    exit();
}

if ($action === 'save_position') {
    if ($columns['left'] === null || $columns['top'] === null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'position_columns_missing']);
        exit();
    }

    $left = trim((string)($json['left'] ?? ''));
    $top = trim((string)($json['top'] ?? ''));
    if (!preg_match('/^\d{1,3}(\.\d{1,2})?%$/', $left) || !preg_match('/^\d{1,3}(\.\d{1,2})?%$/', $top)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'invalid_position']);
        exit();
    }

    $sql = "UPDATE mesas SET `{$columns['left']}` = ?, `{$columns['top']}` = ? WHERE id = ?";
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db_prepare_failed']);
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'sss', $left, $top, $mesaId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(['ok' => true]);
    exit();
}

if ($action === 'save_group') {
    if ($columns['group'] === null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'group_column_missing']);
        exit();
    }

    $group = array_key_exists('group', $json) ? trim((string)$json['group']) : null;
    if ($group !== null && $group !== '' && !preg_match('/^[A-Za-z0-9_-]{1,50}$/', $group)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'invalid_group']);
        exit();
    }

    if ($group === null || $group === '') {
        $sql = "UPDATE mesas SET `{$columns['group']}` = NULL WHERE id = ?";
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'db_prepare_failed']);
            exit();
        }
        mysqli_stmt_bind_param($stmt, 's', $mesaId);
    } else {
        $sql = "UPDATE mesas SET `{$columns['group']}` = ? WHERE id = ?";
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'db_prepare_failed']);
            exit();
        }
        mysqli_stmt_bind_param($stmt, 'ss', $group, $mesaId);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(['ok' => true]);
    exit();
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'invalid_action']);
