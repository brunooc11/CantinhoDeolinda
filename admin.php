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

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #0d0d0d; 
            /* fundo totalmente preto */
            min-height: 100vh;
            /* ocupa a página inteira */
            color: white;
            font-family: Arial;
        }


        /* RETÂNGULO AMARELO À VOLTA DO CONTEÚDO */
        .container {
            width: 80%;
            margin: 50px auto;
            background: #111;
            /* fundo dentro do retângulo */
            padding: 25px;
            border-radius: 12px;

            border: 2px solid #ffcf00;
            /* CONTORNO AMARELO */
            box-shadow: 0 0 25px rgba(255, 200, 0, 0.25);
            /* brilho amarelo opcional */
        }

        h2,
        h3 {
            color: #ffcf33;
            margin-top: 0;
            text-shadow: 0 0 6px #ffcf33;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .danger {
            background: #d9534f;
            color: #fff;
        }

        .success {
            background: #5cb85c;
            color: #fff;
        }

        table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
            background: #1a1a1a;
            color: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background: #ffcf33;
            color: black;
            padding: 12px;
            text-transform: uppercase;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #333;
            text-align: center;
        }

        tr:hover {
            background: #252525;
        }

        a.action-btn {
            padding: 6px 12px;
            background: #d9534f;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        a.action-btn:hover {
            background: #c9302c;
        }

        .top-buttons {
            margin-top: 25px;
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .top-buttons a {
            color: #ffcf33;
            font-weight: bold;
            text-decoration: none;
        }

        .top-buttons a:hover {
            text-decoration: underline;
        }
    </style>
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
            </tr>

            <?php
            $sql = "SELECT id, nome, email, telefone, estado, permissoes FROM Cliente ORDER BY nome ASC";
            $res = mysqli_query($con, $sql);

            while ($user = mysqli_fetch_assoc($res)) {

                echo "<tr>";
                echo "<td>{$user['nome']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['telefone']}</td>";

                if ($user['estado'] == 1) {
                    echo "<td style='color:lightgreen;'>Ativo</td>";
                } else {
                    echo "<td style='color:red;'>Bloqueado</td>";
                }

                echo "<td>" . strtoupper($user['permissoes']) . "</td>";

                if ($user['permissoes'] === 'admin') {
                    echo "<td><span style='color:#ffcf33; font-weight:bold;'>ADMIN</span></td>";
                } else {
                    if ($user['estado'] == 1) {
                        echo "<td><a class='action-btn' href='admin.php?bloquear={$user['id']}'>Bloquear</a></td>";
                    } else {
                        echo "<td><a class='action-btn' style='background:#5cb85c;' href='admin.php?desbloquear={$user['id']}'>Desbloquear</a></td>";
                    }
                }

                echo "</tr>";
            }
            ?>
        </table>

        <div class="top-buttons">
            <a href="dashboard.php">← Dashboard</a>
            <a href="index.php">← Voltar ao início</a>
        </div>

    </div>

</body>

</html>