<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: pages/security/entrar.php");
    exit;
}

$id_denunciante = $_SESSION['id_usuario'];
$id_denunciado = $_POST['id_denunciado'] ?? null;
$motivo = trim($_POST['motivo'] ?? '');
$detalhes = trim($_POST['detalhes'] ?? '');

if (!$id_denunciado || !$motivo) {
    die("Dados incompletos.");
}

$stmt = $conn->prepare("INSERT INTO denuncias (id_denunciante, id_denunciado, motivo, detalhes) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $id_denunciante, $id_denunciado, $motivo, $detalhes);

if ($stmt->execute()) {
    echo "<script>alert('Denúncia enviada com sucesso.'); window.location.href = 'index.php';</script>";
} else {
    echo "Erro ao enviar denúncia.";
}
?>
