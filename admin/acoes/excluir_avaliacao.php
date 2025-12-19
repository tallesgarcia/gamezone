<?php
session_start();
require_once __DIR__ . '/../../config/db.php';


//Verifica se o usuário é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    die("Acesso negado.");
}

if (isset($_POST['id'])) {
    $stmt = $conn->prepare("DELETE FROM avaliacoes WHERE id = ?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
}

header("Location: ../admin_avaliacoes.php");
exit;
