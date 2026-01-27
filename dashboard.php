<?php
require_once("config.php");
require_once("Bd/ligar.php");

// Verifica se o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}


// 🔔 POPUP de reservas confirmadas / recusadas
$idCliente = $_SESSION['id'];

$sqlPopup = "
    SELECT confirmado
    FROM reservas
    WHERE cliente_id = ?
      AND notificado_reserva = 0
      AND confirmado != 0
";

$stmtPopup = mysqli_prepare($con, $sqlPopup);
mysqli_stmt_bind_param($stmtPopup, "i", $idCliente);
mysqli_stmt_execute($stmtPopup);
$resPopup = mysqli_stmt_get_result($stmtPopup);

$temAceite = false;
$temRecusada = false;

while ($row = mysqli_fetch_assoc($resPopup)) {
    if ($row['confirmado'] == 1) {
        $temAceite = true;
    }
    if ($row['confirmado'] == -1) {
        $temRecusada = true;
    }
}

mysqli_stmt_close($stmtPopup);

if ($temAceite || $temRecusada) {

    if ($temAceite && $temRecusada) {
        $msg = "🔔 Tem atualizações nas suas reservas.\n\n"
             . "✔ Algumas reservas foram confirmadas.\n"
             . "❌ Algumas reservas foram recusadas.";
    } elseif ($temAceite) {
        $msg = "✅ A sua reserva foi confirmada pelo restaurante!";
    } else {
        $msg = "❌ A sua reserva foi recusada pelo restaurante.";
    }

    echo "<script>
        alert(" . json_encode($msg) . ");
    </script>";

    // marcar como notificado
    $sqlUpdate = "
        UPDATE reservas
        SET notificado_reserva = 1
        WHERE cliente_id = ?
          AND notificado_reserva = 0
          AND confirmado != 0
    ";
    $stmtUpdate = mysqli_prepare($con, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "i", $idCliente);
    mysqli_stmt_execute($stmtUpdate);
    mysqli_stmt_close($stmtUpdate);
}


// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Excluir conta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
    $id = $_SESSION['id'];
    $query = "DELETE FROM Cliente WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    session_destroy();
    mysqli_close($con);
    header("Location: index.php");
    exit();
}

// Alterar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_senha'])) {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $mensagem = "Por favor, preencha todos os campos.";
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = "As novas senhas não coincidem.";
    } else {
        $id = $_SESSION['id'];
        $query = "SELECT password FROM Cliente WHERE id = ?";
        $stmt = mysqli_prepare($con, $query);
        if (!$stmt) {
            die("Erro na preparação da query: " . mysqli_error($con));
        }

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $senha_bd);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if (!password_verify($senha_atual, $senha_bd)) {
            $mensagem = "A senha atual está incorreta.";
        } else {
            // Atualiza a password
            $nova_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $query = "UPDATE Cliente SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($con, $query);
            mysqli_stmt_bind_param($stmt, "si", $nova_hash, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Termina a sessão após alterar a password
            session_destroy();

            // Redireciona para login com aviso
            header("Location: login.php?pw_alterada=1");
            exit();
        }
    }
}

// Buscar reservas do utilizador
$reservas = [];
$id_cliente = $_SESSION['id'];
$query = "SELECT id, data_reserva, hora_reserva, numero_pessoas ,confirmado
        FROM reservas 
        WHERE cliente_id = ? 
        ORDER BY data_reserva DESC, hora_reserva DESC";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $id_cliente);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $id_reserva, $data_reserva, $hora_reserva, $numero_pessoas, $confirmado);
while (mysqli_stmt_fetch($stmt)) {
    $reservas[] = [
        'id' => $id_reserva,
        'data' => $data_reserva,
        'hora' => $hora_reserva,
        'pessoas' => $numero_pessoas,
        'confirmado' => $confirmado
    ];
}
mysqli_stmt_close($stmt);


// Cancelar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_reserva'])) {
    $id_reserva = intval($_POST['id_reserva']); // garante que é um número
    $query = "DELETE FROM reservas WHERE id = ? AND cliente_id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id_reserva, $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Atualiza a página mostrando a aba Reservas
    header("Location: dashboard.php?tab=Reservas");
    exit();
}

?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Cantinho_Deolinda</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Css/dashboard.css">
    <link rel="stylesheet" href="Css/bttlogin.css">
</head>

<body>

    <div class="dashboard-header">
        <h1>Olá, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</h1>
        <a href="index.php" id="bttInicio" class="btt-padrao-login">← Voltar ao Início</a>
    </div>

    <div class="dashboard-menu">
        <button class="tablink" onclick="openTab('Conta')">Conta</button>
        <button class="tablink" onclick="openTab('Reservas')">Reservas</button>
        <button class="tablink" onclick="openTab('Favoritos')">Favoritos</button>
        <button class="tablink" onclick="openTab('Pedidos')">Pedidos</button>
    </div>

    <!-- Abas -->
    <div id="Conta" class="tabcontent">
        <div class="card">
            <h3>Minha Conta</h3>

            <?php if (!empty($mensagem)): ?>
                <p style="color: <?php echo strpos($mensagem, 'sucesso') !== false ? 'green' : 'red'; ?>;">
                    <?php echo htmlspecialchars($mensagem); ?>
                </p>
            <?php endif; ?>

            <p>Nome: <?php echo htmlspecialchars($_SESSION['nome']); ?></p>
            <p>Email: <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
            <p>Conta criada em: <?php echo date("d/m/Y H:i", strtotime($_SESSION['data'] ?? '')); ?></p>

            <button type="button" class="btt-alterar-senha" onclick="toggleFormSenha()">Alterar Senha</button>

            <div class="form-lateral-card" id="formSenhaCard">
                <form method="POST">
                    <h3>Alterar Senha</h3>
                    <input type="password" name="senha_atual" placeholder="Senha atual" required><br><br>
                    <input type="password" name="nova_senha" placeholder="Nova senha" required><br><br>
                    <input type="password" name="confirmar_senha" placeholder="Confirmar nova senha" required><br><br>
                    <button type="submit" id="bttConfirmar" name="alterar_senha" class="btt-padrao-login">
                        Confirmar Alteração
                    </button>
                </form>
            </div>

            <form method="POST" action="?logout=1">
                <button type="submit" class="btt-sair" id="bttsair">Sair</button>
            </form>

            <form method="POST" onsubmit="return confirmarExclusao();">
                <button type="submit" name="excluir" class="btn btn-excluir" id="btt_excluir_conta">
                    Excluir Conta
                </button>
            </form>
        </div>
    </div>

    <div id="Reservas" class="tabcontent">
        <div class="card">
            <?php
            if (isset($_GET['confirmada']) && $_GET['confirmada'] == 1) {
                echo '<p style="color: green; font-weight: bold;">✔ Reserva confirmada com sucesso!</p>';
            }

            if (isset($_GET['erro']) && $_GET['erro'] == 1) {
                echo '<p style="color: red; font-weight: bold;">Erro ao confirmar a reserva.</p>';
            }
            ?>
            <h3>Reservas</h3>

            <?php if (!empty($reservas)): ?>
                <table class="tabela-reservas">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Pessoas</th>
                            <th>Confirmação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($reservas as $reserva): ?>
                            <tr>
                                <td><?php echo date("d/m/Y", strtotime($reserva['data'])); ?></td>
                                <td><?php echo substr($reserva['hora'], 0, 5); ?></td>
                                <td><?php echo htmlspecialchars($reserva['pessoas']); ?></td>

                                <!-- Coluna de Confirmação -->
                                <td>
                                    <?php
                                    if ($reserva['confirmado']) {
                                        echo '<span style="color:#28a745; font-weight:bold;">✔ Confirmada</span>';
                                    } else {
                                        echo '<span style="color:#ffc107; font-weight:bold;">❌ Pendente</span>';
                                    }
                                    ?>
                                </td>

                                <td class="acao-col">
                                    <form method="POST">
                                        <input type="hidden" name="id_reserva" value="<?php echo $reserva['id']; ?>">

                                        <button class="btn-cancelar" name="cancelar_reserva">
                                            <i class="fa-solid fa-xmark"></i> Cancelar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            <?php else: ?>
                <p>Ainda não há reservas.</p>
            <?php endif; ?>
        </div>
    </div>


    <div id="Favoritos" class="tabcontent">
        <div class="card">
            <h3>Meus Favoritos</h3>
            <p>Ainda não há favoritos.</p>
        </div>
    </div>

    <div id="Pedidos" class="tabcontent">
        <div class="card">
            <h3>Meus Pedidos</h3>
            <p>Ainda não há pedidos.</p>
        </div>
    </div>


    <script src="Js/dashboard.js"></script>
    <script src="Js/alterar_senha.js"></script>

    <script>
        window.onload = function() {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab');
            if (tab) {
                openTab(tab);
            } else {
                openTab('Conta');
            }
        };
    </script>
</body>

</html>