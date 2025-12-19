<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/security/entrar.php");
    exit;
}

$usuarioId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amigo_id'])) {
    $amigoId = (int) $_POST['amigo_id'];

    if ($amigoId === $usuarioId) {
        // Não pode adicionar a si mesmo
        header("Location: ../../index.php?erro=voce_nao_pode_se_adicionar");
        exit;
    }

    // Verifica se já existe amizade ou pedido pendente
    $stmt = $conn->prepare("
        SELECT * FROM amizades 
        WHERE (usuario_id = ? AND amigo_id = ?) 
           OR (usuario_id = ? AND amigo_id = ?)
    ");
    $stmt->bind_param("iiii", $usuarioId, $amigoId, $amigoId, $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Já existe amizade ou pedido
        header("Location: ../../index.php?erro=ja_enviado");
        exit;
    }

    // Insere pedido de amizade
    $stmt = $conn->prepare("
        INSERT INTO amizades (usuario_id, amigo_id, status, criado_em)
        VALUES (?, ?, 'pendente', NOW())
    ");
    $stmt->bind_param("ii", $usuarioId, $amigoId);
    if ($stmt->execute()) {
        echo "Sucesso!";
        exit;
    } else {
        echo "Erro ao enviar!";
        exit;
    }
} else {
    echo "Erro: Dados Inválidos!";
    exit;
}
