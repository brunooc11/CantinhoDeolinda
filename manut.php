<!DOCTYPE html>
<html lang="pt">
<head>
    <title>Site em Manutenção</title>
    <style>
        body { 
            font-family: "Poppins", Arial, sans-serif;
            text-align: center; 
            padding-top: 120px; 
            background: #0d0d0d;
            color: #ffcc33;
        }

        h1 {
            font-size: 40px;
            text-shadow: 0 0 10px rgba(255, 204, 51, 0.3),
                         0 0 20px rgba(255, 204, 51, 0.15);
            margin-bottom: 20px;
        }

        p {
            font-size: 20px;
            color: #e6b84a;
            margin-bottom: 15px;
        }

        /* link igual ao texto — completamente transparente visualmente */
        .admin-inline {
            color: inherit;         /* mesma cor do texto */
            font: inherit;          /* mesmo tipo de letra */
            text-decoration: none;  /* sem underline */
        }
        .admin-inline:hover {
            color: inherit;         /* não muda ao passar o rato */
            text-decoration: none;  /* não aparece nada */
        }
    </style>
</head>
<body>

    <h1>⚠ A Cozinha está em manutenção ⚠</h1>

    <p>Estamos a trabalhar para si.</p>

    <p>🚧 Só <a href="login.php" class="admin-inline">administradores</a> podem aceder. 🚧</p>

</body>
</html>
