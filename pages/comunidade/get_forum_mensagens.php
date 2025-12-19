<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$forum_id = intval($_GET['forum_id'] ?? 0);

$stmt = $conn->prepare("
    SELECT m.mensagem, m.criado_em, u.nome, u.avatar, u.id AS usuario_id
    FROM forum_mensagens m
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE m.forum_id = ?
    ORDER BY m.criado_em ASC
");
$stmt->bind_param("i", $forum_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='text-gray-400'>Nenhuma mensagem neste fórum.</p>";
} else {
    while ($msg = $result->fetch_assoc()) {
        $nome = htmlspecialchars($msg['nome']);
        $mensagem = htmlspecialchars($msg['mensagem']);
        $hora = date('d/m H:i', strtotime($msg['criado_em']));
        $avatar = htmlspecialchars($msg['avatar'] ?? 'default_avatar.png');
        $classe = ($msg['usuario_id'] == $_SESSION['user_id']) ? "bg-indigo-600 self-end" : "bg-gray-700 self-start";

        echo "
        <div class='flex items-start gap-2 mb-2 {$classe} p-2 rounded max-w-sm'>
            <img src='../../uploads/avatars/{$avatar}' class='w-8 h-8 rounded-full'>
            <div>
                <span class='text-sm font-semibold'>{$nome}</span>
                <p class='text-gray-100 text-sm'>{$mensagem}</p>
                <span class='text-gray-300 text-xs'>{$hora}</span>
            </div>
        </div>";
    }
}
$stmt->close();
