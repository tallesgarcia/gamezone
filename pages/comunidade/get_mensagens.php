<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    exit("Acesso negado");
}

$usuario_id = $_SESSION['user_id'];
$amigo_id = isset($_GET['amigo_id']) ? intval($_GET['amigo_id']) : 0;

if ($amigo_id <= 0) {
    exit("Amigo inválido");
}

// Busca as mensagens entre os dois usuários
$stmt = $conn->prepare("
    SELECT m.*, u.nome 
    FROM mensagens m
    JOIN usuarios u ON u.id = m.remetente_id
    WHERE (m.remetente_id = ? AND m.destinatario_id = ?)
       OR (m.remetente_id = ? AND m.destinatario_id = ?)
    ORDER BY m.data_envio ASC
");
$stmt->bind_param("iiii", $usuario_id, $amigo_id, $amigo_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

// Exibe as mensagens
if ($result->num_rows > 0) {
    while ($msg = $result->fetch_assoc()):
?>
        <div class="p-2 my-1 max-w-lg break-words
            <?= $msg['remetente_id'] == $usuario_id 
                ? 'bg-indigo-600 text-white self-end text-right rounded-l-lg rounded-br-lg ml-auto' 
                : 'bg-gray-700 text-white self-start text-left rounded-r-lg rounded-bl-lg mr-auto' ?>">
            
            <div class="text-xs text-gray-300 mb-1">
                <?= htmlspecialchars($msg['nome']) ?> • 
                <?= date('d/m H:i', strtotime($msg['data_envio'])) ?>
            </div>
            
            <div>
                <?= nl2br(htmlspecialchars($msg['mensagem'])) ?>
            </div>
        </div>
<?php
    endwhile;
} else {
    echo "<p class='text-gray-400 text-sm text-center'>Nenhuma mensagem ainda.</p>";
}
?>
