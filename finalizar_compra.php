<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config/db.php';
session_start();

// ======================
// Validações iniciais
// ======================
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/security/entrar.php");
    exit;
}

if (empty($_SESSION['carrinho'])) {
    header("Location: loja.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$itens = $_SESSION['carrinho'];

// Defina a duração padrão das assinaturas em dias
$assinatura_duracao = 30;

// ======================
// Inserção no banco
// ======================
foreach ($itens as $item) {
    if ($item['tipo'] === 'assinatura') {
        $sql = "INSERT INTO assinaturas (usuario_id, produto_id, data_inicio, data_fim, status)
                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), 'ativa')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) die("Erro no prepare de assinatura: " . $conn->error);
        $stmt->bind_param("iii", $user_id, $item['id'], $assinatura_duracao);
    } else {
        $sql = "INSERT INTO compras (usuario_id, produto_id, data_compra) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) die("Erro no prepare de compra: " . $conn->error);
        $stmt->bind_param("ii", $user_id, $item['id']);
    }

    if (!$stmt->execute()) die("Erro no execute: " . $stmt->error);
    $stmt->close();
}

// ======================
// Limpa carrinho
// ======================
$_SESSION['carrinho'] = [];

// ======================
// Redireciona para página de pagamento
// ======================
// Você pode passar parâmetros como user_id, valor total ou IDs das compras
$total = array_sum(array_column($itens, 'preco'));
header("Location: pagamento.php?usuario=$user_id&total=$total");
exit;
?>
