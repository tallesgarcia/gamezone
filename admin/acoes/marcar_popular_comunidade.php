<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';
session_start();

//Verifica se o usuário é admin
if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE comunidades SET popular = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: ../admin_comunidades.php");
exit();
