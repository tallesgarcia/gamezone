<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';
session_start();

// Verifica se o usuário é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Deletar o produto
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Verificar se a tabela ficou vazia
    $result = $conn->query("SELECT COUNT(*) AS total FROM produtos");
    $row = $result->fetch_assoc();

    if ($row['total'] == 0) {
        $conn->query("ALTER TABLE produtos AUTO_INCREMENT = 1");
    }
}

header("Location: ../admin_produtos.php");
exit();
