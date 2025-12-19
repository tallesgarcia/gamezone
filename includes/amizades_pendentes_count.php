<?php
if (!isset($_SESSION)) session_start();

require_once __DIR__ . '/../config/db.php';

$quantidadePendentes = 0;

if (isset($_SESSION['user_id'])) {
    $usuarioId = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM amizades WHERE amigo_id = ? AND status = 'pendente'");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $stmt->bind_result($quantidadePendentes);
    $stmt->fetch();
    $stmt->close();
}
?>
