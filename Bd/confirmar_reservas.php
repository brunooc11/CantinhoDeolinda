<?php
session_start();
include("ligar.php");

// (mais tarde podes proteger esta página para só o admin aceder)

echo "<h2>Reservas pendentes</h2>";

$sql = "SELECT r.id, c.nome, c.email, r.data_reserva, r.hora_reserva, r.numero_pessoas
        FROM reservas r
        JOIN Cliente c ON r.cliente_id = c.id
        WHERE r.confirmado = 0
        ORDER BY r.data_reserva ASC";

$result = $con->query($sql);

if ($result->num_rows === 0) {
    echo "<p>Não há reservas pendentes.</p>";
}

while ($row = $result->fetch_assoc()) {
    echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>
            <p><strong>Cliente:</strong> {$row['nome']} ({$row['email']})</p>
            <p><strong>Data:</strong> {$row['data_reserva']} às {$row['hora_reserva']}</p>
            <p><strong>Pessoas:</strong> {$row['numero_pessoas']}</p>
            <a href='confirmar_reservas.php?confirmar={$row['id']}'>✅ Confirmar</a> |
            <a href='confirmar_reservas.php?recusar={$row['id']}'>❌ Recusar</a>
          </div>";
}

// Confirma reserva
if (isset($_GET['confirmar'])) {
    $id = $_GET['confirmar'];
    $sql = "UPDATE reservas SET confirmado = 1 WHERE id=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "<script>alert('Reserva confirmada!'); window.location.href='confirmar_reservas.php';</script>";
}

// Recusa reserva
if (isset($_GET['recusar'])) {
    $id = $_GET['recusar'];
    $sql = "DELETE FROM reservas WHERE id=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "<script>alert('Reserva recusada!'); window.location.href='confirmar_reservas.php';</script>";
}
?>
