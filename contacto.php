<?php
require("config.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $stmt = $con->prepare(
    "INSERT INTO contactos (nome, email, assunto, mensagem)
     VALUES (?, ?, ?, ?)"
  );
  $stmt->bind_param(
    "ssss",
    $_POST['nome'],
    $_POST['email'],
    $_POST['assunto'],
    $_POST['mensagem']
  );
  $stmt->execute();
  $stmt->close();

  echo "OK";
  exit;
}
