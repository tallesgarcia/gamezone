<?php
session_start();
require_once __DIR__ . './../../config/db.php';

$response = ['count' => 0, 'notificacoes' => []];

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id, mensagem, lida, criada_em 
                            FROM notificacoes 
                            WHERE usuario_id = ? 
                            ORDER BY criada_em DESC 
                            LIMIT 5");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();

    $count = 0;
    $notifs = [];
    while($row = $res->fetch_assoc()) {
        $notifs[] = $row;
        if($row['lida']==0) $count++;
    }

    $response['count'] = $count;
    $response['notificacoes'] = $notifs;
}

header('Content-Type: application/json');
echo json_encode($response);
