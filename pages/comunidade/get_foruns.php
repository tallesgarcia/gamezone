<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$comunidade_id = intval($_GET['comunidade_id'] ?? 0);
if ($comunidade_id <= 0) {
    echo "<p class='text-gray-400'>Comunidade inválida.</p>";
    exit;
}

$stmt = $conn->prepare("SELECT id, titulo, descricao, criado_em FROM foruns WHERE comunidade_id = ? ORDER BY criado_em DESC");
$stmt->bind_param("i", $comunidade_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='text-gray-400'>Nenhum fórum criado ainda.</p>";
} else {
    while ($forum = $result->fetch_assoc()) {
        $titulo = htmlspecialchars($forum['titulo']);
        $descricao = htmlspecialchars(mb_strimwidth($forum['descricao'] ?? '', 0, 100, '...'));
        $data = date('d/m/Y H:i', strtotime($forum['criado_em']));
        $id = (int)$forum['id'];

        echo <<<HTML
        <div class="bg-gray-700 p-4 rounded shadow hover:bg-gray-600 transition">
            <a href="forum_ver.php?id={$id}" class="block text-lg font-semibold text-indigo-400 hover:underline">{$titulo}</a>
            <p class="text-gray-300 text-sm mt-1">{$descricao}</p>
            <span class="text-gray-500 text-xs">Criado em: {$data}</span>
        </div>
HTML;
    }
}
$stmt->close();
