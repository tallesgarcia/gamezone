<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';
session_start();

//Verifica se o usuário é admin
if ($_SESSION['tipo_usuario'] !== 'admin') exit;

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
}

header("Location: ../admin_usuarios.php");
