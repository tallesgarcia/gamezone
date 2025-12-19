<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$comunidade_id = intval($_GET['comunidade_id'] ?? 0);
if ($comunidade_id <= 0) {
    exit("Chat inválido.");
}

$stmt = $conn->prepare("
    SELECT m.mensagem, m.criado_em, u.nome, u.avatar
    FROM mensagens_comunidade m
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE m.comunidade_id = ?
    ORDER BY m.criado_em ASC
");
$stmt->bind_param("i", $comunidade_id);
$stmt->execute();
$result = $stmt->get_result();

while ($msg = $result->fetch_assoc()) {
    $nome = htmlspecialchars($msg['nome']);
    $mensagem = htmlspecialchars($msg['mensagem']);
    $hora = date('H:i', strtotime($msg['criado_em']));
    $avatar = htmlspecialchars($msg['avatar'] ?? 'default_avatar.png');

    echo <<<HTML
    <div class="flex items-start gap-3 mb-2">
        <img src="../../uploads/avatars/{$avatar}" class="w-8 h-8 rounded-full">
        <div>
            <span class="font-semibold text-indigo-400">{$nome}</span>
            <span class="text-xs text-gray-500">{$hora}</span>
            <p class="text-gray-200">{$mensagem}</p>
        </div>
    </div>
HTML;
}
$stmt->close();
