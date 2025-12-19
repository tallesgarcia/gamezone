<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
header("Content-Type: text/plain");

if (!isset($_SESSION['user_id'])) {
    echo "erro:not_logged";
    exit;
}

$user_id = $_SESSION['user_id'];
$forum_id = intval($_POST['forum_id'] ?? 0);
$mensagem = trim($_POST['mensagem'] ?? '');

if ($forum_id <= 0 || $mensagem === '') {
    echo "erro:invalid_data";
    exit;
}

// Insere mensagem no fórum
$stmt = $conn->prepare("INSERT INTO forum_mensagens (forum_id, usuario_id, mensagem, criado_em) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $forum_id, $user_id, $mensagem);

if ($stmt->execute()) {
    echo "ok";
} else {
    echo "erro:db - " . $stmt->error;
}

$stmt->close();
$conn->close();
