<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || empty(trim($_POST['mensagem']))) {
    exit;
}

if (isset($_SESSION['ultima_msg']) && time() - $_SESSION['ultima_msg'] < 5) {
    http_response_code(429); // Too Many Requests
    exit;
}
$_SESSION['ultima_msg'] = time();

$usuario_id = $_SESSION['user_id'];
$mensagem = trim($_POST['mensagem']);

// Limite de tamanho (opcional)
if (strlen($mensagem) > 500) {
    exit;
}

$stmt = $conn->prepare("INSERT INTO chat_global (usuario_id, mensagem) VALUES (?, ?)");
$stmt->bind_param("is", $usuario_id, $mensagem);

if (!$stmt->execute()) {
    http_response_code(500);
    echo "Erro ao enviar mensagem.";
}
?>