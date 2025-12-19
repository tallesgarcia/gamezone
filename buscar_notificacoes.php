<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0, 'notificacoes' => []]);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, mensagem, lida, criada_em FROM notificacoes WHERE usuario_id = ? ORDER BY criada_em DESC LIMIT 5");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($id, $mensagem, $lida, $criada_em);

    $notificacoes = [];
    $count = 0;
    while ($stmt->fetch()) {
        $notificacoes[] = [
            'id' => $id,
            'mensagem' => $mensagem,
            'lida' => $lida,
            'criada_em' => $criada_em
        ];
        if ($lida == 0) $count++;
    }
    $stmt->close();
    echo json_encode(['count' => $count, 'notificacoes' => $notificacoes]);
} else {
    echo json_encode(['count' => 0, 'notificacoes' => []]);
}
