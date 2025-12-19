<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    http_response_code(403);
    exit("Acesso negado.");
}

$conn->query("DELETE FROM mensagens_comunidade");

if ($conn->error) {
    http_response_code(500);
    echo "Erro ao limpar chat.";
} else {
    echo "Chat limpo com sucesso.";
}
?>