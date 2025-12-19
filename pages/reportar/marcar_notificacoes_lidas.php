<?php
session_start();
require_once __DIR__ . './../../config/db.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
}
?>
