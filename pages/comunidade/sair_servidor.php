<?php
session_start();
require_once __DIR__ . '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: security/entrar.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$comunidade_id = intval($_POST['comunidade_id'] ?? 0);

// Verifica se a comunidade existe
$stmt = $pdo->prepare("SELECT * FROM comunidades WHERE id = ?");
$stmt->execute([$comunidade_id]);
$comunidade = $stmt->fetch();

if (!$comunidade) {
    die("Comunidade não encontrada!");
}

// Remove usuário da comunidade
$stmt = $pdo->prepare("DELETE FROM membros_comunidade WHERE comunidade_id = ? AND usuario_id = ?");
$stmt->execute([$comunidade_id, $user_id]);

// Atualiza contagem de membros na comunidade
$pdo->prepare("UPDATE comunidades SET membros = GREATEST(membros - 1, 0) WHERE id = ?")->execute([$comunidade_id]);

header("Location: minhas_comunidades.php");
exit;
