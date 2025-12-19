<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$canal_id = intval($_GET['canal_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;

if (!$canal_id || !$user_id) {
    echo json_encode([]);
    exit;
}

// Busca mensagens do canal com usuário e avatar
$stmt = $conn->prepare("
    SELECT m.id, m.mensagem, m.criado_em AS data_envio, u.nome AS usuario_nome, u.avatar
    FROM mensagens_canal m
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE m.canal_id = ?
    ORDER BY m.criado_em ASC
");
$stmt->bind_param("i", $canal_id);
$stmt->execute();
$res = $stmt->get_result();

$mensagens = [];
while ($row = $res->fetch_assoc()) {
    $mensagens[] = [
        'id' => (int)$row['id'],
        'mensagem' => htmlspecialchars($row['mensagem']),
        'data_envio' => $row['data_envio'],
        'usuario_nome' => htmlspecialchars($row['usuario_nome']),
        'avatar' => $row['avatar'] ?: '../../assets/img/default_avatar.png'
    ];
}
$stmt->close();

echo json_encode($mensagens);
