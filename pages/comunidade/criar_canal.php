<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: security/entrar.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$comunidade_id = intval($_POST['comunidade_id'] ?? 0);
$nome = trim($_POST['nome'] ?? '');

if ($nome == '') {
    die("Nome do canal é obrigatório!");
}

$stmt = $pdo->prepare("INSERT INTO canais (comunidade_id, nome) VALUES (?, ?)");
$stmt->execute([$comunidade_id, $nome]);

header("Location: comunidade.php?id=" . $comunidade_id);
exit;
