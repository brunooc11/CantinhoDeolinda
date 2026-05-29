<?php
if (!function_exists('esc')) {
    function esc($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cd_csrf_token')) {
    function cd_csrf_token(): string
    {
        return (string)($_SESSION['csrf_token'] ?? '');
    }
}

if (!function_exists('cd_csrf_input')) {
    function cd_csrf_input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . esc(cd_csrf_token()) . '">';
    }
}

if (!function_exists('cd_admin_audit')) {
    function cd_admin_audit(mysqli $con, string $acao, string $alvoTipo, ?int $alvoId = null, ?string $detalhes = null): void
    {
        $adminId = (int)($_SESSION['id'] ?? 0);
        $adminNome = (string)($_SESSION['nome'] ?? 'admin');
        $stmt = mysqli_prepare($con, "INSERT INTO admin_audit_log (admin_id, admin_nome, acao, alvo_tipo, alvo_id, detalhes) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isssis', $adminId, $adminNome, $acao, $alvoTipo, $alvoId, $detalhes);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}
