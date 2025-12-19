<?php
require_once __DIR__ . '/../../config/db.php';

$query = "SELECT c.mensagem, c.data_envio, u.nome 
          FROM chat_global c 
          JOIN usuarios u ON c.usuario_id = u.id 
          ORDER BY c.data_envio DESC 
          LIMIT 50";

$result = $conn->query($query);

if (!$result) {
    echo "<div class='mensagem'>Erro ao carregar mensagens: " . htmlspecialchars($conn->error) . "</div>";
    exit;
}

$mensagens = [];
while ($row = $result->fetch_assoc()) {
    $nome = htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8');
    $mensagem = htmlspecialchars($row['mensagem'], ENT_QUOTES, 'UTF-8');
    $mensagens[] = "<div class='mensagem mb-2'><strong class='text-indigo-400'>{$nome}:</strong> {$mensagem}</div>";
}

echo implode("", array_reverse($mensagens));
