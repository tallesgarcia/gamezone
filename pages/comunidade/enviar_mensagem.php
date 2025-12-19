<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Proibido
    exit("Usuário não autenticado");
}

$remetente = (int) $_SESSION['user_id'];
$amigo_id = isset($_POST['amigo_id']) ? (int) $_POST['amigo_id'] : 0;
$mensagem = trim($_POST['mensagem'] ?? '');

if ($amigo_id > 0 && $mensagem !== '') {
    $stmt = $conn->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, mensagem) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $remetente, $amigo_id, $mensagem);

    if ($stmt->execute()) {
        echo "ok"; // resposta simples para o AJAX
    } else {
        http_response_code(500);
        echo "Erro ao enviar mensagem.";
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo "Dados inválidos.";
}

$conn->close();
?>
