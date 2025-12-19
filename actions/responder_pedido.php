<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'], $_POST['id_usuario'], $_POST['acao'])) {
    header("Location: ./pages/comunidade/amigos_pendentes.php");
    exit;
}

$usuarioLogado = $_SESSION['user_id'];
$idUsuario = intval($_POST['id_usuario']);
$acao = $_POST['acao'];

if ($acao === 'aceitar') {
    $stmt = $conn->prepare("UPDATE amizades SET status = 'aceito' WHERE usuario_id = ? AND amigo_id = ?");
    $stmt->bind_param("ii", $idUsuario, $usuarioLogado);
    $stmt->execute();
} elseif ($acao === 'rejeitar') {
    $stmt = $conn->prepare("DELETE FROM amizades WHERE usuario_id = ? AND amigo_id = ?");
    $stmt->bind_param("ii", $idUsuario, $usuarioLogado);
    $stmt->execute();
}

header("Location: ./pages/comunidade/amigos_pendentes.php");
exit;