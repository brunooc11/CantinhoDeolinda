<?php

function cd_feedback_has_column(mysqli $con, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $res = mysqli_query($con, $sql);

    return $res && mysqli_num_rows($res) > 0;
}

function cd_feedback_index_exists(mysqli $con, string $table, string $indexName): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $indexName)) {
        return false;
    }

    $sql = "SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'";
    $res = mysqli_query($con, $sql);

    return $res && mysqli_num_rows($res) > 0;
}

function cd_feedback_primary_column(mysqli $con): ?string
{
    $res = mysqli_query($con, "SHOW KEYS FROM contactos WHERE Key_name = 'PRIMARY' ORDER BY Seq_in_index ASC");
    if (!$res) {
        return null;
    }

    $row = mysqli_fetch_assoc($res);
    $column = trim((string)($row['Column_name'] ?? ''));

    return preg_match('/^[A-Za-z0-9_]+$/', $column) ? $column : null;
}

function cd_feedback_ensure_schema(mysqli $con): string
{
    mysqli_query(
        $con,
        "CREATE TABLE IF NOT EXISTS contactos (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            assunto VARCHAR(120) NOT NULL,
            mensagem TEXT NOT NULL,
            estado VARCHAR(20) NOT NULL DEFAULT 'novo',
            admin_nota TEXT NULL,
            lido_em DATETIME NULL,
            respondido_em DATETIME NULL,
            arquivado_em DATETIME NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    if (!cd_feedback_has_column($con, 'contactos', 'id') && cd_feedback_primary_column($con) === null) {
        mysqli_query($con, "ALTER TABLE contactos ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
    }

    if (!cd_feedback_has_column($con, 'contactos', 'criado_em')) {
        mysqli_query($con, "ALTER TABLE contactos ADD COLUMN criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    if (!cd_feedback_has_column($con, 'contactos', 'estado')) {
        mysqli_query($con, "ALTER TABLE contactos ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'novo'");
    }

    if (!cd_feedback_has_column($con, 'contactos', 'admin_nota')) {
        mysqli_query($con, "ALTER TABLE contactos ADD COLUMN admin_nota TEXT NULL");
    }

    if (!cd_feedback_has_column($con, 'contactos', 'lido_em')) {
        mysqli_query($con, "ALTER TABLE contactos ADD COLUMN lido_em DATETIME NULL");
    }

    if (!cd_feedback_has_column($con, 'contactos', 'respondido_em')) {
        mysqli_query($con, "ALTER TABLE contactos ADD COLUMN respondido_em DATETIME NULL");
    }

    if (!cd_feedback_has_column($con, 'contactos', 'arquivado_em')) {
        mysqli_query($con, "ALTER TABLE contactos ADD COLUMN arquivado_em DATETIME NULL");
    }

    if (!cd_feedback_has_column($con, 'contactos', 'atualizado_em')) {
        mysqli_query(
            $con,
            "ALTER TABLE contactos
             ADD COLUMN atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        );
    }

    $pk = cd_feedback_primary_column($con);
    if ($pk === null && cd_feedback_has_column($con, 'contactos', 'id')) {
        mysqli_query($con, "ALTER TABLE contactos MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
        mysqli_query($con, "ALTER TABLE contactos ADD PRIMARY KEY (id)");
        $pk = cd_feedback_primary_column($con);
    }

    mysqli_query($con, "UPDATE contactos SET estado = 'novo' WHERE estado IS NULL OR estado = ''");

    if (!cd_feedback_index_exists($con, 'contactos', 'idx_contactos_estado')) {
        mysqli_query($con, "ALTER TABLE contactos ADD INDEX idx_contactos_estado (estado)");
    }

    if (!cd_feedback_index_exists($con, 'contactos', 'idx_contactos_assunto')) {
        mysqli_query($con, "ALTER TABLE contactos ADD INDEX idx_contactos_assunto (assunto)");
    }

    if (!cd_feedback_index_exists($con, 'contactos', 'idx_contactos_criado_em')) {
        mysqli_query($con, "ALTER TABLE contactos ADD INDEX idx_contactos_criado_em (criado_em)");
    }

    return $pk ?? 'id';
}
