<?php
session_start();
require_once __DIR__ . './../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$q = "%" . ($_GET['q'] ?? '') . "%";

$sql = "SELECT DISTINCT u.id, u.nome
        FROM amizades a
        JOIN usuarios u ON (u.id = a.amigo_id OR u.id = a.usuario_id)
        WHERE (a.usuario_id = ? OR a.amigo_id = ?)
          AND a.status = 'aceito'
          AND u.id != ?
          AND u.nome LIKE ?
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiis", $usuario_id, $usuario_id, $usuario_id, $q);
$stmt->execute();
$result = $stmt->get_result();

$amigos = [];
while ($row = $result->fetch_assoc()) {
    $amigos[] = $row;
}

echo json_encode($amigos);
