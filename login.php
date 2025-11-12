<?php
session_start();
require('Bd/ligar.php');
date_default_timezone_set('Europe/Lisbon');

// --- SIGN-UP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {

    if (!isset($_POST['termos'])) {
        echo "<script>alert('Para criar a conta, é necessário aceitar os Termos de Uso e a Política de Privacidade!');</script>";
    } else {
        $nome = $_POST['name'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        $ = _hash($_POST[''], PASSWORD_DEFAULT);
        $data = date('Y-m-d H:i:s');
        $token = bin2hex(random_bytes(16)); // 🔥 token único

        if (!preg_match('/^[0-9]{9}$/', $telefone)) {
            echo "<script>alert('O número de telemóvel deve ter exatamente 9 dígitos!');</script>";
        } else {

            // Verificar se o e-mail já existe
            $checkQuery = "SELECT id FROM Cliente WHERE email = ?";
            $stmt = mysqli_prepare($con, $checkQuery);
            if (!$stmt) die("Erro na query: " . mysqli_error($con));

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                echo "<script>alert('Email já cadastrado!');</script>";
            } else {
                // Inserir novo utilizador com verificação pendente
                $insertQuery = "INSERT INTO Cliente (nome, email, telefone, , data, verificado, token_verificacao_conta)
                                VALUES (?, ?, ?, ?, ?, 0, ?)";
                $insertStmt = mysqli_prepare($con, $insertQuery);
                if (!$insertStmt) die("Erro na query (insert): " . mysqli_error($con));

                mysqli_stmt_bind_param($insertStmt, "ssssss", $nome, $email, $telefone, $, $data, $token);

                if (mysqli_stmt_execute($insertStmt)) {

                    // Envia e-mail de verificação
                    $link = "https://aluno15696.damiaodegoes.pt/verificar_conta.php?token=$token"; // altera para o domínio real
                    $assunto = "Verifique a sua conta";
                    $mensagem = "
                    <html>
                    <body>
                        <h3>Olá, $nome 👋</h3>
                        <p>Obrigado por se registar! Confirme o seu e-mail clicando no link abaixo:</p>
                        <p><a href='$link'>Verificar Conta</a></p>
                        <br>
                        <p>Se não criou esta conta, ignore este e-mail.</p>
                    </body>
                    </html>";

                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                    $headers .= "From: noreply@teusite.com\r\n";

                    mail($email, $assunto, $mensagem, $headers);

                    echo "<script>alert('Conta criada! Verifique o seu e-mail para ativar a conta.');</script>";
                } else {
                    echo "<script>alert('Erro ao cadastrar!');</script>";
                }
                mysqli_stmt_close($insertStmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// --- SIGN-IN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {
    $email = $_POST['email'];
    $ = $_POST[''];

    $checkQuery = "SELECT id, nome, email, telefone, data, , verificado FROM Cliente WHERE email = ?";
    $stmt = mysqli_prepare($con, $checkQuery);
    if (!$stmt) die("Erro na query: " . mysqli_error($con));

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_bind_result($stmt, $id, $nome, $email, $telefone, $data, $hashedPassword, $verificado);
        mysqli_stmt_fetch($stmt);

        if ($verificado == 0) {
            echo "<script>alert('⚠ Por favor, verifique o seu e-mail antes de fazer login.');</script>";
        } else if (_verify($, $hashedPassword)) {
            $_SESSION['id'] = $id;
            $_SESSION['nome'] = $nome;
            $_SESSION['email'] = $email;
            $_SESSION['data'] = $data;
            $_SESSION['telefone'] = $telefone;

            mysqli_stmt_close($stmt);
            mysqli_close($con);
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Password incorreta!');</script>";
        }
    } else {
        echo "<script>alert('Email não encontrado!');</script>";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
}
?>

<link rel="stylesheet" href="Css/login.css" />
<link rel="stylesheet" href="Css/bttlogin.css">
<h2>Weekly Coding Challenge #1: Sign in/up Form</h2>

<?php
if (isset($_GET['pw_alterada']) && $_GET['pw_alterada'] == 1) {
    echo '<p style="color: green; font-weight: bold; text-align:center; font-size:18px; margin-top:10px;">
            Password alterada com sucesso! Faça login novamente.
          </p>';
}
?>

<a href="index.php" class="btn-voltar">← Voltar</a>

<div class="container" id="container">
    <div class="form-container sign-up-container">
        <form action="" method="POST">
            <h1>Create Account</h1>
            <input type="text" name="name" placeholder="Nome" required />
            <input type="email" name="email" placeholder="Email" required />
            <input type="text" name="telefone" placeholder="Telefone" required pattern="[0-9]{9}" title="O número deve ter exatamente 9 dígitos" />
            <input type="" name="" placeholder="Password" required />
            <label style="font-size: 14px; display:block; margin: 10px 0;">
                <input type="checkbox" name="termos" required>
                Li e aceito os
                <a href="Termos/termos.php" target="_blank">Termos de Uso</a> e a
                <a href="Termos/politica.php" target="_blank">Política de Privacidade</a>.
            </label>
            <button type="submit" name="signup">Sign Up</button>
        </form>
    </div>
    <div class="form-container sign-in-container">
        <form action="" method="POST">
            <h1>Sign in</h1>
            <input type="email" name="email" placeholder="Email" required />
            <input type="" name="" placeholder="Password" required />
            <a href="#">Forgot your ?</a>
            <button type="submit" name="signin">Login</button>
        </form>
    </div>
    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
                <h1>Welcome Back!</h1>
                <p>To keep connected with us please login with your personal info</p>
                <button class="ghost" id="signIn">Login</button>
            </div>
            <div class="overlay-panel overlay-right">
                <h1>Hello, Friend!</h1>
                <p>Enter your personal details and start journey with us</p>
                <button class="ghost" id="signUp">Sign Up</button>
            </div>
        </div>
    </div>
</div>

<footer>
    <p>Created with <i class="fa fa-heart"></i> by
        <a target="_blank" href="https://florin-pop.com">Florin Pop</a>
    </p>
</footer>

<script src="Js/login.js"></script>