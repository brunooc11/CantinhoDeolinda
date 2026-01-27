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


// --- MARCAR COMPARECEU / NAO COMPARECEU ---
if (isset($_GET['presenca']) && isset($_GET['reserva'])) {
    $idReserva = intval($_GET['reserva']);
    $estado = $_GET['presenca'] === 'compareceu' ? 'compareceu' : 'nao_compareceu';

    // Atualiza o estado da reserva
    mysqli_query($con, "UPDATE reservas SET estado = '$estado' WHERE id = $idReserva");

    // Se NÃO compareceu → contar faltas
    if ($estado === 'nao_compareceu') {

        // Buscar o cliente da reserva
        $sqlCli = mysqli_query($con, "SELECT cliente_id FROM reservas WHERE id = $idReserva");
        $cli = mysqli_fetch_assoc($sqlCli);
        $clienteID = $cli['cliente_id'];

        // Contar faltas deste cliente
        $faltasSQL = mysqli_query(
            $con,
            "SELECT COUNT(*) AS faltas 
             FROM reservas 
             WHERE cliente_id = $clienteID AND estado = 'nao_compareceu'"
        );
        $faltas = mysqli_fetch_assoc($faltasSQL)['faltas'];

        // BLOQUEAR O USER PARA NAO PODER RESERVAR, AUTOMÁTICO APÓS 2 FALTAS
        if ($faltas >= 2) {
            mysqli_query($con, "UPDATE Cliente SET lista_negra = 1 WHERE id = $clienteID");
        }
    }

    header("Location: admin.php");
    exit();
}


// BLOQUEAR UTILIZADOR
if (isset($_GET['bloquear'])) {
    $id = intval($_GET['bloquear']);

    // Impedir autobloqueio
    if ($id == $_SESSION['id']) {
        echo "<script>alert('Não te podes bloquear a ti próprio!'); window.location.href='admin.php';</script>";
        exit();
    }

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

// RESETAR FALTAS DO UTILIZADOR
if (isset($_GET['reset_faltas'])) {
    $id = intval($_GET['reset_faltas']);

    // Atualizar reservas removendo faltas
    mysqli_query(
        $con,
        "UPDATE reservas 
         SET estado = 'perdoado(reset)' 
         WHERE cliente_id = $id 
           AND estado = 'nao_compareceu'"
    );

    mysqli_query($con, "UPDATE Cliente SET lista_negra = 0 WHERE id = $id");

    echo "<script>alert('Faltas resetadas e utilizador removido da lista negra.'); window.location.href='admin.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Painel de Administração</title>
    <link rel="stylesheet" href="Css/admin.css">
    <link rel="stylesheet" href="Css/bttlogin.css">
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
                <th>Faltas</th>
                <th>Lista Negra</th>
                <th>Reset</th>
                <th>Ação</th>
                <th>Role</th>
            </tr>

            <?php
            $sql = "SELECT id, nome, email, telefone, estado, permissoes, lista_negra FROM Cliente ORDER BY nome ASC";
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

                // Contar faltas do utilizador
                $faltasQuery = mysqli_query(
                    $con,
                    "SELECT COUNT(*) AS total 
                    FROM reservas 
                    WHERE cliente_id = {$user['id']} 
                    AND estado = 'nao_compareceu'"
                );
                $faltasUser = mysqli_fetch_assoc($faltasQuery)['total'];

                // Cor das faltas 
                $corFaltasUser = $faltasUser >= 2 ? 'red' : 'white';

                // Mostrar faltas
                echo "<td style='color:$corFaltasUser; font-weight:bold;'>$faltasUser</td>";

                // Lista negra
                if ($user['lista_negra'] == 1) {
                    echo "<td style='color:red; font-weight:bold;'>Sim</td>";
                } else {
                    echo "<td style='color:lightgreen; font-weight:bold;'>Não</td>";
                }

                // Mostrar botão de reset se houver faltas
                echo "<td>";
                if ($faltasUser > 0) {
                    echo "<a class='action-btn blue-btn' 
                        href='admin.php?reset_faltas={$user['id']}'>
                        Resetar Faltas
                        </a>";
                } else {
                    echo "<span style='color:gray;'>Sem faltas</span>";
                }
                echo "</td>";

                //Btt de bloquear/Desbloquear
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

        <h3>Gestão de Reservas</h3>

        <table>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Pessoas</th>
                <th>Confirmação</th>
                <th>Estado</th>
                <th>Ação</th>
            </tr>

            <?php
            // Buscar todas as reservas com dados do cliente
            $reservas = mysqli_query(
                $con,
                "SELECT r.*, c.nome 
                FROM reservas r
                JOIN Cliente c ON r.cliente_id = c.id
                ORDER BY r.data_reserva DESC, r.hora_reserva DESC"
            );

            while ($r = mysqli_fetch_assoc($reservas)) {
                /* cor do estado da reserva */
                switch ($r['estado']) {
                    case 'compareceu':
                        $corEstado = 'green';
                        break;
                    case 'nao_compareceu':
                        $corEstado = 'red';
                        break;
                    case 'perdoado(reset)':
                        $corEstado = 'yellow';
                        break;
                    default:
                        $corEstado = 'orange'; // pendente
                }


                /* confirmaçao da reserva */
                if ($r['confirmado'] == 1) {
                    $confLabel = "Confirmada";
                    $confColor = "green";
                } elseif ($r['confirmado'] == -1) {
                    $confLabel = "Recusada";
                    $confColor = "red";
                } else {
                    $confLabel = "Pendente";
                    $confColor = "orange";
                }



                /* tabela */
                echo "<tr>";

                echo "<td>{$r['id']}</td>";
                echo "<td>{$r['nome']}</td>";
                echo "<td>{$r['data_reserva']}</td>";
                echo "<td>{$r['hora_reserva']}</td>";
                echo "<td>{$r['numero_pessoas']}</td>";

                // Confirmação
                echo "<td style='color:$confColor; font-weight:bold;'>$confLabel</td>";

                // Estado
                echo "<td style='color:$corEstado; font-weight:bold;'>{$r['estado']}</td>";


                /* btts */
                echo "<td>";

                $estadoAtual = $r['estado'];

                // Mostrar botões APENAS se:
                // - Reserva está confirmada
                // - Estado ainda é pendente
                if ($r['confirmado'] == 1 && $estadoAtual === 'pendente') {

                    echo "<a class='action-btn green-btn' 
                    href='admin.php?presenca=compareceu&reserva={$r['id']}'>
                    Compareceu
                    </a>";

                    echo "<a class='action-btn danger' 
                    href='admin.php?presenca=nao_compareceu&reserva={$r['id']}'>
                    Não Compareceu
                    </a>";
                } else {

                    // Estado final → Não mostrar botões
                    echo "<span style='color:gray;'>Sem ações disponíveis</span>";
                }

                echo "</td>";


                echo "</tr>";
            }
            ?>
        </table>


        <div class="botoesNav">
            <a href="index.php" id="btnInicio">← Início</a>
            <a href="dashboard.php" id="btnDashboard">← Dashboard</a>
            <a href="Bd/confirmar_reservas.php" id="btnDashboard">← confirmar_reservas</a>
        </div>

    </div>
</body>

</html>