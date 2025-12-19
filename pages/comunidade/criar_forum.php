<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["sucesso" => false, "mensagem" => "Você precisa estar logado."]);
    exit;
}

$comunidade_id = intval($_POST['comunidade_id'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

if ($comunidade_id <= 0 || $titulo === '') {
    echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos obrigatórios."]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO foruns (comunidade_id, titulo, descricao) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $comunidade_id, $titulo, $descricao);

if ($stmt->execute()) {
    echo json_encode(["sucesso" => true, "mensagem" => "Fórum criado com sucesso!"]);
} else {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro ao criar fórum."]);
}
$stmt->close();
