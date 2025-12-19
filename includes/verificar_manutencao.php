<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Se o usuário for admin, não bloquear
if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin') {
    return;
}

// Verifica se o modo manutenção está ativado
$stmt = $conn->prepare("SELECT nome, valor FROM configuracoes WHERE nome IN ('modo_manutencao', 'mensagem_manutencao')");
$stmt->execute();
$res = $stmt->get_result();

$modo_manutencao = '0';
$mensagem_manutencao = 'Estamos temporariamente em manutenção. Tente novamente em breve.';

while ($row = $res->fetch_assoc()) {
    if ($row['nome'] === 'modo_manutencao') {
        $modo_manutencao = $row['valor'];
    }
    if ($row['nome'] === 'mensagem_manutencao') {
        $mensagem_manutencao = $row['valor'];
    }
}

if ($modo_manutencao === '1') {
    echo "
    <!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <title>Manutenção - GameZone</title>
        <style>
            body {
                background-color: #f9fafb;
                font-family: Arial, sans-serif;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100vh;
                color: #333;
                text-align: center;
            }
            h1 {
                font-size: 2rem;
                color: #4F46E5;
            }
            p {
                max-width: 500px;
                margin-top: 1rem;
            }
        </style>
    </head>
    <body>
        <h1>🔧 Modo Manutenção Ativado</h1>
        <p>" . htmlspecialchars($mensagem_manutencao) . "</p>
    </body>
    </html>";
    exit();
}
?>