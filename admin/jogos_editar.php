<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
session_start();


//Verifica se o usuário é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) { 
    header("Location: admin_jogos.php");
    exit();
}

// Consulta dados do jogo
$stmt = $conn->prepare("SELECT * FROM jogos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$jogo = $stmt->get_result()->fetch_assoc();

if (!$jogo) {
    echo "Jogo não encontrado.";
    exit();
}

$mensagem = "";
$imagem_capa = $jogo['imagem_capa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $genero = trim($_POST['genero']);
    $plataforma = trim($_POST['plataforma']);
    $popular = isset($_POST['popular']) ? 1 : 0;
    $data_lancamento = $_POST['data_lancamento'];

    // Upload de imagem nova (se houver)
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $permitidas) && $_FILES['imagem']['size'] <= 2 * 1024 * 1024) {
            if (!is_dir('../uploads/jogos')) {
                mkdir('../uploads/jogos', 0755, true);
            }

            $nome_arquivo = uniqid('jogo_') . '.' . $ext;
            $caminho = '../uploads/jogos/' . $nome_arquivo;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho)) {
                // Apaga imagem antiga, se não for a padrão
                if (!empty($jogo['imagem_capa']) && file_exists('../' . $jogo['imagem_capa']) && $jogo['imagem_capa'] !== 'assets/img/capa_padrao.jpg') {
                    unlink('../' . $jogo['imagem_capa']);
                }
                $imagem_capa = 'uploads/jogos/' . $nome_arquivo;
            }
        }
    }

    if ($nome && $genero && $plataforma && $data_lancamento) {
        $stmt = $conn->prepare("UPDATE jogos SET nome=?, genero=?, plataforma=?, data_lancamento=?, imagem_capa=?, popular=? WHERE id=?");
        $stmt->bind_param("ssssssi", $nome, $genero, $plataforma, $data_lancamento, $imagem_capa, $popular, $id);
        $stmt->execute();

        header("Location: admin_jogos.php?status=atualizado");
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
    <title>Editar Jogo - Admin | GameZone</title>
      <script src="https://cdn.tailwindcss.com"></script>
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <link rel="stylesheet" href="./assets/css/estilos.css">
      <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-800 p-6">
<main class="max-w-2xl mx-auto mt-20 bg-white p-6 shadow rounded">
    <h1 class="text-2xl font-bold mb-6">✏️ Editar Jogo</h1>

    <?php if (!empty($mensagem)): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-600 rounded"><?= $mensagem ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="text" name="nome" value="<?= htmlspecialchars($jogo['nome']) ?>" required class="w-full p-2 border rounded">
        <input type="text" name="genero" value="<?= htmlspecialchars($jogo['genero']) ?>" required class="w-full p-2 border rounded">
        <input type="text" name="plataforma" value="<?= htmlspecialchars($jogo['plataforma']) ?>" required class="w-full p-2 border rounded">
        <input type="date" name="data_lancamento" value="<?= htmlspecialchars($jogo['data_lancamento']) ?>" required class="w-full p-2 border rounded">

        <div>
            <label class="block text-sm text-gray-600 mb-1">Capa Atual:</label>
            <img src="../<?= $jogo['imagem_capa'] ?: 'assets/img/capa_padrao.jpg' ?>" class="w-32 rounded border mb-2">
            <input type="file" name="imagem" accept="image/*" class="w-full p-2 border rounded">
            <p class="text-xs text-gray-500 mt-1">Formatos aceitos: JPG, PNG, WebP (máx. 2MB)</p>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="popular" id="popular" <?= $jogo['popular'] ? 'checked' : '' ?>>
            <label for="popular" class="text-sm text-gray-700">Marcar como popular</label>
        </div>

        <div class="flex justify-between">
            <a href="admin_jogos.php" class="text-gray-600 hover:underline">Cancelar</a>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded text-sm">Salvar Alterações</button>
        </div>
    </form>
</main>
</body>
</html>