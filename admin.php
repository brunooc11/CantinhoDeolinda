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

// BLOQUEAR UTILIZADOR
if (isset($_GET['bloquear'])) {
    $id = intval($_GET['bloquear']);
    mysqli_query($con, "UPDATE Cliente SET estado = 0 WHERE id = $id");
    echo "<script>alert('Utilizador bloqueado!'); window.location.href='admin.php';</script>";
    exit();
}

// DESBLOQUEAR UTILIZADOR
if (isset($_GET['desbloquear'])) {
    $id = intval($_GET['desbloquear']);
    mysqli_query($con, "UPDATE Cliente SET estado = 1 WHERE id = $id");
    echo "<script>alert('Utilizador desbloqueado!'); window.location.href='admin.php';</script>";
    exit();
}

// TORNAR ADMIN
if (isset($_GET['role_admin'])) {
    $id = intval($_GET['role_admin']);
    mysqli_query($con, "UPDATE Cliente SET permissoes = 'admin' WHERE id = $id");
    echo "<script>alert('Utilizador agora é admin!'); window.location.href='admin.php';</script>";
    exit();
}

// TORNAR CLIENTE
if (isset($_GET['role_user'])) {
    $id = intval($_GET['role_user']);

    //impefir o admin de se autodespromover 
     if ($id == $_SESSION['id']) {
        echo "<script>alert('Não te podes remover a ti próprio como admin!'); window.location.href='admin.php';</script>";
        exit();
    }

    $check = mysqli_query($con, "SELECT COUNT(*) AS total FROM Cliente WHERE permissoes = 'admin'");
    $row = mysqli_fetch_assoc($check);

    if ($row['total'] <= 1) {
        echo "<script>alert('Não podes remover o último admin!'); window.location.href='admin.php';</script>";
        exit();
    }

    mysqli_query($con, "UPDATE Cliente SET permissoes = 'cliente' WHERE id = $id");
    echo "<script>alert('Utilizador agora é cliente!'); window.location.href='admin.php';</script>";
    exit();
}

// ESTADO DO SITE
$sql = "SELECT bloqueado FROM estado_site LIMIT 1";
$res = mysqli_query($con, $sql);
$row = mysqli_fetch_assoc($res);
$bloqueado = $row['bloqueado'];

if (isset($_POST['bloquear_site'])) {
    mysqli_query($con, "UPDATE estado_site SET bloqueado = 1");
    echo "<script>alert('Site bloqueado!'); window.location.href='admin.php';</script>";
    exit();
}

if (isset($_POST['ativar_site'])) {
    mysqli_query($con, "UPDATE estado_site SET bloqueado = 0");
    echo "<script>alert('Site ativado!'); window.location.href='admin.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Painel de Administração</title>
    <link rel="stylesheet" href="Css/admin.css">
    <link rel="stylesheet" href="Css/bttlogin.css"
        </head>

<body>

    <div class="container">
        <h2>Painel de Administração</h2>
        <p>Use este painel para gerir o site.</p>

        <h3>Estado do Site</h3>
        <form method="post">
            <?php if ($bloqueado == 0): ?>
                <button type="submit" name="bloquear_site" class="btn danger">🔒 Bloquear Site</button>
            <?php else: ?>
                <button type="submit" name="ativar_site" class="btn success">🔓 Ativar Site</button>
            <?php endif; ?>
        </form>

        <h3>Utilizadores</h3>

        <table>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Telefone</th>
                <th>Estado</th>
                <th>Tipo</th>
                <th>Ação</th>
                <th>Role</th>
            </tr>

            <?php
            $sql = "SELECT id, nome, email, telefone, estado, permissoes FROM Cliente ORDER BY nome ASC";
            $res = mysqli_query($con, $sql);

            while ($user = mysqli_fetch_assoc($res)) {

                echo "<tr>";
                echo "<td>{$user['nome']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['telefone']}</td>";

                echo $user['estado'] == 1
                    ? "<td style='color:lightgreen;'>Ativo</td>"
                    : "<td style='color:red;'>Bloqueado</td>";

                echo "<td>" . strtoupper($user['permissoes']) . "</td>";

                echo "<td>";
                if ($user['estado'] == 1) {
                    echo "<a class='action-btn' href='admin.php?bloquear={$user['id']}'>Bloquear</a>";
                } else {
                    echo "<a class='action-btn green-btn' href='admin.php?desbloquear={$user['id']}'>Desbloquear</a>";
                }
                echo "</td>";

                echo "<td>";
                if ($user['permissoes'] === 'admin') {
                    echo "<a class='action-btn blue-btn' href='admin.php?role_user={$user['id']}'>Tornar Cliente</a>";
                } else {
                    echo "<a class='action-btn blue-btn' href='admin.php?role_admin={$user['id']}'>Tornar Admin</a>";
                }
                echo "</td>";

                echo "</tr>";
            }
            ?>
        </table>

        <div class="botoesNav">
            <a href="index.php" id="btnInicio">← Início</a>
            <a href="dashboard.php" id="btnDashboard">← Dashboard</a>
        </div>

    </div>
</body>

</html>