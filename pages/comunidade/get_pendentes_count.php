<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$usuarioId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM amizades WHERE amigo_id = ? AND status = 'pendente'");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode(['count' => (int)$result['total']]);
?>

<script>
let pendentesAnteriores = 0;

function atualizarContadorPendentes() {
  fetch("get_pendentes_count.php")
    .then(res => res.json())
    .then(data => {
      const contador = document.getElementById("contador-pendentes");
      const novoValor = data.count;

      if (novoValor > pendentesAnteriores) {
        document.getElementById("notificacao-som").play();
      }

      pendentesAnteriores = novoValor;
      contador.textContent = novoValor;
      contador.style.display = novoValor > 0 ? 'inline-block' : 'none';
    });
}
</script>