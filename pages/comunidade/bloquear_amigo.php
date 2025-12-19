<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'], $_POST['id_amigo'])) {
    exit("Requisição inválida.");
}

$usuarioId = $_SESSION['user_id'];
$amigoId = intval($_POST['id_amigo']);

$stmt = $conn->prepare("UPDATE amizades SET status = 'bloqueado' WHERE (usuario_id = ? AND amigo_id = ?) OR (usuario_id = ? AND amigo_id = ?)");
$stmt->bind_param("iiii", $usuarioId, $amigoId, $amigoId, $usuarioId);
$stmt->execute();

header("Location: pages/comunidade/amigos.php");
exit();