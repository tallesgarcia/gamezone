<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id'])){
    http_response_code(403);
    echo json_encode(['error' => 'Usuário não logado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$missao_id = intval($_POST['missao_id'] ?? 0);
if($missao_id <= 0){
    http_response_code(400);
    echo json_encode(['error' => 'Missão inválida']);
    exit;
}

// Buscar valor de XP da missão
$stmt = $conn->prepare("SELECT xp FROM missoes WHERE id = ?");
$stmt->bind_param("i", $missao_id);
$stmt->execute();
$res = $stmt->get_result();
$missao = $res->fetch_assoc();
$xp = $missao['xp'] ?? 0;

// Inserir ou atualizar registro do usuário
$stmt = $conn->prepare("
    INSERT INTO usuario_missoes (usuario_id, missao_id, concluido, concluido_em)
    VALUES (?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE concluido = 1, concluido_em = NOW()
");
$stmt->bind_param("ii", $user_id, $missao_id);
$ok = $stmt->execute();

// Atualizar XP do usuário
if($ok && $xp > 0){
    $stmt2 = $conn->prepare("UPDATE usuarios SET xp = xp + ? WHERE id = ?");
    $stmt2->bind_param("ii", $xp, $user_id);
    $stmt2->execute();
}

echo json_encode(['success' => $ok, 'xp' => $xp]);
?>
