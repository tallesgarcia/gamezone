<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'], $_POST['usuario_id'])) {
    exit("Requisição inválida.");
}

$usuarioId = $_SESSION['user_id'];
$remetenteId = intval($_POST['usuario_id']);

$stmt = $conn->prepare("UPDATE amizades SET status = 'aceito' WHERE usuario_id = ? AND amigo_id = ? AND status = 'pendente'");
$stmt->bind_param("ii", $remetenteId, $usuarioId);
$stmt->execute();

header("Location: pages/comunidade/amigos_pendentes.php");
exit();