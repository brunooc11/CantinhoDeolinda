<?php
session_start();
require('Bd/ligar.php');
//require("config.php");
date_default_timezone_set('Europe/Lisbon');

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require("phpmailer/src/PHPMailer.php");
require("phpmailer/src/SMTP.php");
require("phpmailer/src/Exception.php");

$env = parse_ini_file("Seguranca/config.env");

// --- SIGN-UP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {

    if (!isset($_POST['termos'])) {

        echo "<script>alert('Para criar a conta, é necessário aceitar os Termos de Uso e a Política de Privacidade!');</script>";
    } else {

        $nome     = $_POST['name'];
        $email    = $_POST['email'];
        $codigo_pais = $_POST['codigo_pais'] ?? '';
        $telefone_local = $_POST['telefone'] ?? '';
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $data     = date('Y-m-d H:i:s');
        $token    = bin2hex(random_bytes(16)); // 🔥 token único

        $codigo_pais = preg_replace('/\D+/', '', $codigo_pais);
        $telefone_local = preg_replace('/\D+/', '', $telefone_local);
        $telefone_completo = $codigo_pais . $telefone_local;
        $regras_telefone = [
            '351' => ['min' => 9, 'max' => 9],
            '34' => ['min' => 9, 'max' => 9],
            '33' => ['min' => 9, 'max' => 9],
            '49' => ['min' => 10, 'max' => 11],
            '44' => ['min' => 10, 'max' => 10],
            '1' => ['min' => 10, 'max' => 10],
            '55' => ['min' => 10, 'max' => 11],
            '244' => ['min' => 9, 'max' => 9],
            '258' => ['min' => 9, 'max' => 9],
            '238' => ['min' => 7, 'max' => 7],
        ];
        $regra = $regras_telefone[$codigo_pais] ?? ['min' => 4, 'max' => 14];
        $min_local = $regra['min'];
        $max_local = $regra['max'];

        if (!preg_match('/^\d{1,4}$/', $codigo_pais)) {

            echo "<script>alert('Indicativo de pais invalido.');</script>";
        } elseif (!preg_match('/^\d+$/', $telefone_local)) {

            echo "<script>alert('Numero de telefone invalido.');</script>";
        } elseif (strlen($telefone_local) < $min_local || strlen($telefone_local) > $max_local) {

            echo "<script>alert('Numero local invalido para este pais. Deve ter entre {$min_local} e {$max_local} digitos.');</script>";
        } elseif (strlen($telefone_completo) < 8 || strlen($telefone_completo) > 15) {

            echo "<script>alert('Telefone invalido (deve ter entre 8 e 15 digitos no total).');</script>";
        } else {
            $telefone = '+' . $telefone_completo;

            // Verificar se o e-mail já existe
            $checkQuery = "SELECT id FROM Cliente WHERE email = ?";
            $stmt = mysqli_prepare($con, $checkQuery);
            if (!$stmt) {
                die("Erro na query: " . mysqli_error($con));
            }

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {

                echo "<script>alert('Email já cadastrado!');</script>";
            } else {

                // Inserir novo utilizador com verificação pendente
                $insertQuery = "
                    INSERT INTO Cliente 
                    (nome, email, telefone, password, data, verificado, token_verificacao_conta, permissoes, estado)
                    VALUES (?, ?, ?, ?, ?, 0, ?, 'cliente', 1)
                ";

                $insertStmt = mysqli_prepare($con, $insertQuery);
                if (!$insertStmt) {
                    die("Erro na query (insert): " . mysqli_error($con));
                }

                mysqli_stmt_bind_param(
                    $insertStmt,
                    "ssssss",
                    $nome,
                    $email,
                    $telefone,
                    $password,
                    $data,
                    $token
                );

                if (mysqli_stmt_execute($insertStmt)) {

                    // Envia e-mail de verificação
                    $link = "https://aluno15696.damiaodegoes.pt/verificar_conta.php?token=$token";
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
                        </html>
                    ";

                    $mail = new PHPMailer(true);

                    /*
                    $mail->SMTPDebug = 2;
                    $mail->Debugoutput = 'html';
                    */

                    $mail->isSMTP();
                    $mail->Host       = $env['SMTP_HOST'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $env['SMTP_USER'];
                    $mail->Password   = $env['SMTP_PASS'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom($env['SMTP_FROM'], $env['SMTP_FROM_NAME']);
                    $mail->addAddress($email, $nome);

                    $mail->isHTML(true);
                    $mail->Subject = $assunto;
                    $mail->Body    = $mensagem;
                    $mail->AltBody = 'Confirme a sua conta através do link enviado por email.';

                    if ($mail->send()) {
                        echo "<script>alert('Conta criada! Verifique o seu e-mail para ativar a conta.');</script>";
                    } else {
                        echo "<script>alert('Erro ao enviar email: {$mail->ErrorInfo}');</script>";
                    }

                    mysqli_stmt_close($insertStmt);
                }
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// --- SIGN-IN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {

    $email    = $_POST['email'];
    $password = $_POST['password'];

    $checkQuery = "
        SELECT id, nome, email, telefone, data, password, verificado, permissoes, estado
        FROM Cliente
        WHERE email = ?
    ";

    $stmt = mysqli_prepare($con, $checkQuery);
    if (!$stmt) {
        die("Erro na query: " . mysqli_error($con));
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {

        mysqli_stmt_bind_result(
            $stmt,
            $id,
            $nome,
            $email,
            $telefone,
            $data,
            $hashedPassword,
            $verificado,
            $permissoes,
            $estado
        );

        mysqli_stmt_fetch($stmt);

        if ($estado == 0) {

            echo "<script>
                alert('A sua conta está bloqueada pelo administrador.');
                window.location.href = 'index.php';
            </script>";
            exit();
        }

        if ($verificado == 0) {

            echo "<script>alert('⚠ Por favor, verifique o seu e-mail antes de fazer login.');</script>";
        } elseif (!is_string($hashedPassword)) {

            echo "<script>alert('Erro na password da conta.');</script>";
        } elseif (password_verify($password, $hashedPassword)) {

            $_SESSION['id']         = $id;
            $_SESSION['nome']       = $nome;
            $_SESSION['email']      = $email;
            $_SESSION['data']       = $data;
            $_SESSION['telefone']   = $telefone;
            $_SESSION['permissoes'] = $permissoes;

            mysqli_stmt_close($stmt);
            mysqli_close($con);

            if ($permissoes === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
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
<link rel="stylesheet" href="Css/login.css">
<link rel="stylesheet" href="Css/bttlogin.css">

<?php
if (isset($_GET['pw_alterada']) && $_GET['pw_alterada'] == 1) {
    echo '
        <p style="color: green; font-weight: bold; text-align:center; font-size:18px; margin-top:10px;">
            Password alterada com sucesso! Faça login novamente.
        </p>
    ';
}
?>

<a href="index.php" class="btn-voltar">← Voltar</a>

<div class="container" id="container">

    <div class="form-container sign-up-container">
        <form action="" method="POST">
            <h1>Criar Conta</h1>

            <input type="text" name="name" placeholder="Nome" required>
            <input type="email" name="email" placeholder="Email" required>
            <div class="phone-country-row">
                <div class="country-code-box">
                    <img id="countryFlag" class="country-flag" src="https://flagcdn.com/w20/un.png" alt="Selecionar pais">
                    <input
                        type="text"
                        id="codigoPaisInput"
                        name="codigo_pais"
                        placeholder="+351"
                        maxlength="5"
                        autocomplete="off"
                        pattern="\\+[0-9]{1,4}"
                        title="Indicativo no formato +351"
                        required>
                </div>
                <input
                    type="text"
                    id="telefoneInput"
                    name="telefone"
                    placeholder="Numero de telemovel"
                    pattern="[0-9]{4,14}"
                    title="Introduza apenas digitos do numero local (4 a 14)"
                    required>
            </div>
            <div class="password-wrapper">
                <input type="password" id="signupPassword" name="password" placeholder="Password" required>

                <button type="button"
                    class="toggle-pass"
                    data-target="signupPassword"
                    aria-label="Mostrar password">

                    <!-- olho aberto -->
                    <svg class="eye-open" viewBox="0 0 24 24">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="12" cy="12" r="3.5"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                    </svg>

                    <!-- olho fechado -->
                    <svg class="eye-closed" viewBox="0 0 24 24" style="display:none">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="12" cy="12" r="3.5"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <line x1="3" y1="3" x2="21" y2="21"
                            stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" />
                    </svg>

                </button>

            </div>


            <div class="terms">
                <label>
                    <input type="checkbox" name="termos" required>
                    <span>
                        Li e aceito os
                        <a href="Recursos/termos.php">Termos de Uso</a>
                        e a
                        <a href="Recursos/politica.php">Política de Privacidade</a>.
                    </span>
                </label>
            </div>


            <button type="submit" name="signup">Sign Up</button>
        </form>
    </div>

    <div class="form-container sign-in-container">
        <form action="" method="POST">
            <h1>Iniciar Sessão</h1>

            <input type="email" name="email" placeholder="Email" required>
            <div class="password-wrapper">
                <input type="password" id="signinPassword" name="password" placeholder="Password" required>

                <button type="button"
                    class="toggle-pass"
                    data-target="signinPassword"
                    aria-label="Mostrar password">

                    <!-- olho aberto -->
                    <svg class="eye-open" viewBox="0 0 24 24">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="12" cy="12" r="3.5"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                    </svg>

                    <!-- olho fechado -->
                    <svg class="eye-closed" viewBox="0 0 24 24" style="display:none">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="12" cy="12" r="3.5"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <line x1="3" y1="3" x2="21" y2="21"
                            stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" />
                    </svg>

                </button>

            </div>


            <a href="recuperacao/forgot_password.php">Esqueci-me da palavra-passe?</a>

            <button type="submit" name="signin">Entrar</button>
        </form>
    </div>

    <div class="overlay-container">
        <div class="overlay">

            <div class="overlay-panel overlay-left">
                <h1>Olá Novamente!</h1>
                <p>Para se manter ligado a nós, inicie sessão com os seus dados pessoais.</p>
                <button class="ghost" id="signIn">Entrar</button>
            </div>

            <div class="overlay-panel overlay-right">
                <h1>Bem-vindo!</h1>
                <p>Introduza os seus dados pessoais e comece a sua jornada connosco.</p>
                <button class="ghost" id="signUp">Criar conta</button>
            </div>

        </div>
    </div>

</div>

<footer>
    <p>© 2025 Cantinho Deolinda — Todos os direitos reservados</p>
</footer>

<!-- defer garante que o JavaScript só executa depois do HTML estar totalmente carregado -->
<script src="Js/login.js" defer></script>
