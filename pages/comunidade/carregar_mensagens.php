<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['id'], $_GET['id'])) exit;

$usuarioId = $_SESSION['id'];
$amigoId = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT m.*, u.nome 
    FROM mensagens m
    JOIN usuarios u ON m.remetente_id = u.id
    WHERE (remetente_id = ? AND destinatario_id = ?)
       OR (remetente_id = ? AND destinatario_id = ?)
    ORDER BY m.data_envio ASC
");
$stmt->bind_param("iiii", $usuarioId, $amigoId, $amigoId, $usuarioId);
$stmt->execute();
$res = $stmt->get_result();

while ($msg = $res->fetch_assoc()):
    $alinhamento = $msg['remetente_id'] == $usuarioId ? 'text-right text-indigo-300' : 'text-left text-green-300';
    ?>
    <div class="<?= $alinhamento ?>">
        <div class="text-sm"><?= htmlspecialchars($msg['nome']) ?>:</div>
        <div><?= nl2br(htmlspecialchars($msg['conteudo'])) ?></div>
        <div class="text-xs text-gray-400"><?= date('H:i', strtotime($msg['data_envio'])) ?></div>
    </div>
<?php endwhile; ?>
