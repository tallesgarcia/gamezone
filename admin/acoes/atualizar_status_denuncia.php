<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Verifica se o usuário é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    http_response_code(403);
    echo "Acesso negado.";
    exit;
}

// Verifica se veio os dados via POST
if (!isset($_POST['id'], $_POST['status'])) {
    http_response_code(400);
    echo "Requisição inválida.";
    exit;
}

$id = intval($_POST['id']);
$status = $_POST['status'];

// Apenas status permitidos
$status_permitidos = ['pendente', 'analisando', 'resolvido'];
if (!in_array($status, $status_permitidos)) {
    http_response_code(400);
    echo "Status inválido.";
    exit;
}

// Atualiza o status da denúncia
$stmt = $conn->prepare("UPDATE denuncias SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo "Status atualizado para: $status";
} else {
    http_response_code(500);
    echo "Erro ao atualizar denúncia.";
}

$stmt->close();
$conn->close();
