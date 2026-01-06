<?php
require("..\config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$env = parse_ini_file("Seguranca/config.env");

$data = json_decode(file_get_contents("php://input"), true);

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $env['SMTP_HOST'];
$mail->SMTPAuth = true;
$mail->Username = $env['SMTP_USER'];
$mail->Password = $env['SMTP_PASS'];
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom($env['SMTP_USER'], "Cantinho Deolinda");
$mail->addReplyTo($data['email'], $data['nome']);
$mail->addAddress($env['SMTP_ADMIN']);

$mail->isHTML(true);
$mail->Subject = "Novo contacto: " . $data['assunto'];
$mail->Body = "Mensagem de {$data['nome']}<br>{$data['mensagem']}";

$mail->send();
