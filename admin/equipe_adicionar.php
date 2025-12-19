<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
session_start();

// Verifica se o usuário é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $github = $_POST['github_url'] ?? '';
    $linkedin = $_POST['linkedin_url'] ?? '';
    $ordem = $_POST['ordem'] ?? null;
    $foto = $_FILES['foto'] ?? null;

    $nome_foto = null;

    // Upload da imagem
    if ($foto && $foto['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $nome_foto = uniqid('equipe_') . '.' . $extensao;
        $destino = __DIR__ . '/../uploads/equipe/' . $nome_foto;

        if (!is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0777, true);
        }

        if (!move_uploaded_file($foto['tmp_name'], $destino)) {
            $mensagem = "❌ Erro ao salvar a imagem.";
        }
    }

    // Inserção no banco
    $stmt = $conn->prepare("INSERT INTO equipe (nome, cargo, descricao, foto, github_url, linkedin_url, ordem) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $nome, $cargo, $descricao, $nome_foto, $github, $linkedin, $ordem);

    if ($stmt->execute()) {
        header("Location: admin_equipe.php?sucesso=1");
        exit();
    } else {
        $mensagem = "❌ Erro ao adicionar membro.";
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
    <title>Adicionar Membro | Admin| GameZone</title>
      <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="./assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">➕ Adicionar Membro</h1>

        <?php if (!empty($mensagem)): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-600 rounded"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input required name="nome" placeholder="Nome" class="w-full p-2 border rounded" />
            <input required name="cargo" placeholder="Cargo" class="w-full p-2 border rounded" />
            <textarea name="descricao" placeholder="Descrição" class="w-full p-2 border rounded"></textarea>
            <input type="file" name="foto" accept="image/*" class="w-full p-2 border rounded" />
            <input name="github_url" placeholder="GitHub" class="w-full p-2 border rounded" />
            <input name="linkedin_url" placeholder="LinkedIn" class="w-full p-2 border rounded" />
            <input type="number" name="ordem" placeholder="Ordem" class="w-full p-2 border rounded" />

            <div class="flex justify-between">
                <a href="admin_equipe.php" class="text-gray-600 hover:underline">Cancelar</a>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Salvar</button>
            </div>
        </form>
    </div>
</body>
</html>