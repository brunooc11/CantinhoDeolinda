<?php

const CD_MESA_AUTO_RELEASE_MINUTES = 90;

function cd_has_column_safe(mysqli $con, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $res = mysqli_query($con, $sql);
    return $res && mysqli_num_rows($res) > 0;
}

function cd_has_rows_for_condition_safe(mysqli $con, string $table, string $conditionSql): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $sql = "SELECT 1 FROM `$table` WHERE $conditionSql LIMIT 1";
    $res = mysqli_query($con, $sql);
    return $res && mysqli_num_rows($res) > 0;
}

function cd_sync_mesa_states(mysqli $con, int $durationMinutes = CD_MESA_AUTO_RELEASE_MINUTES): void
{
    if ($durationMinutes <= 0) {
        $durationMinutes = CD_MESA_AUTO_RELEASE_MINUTES;
    }

    $hasTipo = cd_has_column_safe($con, 'mesas', 'tipo');
    $hasAtiva = cd_has_column_safe($con, 'mesas', 'ativa');

    $joinFilters = [];
    if ($hasTipo && cd_has_rows_for_condition_safe($con, 'mesas', "tipo = 'mesa'")) {
        $joinFilters[] = "m.tipo = 'mesa'";
    }
    if ($hasAtiva && cd_has_rows_for_condition_safe($con, 'mesas', "ativa = 1")) {
        $joinFilters[] = "m.ativa = 1";
    }
    $joinWhere = count($joinFilters) > 0 ? ' AND ' . implode(' AND ', $joinFilters) : '';

    $sqlReservadas = "
        UPDATE mesas m
        JOIN reserva_mesas rm ON rm.mesa_id = m.id
        JOIN reservas r ON r.id = rm.reserva_id
        SET m.estado = 'reservada'
        WHERE r.confirmado = 1
          AND r.estado = 'pendente'
          AND TIMESTAMPADD(MINUTE, ?, TIMESTAMP(r.data_reserva, r.hora_reserva)) > NOW()
          $joinWhere
    ";
    $stmtReservadas = mysqli_prepare($con, $sqlReservadas);
    if ($stmtReservadas) {
        mysqli_stmt_bind_param($stmtReservadas, 'i', $durationMinutes);
        mysqli_stmt_execute($stmtReservadas);
        mysqli_stmt_close($stmtReservadas);
    }

    $sqlOcupadas = "
        UPDATE mesas m
        JOIN reserva_mesas rm ON rm.mesa_id = m.id
        JOIN reservas r ON r.id = rm.reserva_id
        SET m.estado = 'ocupada'
        WHERE r.confirmado = 1
          AND r.estado = 'compareceu'
          AND TIMESTAMPADD(MINUTE, ?, TIMESTAMP(r.data_reserva, r.hora_reserva)) > NOW()
          $joinWhere
    ";
    $stmtOcupadas = mysqli_prepare($con, $sqlOcupadas);
    if ($stmtOcupadas) {
        mysqli_stmt_bind_param($stmtOcupadas, 'i', $durationMinutes);
        mysqli_stmt_execute($stmtOcupadas);
        mysqli_stmt_close($stmtOcupadas);
    }

    $sqlLibertarInativas = "
        UPDATE mesas m
        JOIN reserva_mesas rm ON rm.mesa_id = m.id
        LEFT JOIN reservas r ON r.id = rm.reserva_id
        SET m.estado = 'livre'
        WHERE (
            r.id IS NULL
            OR r.confirmado <> 1
            OR r.estado NOT IN ('pendente', 'compareceu')
            OR TIMESTAMPADD(MINUTE, ?, TIMESTAMP(r.data_reserva, r.hora_reserva)) <= NOW()
        )
        $joinWhere
    ";
    $stmtLibertarInativas = mysqli_prepare($con, $sqlLibertarInativas);
    if ($stmtLibertarInativas) {
        mysqli_stmt_bind_param($stmtLibertarInativas, 'i', $durationMinutes);
        mysqli_stmt_execute($stmtLibertarInativas);
        mysqli_stmt_close($stmtLibertarInativas);
    }
}

function cd_get_mesa_lock_map(mysqli $con, int $durationMinutes = CD_MESA_AUTO_RELEASE_MINUTES): array
{
    if ($durationMinutes <= 0) {
        $durationMinutes = CD_MESA_AUTO_RELEASE_MINUTES;
    }

    $locks = [];
    $sql = "
        SELECT
            rm.mesa_id,
            r.id AS reserva_id,
            r.estado AS estado_reserva,
            r.data_reserva,
            r.hora_reserva,
            r.numero_pessoas,
            c.nome AS cliente_nome,
            c.email AS cliente_email,
            TIMESTAMP(r.data_reserva, r.hora_reserva) AS inicio_reserva,
            TIMESTAMPADD(MINUTE, ?, TIMESTAMP(r.data_reserva, r.hora_reserva)) AS fim_reserva
        FROM reserva_mesas rm
        JOIN reservas r ON r.id = rm.reserva_id
        JOIN Cliente c ON c.id = r.cliente_id
        WHERE r.confirmado = 1
          AND (
                r.estado = 'pendente'
                OR r.estado = 'compareceu'
              )
          AND TIMESTAMPADD(MINUTE, ?, TIMESTAMP(r.data_reserva, r.hora_reserva)) > NOW()
        ORDER BY fim_reserva ASC, r.id DESC
    ";

    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return $locks;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $durationMinutes, $durationMinutes);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $mesaId = (string)($row['mesa_id'] ?? '');
            if ($mesaId === '') {
                continue;
            }

            $estadoReserva = (string)($row['estado_reserva'] ?? 'pendente');
            $fimReserva = (string)($row['fim_reserva'] ?? '');
            $inicioReserva = (string)($row['inicio_reserva'] ?? '');
            $statusLabel = $estadoReserva === 'compareceu' ? 'Ocupada' : 'Reservada';
            $releaseShort = $fimReserva !== '' ? date('H:i', strtotime($fimReserva)) : '--:--';
            $releaseFull = $fimReserva !== '' ? date('d/m/Y H:i', strtotime($fimReserva)) : '-';
            $clienteNome = trim((string)($row['cliente_nome'] ?? ''));
            $clienteEmail = trim((string)($row['cliente_email'] ?? ''));
            $reservaData = trim((string)($row['data_reserva'] ?? ''));
            $reservaHora = trim((string)($row['hora_reserva'] ?? ''));

            $locks[$mesaId] = [
                'reserva_id' => (int)($row['reserva_id'] ?? 0),
                'status' => $estadoReserva === 'compareceu' ? 'ocupada' : 'reservada',
                'status_label' => $statusLabel,
                'cliente_nome' => $clienteNome !== '' ? $clienteNome : 'Cliente',
                'cliente_email' => $clienteEmail !== '' ? $clienteEmail : '-',
                'numero_pessoas' => (int)($row['numero_pessoas'] ?? 0),
                'data_reserva' => $reservaData !== '' ? date('d/m/Y', strtotime($reservaData)) : '-',
                'hora_reserva' => $reservaHora !== '' ? substr($reservaHora, 0, 5) : '-',
                'release_time' => $releaseShort,
                'release_at' => $releaseFull,
                'starts_at' => $inicioReserva !== '' ? date('d/m/Y H:i', strtotime($inicioReserva)) : '-',
            ];
        }
    }
    mysqli_stmt_close($stmt);

    return $locks;
}
