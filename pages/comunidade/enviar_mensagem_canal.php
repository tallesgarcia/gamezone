<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$canal_id = intval($_POST['canal_id'] ?? 0);
$mensagem = trim($_POST['mensagem'] ?? '');

if (!$user_id || !$canal_id || $mensagem === '') {
    http_response_code(400);
    exit;
}

// Verifica se usuário é membro da comunidade do canal
$stmt = $conn->prepare("
    SELECT c.id
    FROM canais ch
    JOIN comunidades c ON ch.comunidade_id = c.id
    JOIN membros_comunidades m ON m.comunidade_id = c.id
    WHERE c.id = ? AND m.usuario_id = ?
");
$stmt->bind_param("ii", $canal_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    http_response_code(403); // Não é membro
    exit;
}
$stmt->close();

// Insere mensagem
$stmt = $conn->prepare("INSERT INTO mensagens_canal (canal_id, usuario_id, mensagem, criado_em) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $canal_id, $user_id, $mensagem);
$stmt->execute();
$stmt->close();

http_response_code(200);
