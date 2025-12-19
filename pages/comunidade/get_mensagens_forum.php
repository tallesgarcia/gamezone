<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$forum_id = intval($_GET['forum_id'] ?? 0);

$stmt = $conn->prepare("SELECT m.mensagem, m.criado_em, u.nome 
                        FROM mensagens_forum m
                        JOIN usuarios u ON u.id = m.usuario_id
                        WHERE m.forum_id = ?
                        ORDER BY m.id ASC");
$stmt->bind_param("i", $forum_id);
$stmt->execute();
$result = $stmt->get_result();

while ($msg = $result->fetch_assoc()):
?>
    <div class="mb-2">
        <span class="font-bold"><?= htmlspecialchars($msg['nome']) ?>:</span>
        <?= htmlspecialchars($msg['mensagem']) ?>
        <span class="text-xs text-gray-400">(<?= $msg['criado_em'] ?>)</span>
    </div>
<?php endwhile; ?>
