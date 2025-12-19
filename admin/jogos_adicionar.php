<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
session_start();

//Verifica se o usuário é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $genero = trim($_POST['genero']);
    $plataforma = trim($_POST['plataforma']);
    $popular = isset($_POST['popular']) ? 1 : 0;
    $data_lancamento = $_POST['data_lancamento'];
    $imagem_capa = 'assets/img/capa_padrao.jpg'; // fallback

    // Processa upload de imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($extensao, $permitidas) && $_FILES['imagem']['size'] <= 2 * 1024 * 1024) {
            if (!is_dir('../uploads/jogos')) {
                mkdir('../uploads/jogos', 0755, true);
            }
            $novo_nome = uniqid('jogo_') . '.' . $extensao;
            $caminho = '../uploads/jogos/' . $novo_nome;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho)) {
                $imagem_capa = 'uploads/jogos/' . $novo_nome;
            }
        }
    }

    if ($nome && $genero && $plataforma && $data_lancamento) {
        $stmt = $conn->prepare("INSERT INTO jogos (nome, genero, plataforma, data_lancamento, imagem_capa, popular) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $nome, $genero, $plataforma, $data_lancamento, $imagem_capa, $popular);
        $stmt->execute();
        header("Location: admin_jogos.php?status=adicionado");
        exit();
    } else {
        $mensagem = "Preencha todos os campos obrigatórios.";
    }
}


// ==============================
// NOTIFICAÇÕES
// ==============================
$notificacoes = [];
$notifCount = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id, mensagem, lida, criada_em FROM notificacoes WHERE usuario_id = ? ORDER BY criada_em DESC LIMIT 5");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($nid, $nmsg, $nlida, $ncriada);
        while ($stmt->fetch()) {
            $notificacoes[] = ['id'=>$nid,'mensagem'=>$nmsg,'lida'=>$nlida,'criada_em'=>$ncriada];
            if ($nlida==0) $notifCount++;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Jogo - Admin | GameZone</title>
    <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="./assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-800 p-6">
  <main class="max-w-2xl mx-auto mt-20 bg-white p-6 shadow rounded">
    <h1 class="text-2xl font-bold mb-6">🎮 Adicionar Novo Jogo</h1>

    <?php if (!empty($mensagem)): ?>
      <div class="mb-4 p-3 bg-red-100 text-red-600 rounded"><?= $mensagem ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <input type="text" name="nome" required placeholder="Nome do jogo" class="w-full p-2 border rounded">
      <input type="text" name="genero" required placeholder="Gênero" class="w-full p-2 border rounded">
      <input type="text" name="plataforma" required placeholder="Plataforma" class="w-full p-2 border rounded">
      <input type="date" name="data_lancamento" required class="w-full p-2 border rounded">

      <div>
        <label class="block text-sm text-gray-600 mb-1">Capa (JPG, PNG, WebP até 2MB)</label>
        <input type="file" name="imagem" accept="image/*" class="w-full p-2 border rounded">
      </div>

      <div class="flex items-center gap-2">
        <input type="checkbox" name="popular" id="popular">
        <label for="popular" class="text-sm text-gray-700">Marcar como popular</label>
      </div>

      <div class="flex justify-end">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded text-sm">Salvar Jogo</button>
      </div>
    </form>
  </main>
</body>
</html>
