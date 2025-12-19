<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'], $_POST['id_destino'])) {
    header("Location: ./pages/comunidade/amigos.php");
    exit;
}

$usuarioId = $_SESSION['user_id'];
$idDestino = intval($_POST['id_destino']);

if ($usuarioId === $idDestino) {
    exit("Você não pode adicionar a si mesmo.");
}

$check = $conn->prepare("
    SELECT * FROM amizades 
    WHERE (usuario_id = ? AND amigo_id = ?) OR (usuario_id = ? AND amigo_id = ?)
");
$check->bind_param("iiii", $usuarioId, $idDestino, $idDestino, $usuarioId);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO amizades (usuario_id, amigo_id, status) VALUES (?, ?, 'pendente')");
    $stmt->bind_param("ii", $usuarioId, $idDestino);
    $stmt->execute();
}

header("Location: ./pages/comunidade/amigos.php?buscar=");
