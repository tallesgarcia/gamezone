<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/db.php';
session_start();


// Verifica se é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Busca membro pelo ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: admin_equipe.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM equipe WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$membro = $stmt->get_result()->fetch_assoc();

if (!$membro) {
    echo "Membro não encontrado.";
    exit();
}

$mensagem = "";
$toast = false;

// Processa envio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $cargo = $_POST['cargo'];
    $descricao = $_POST['descricao'];
    $github_url = $_POST['github_url'];
    $linkedin_url = $_POST['linkedin_url'];
    $ordem = $_POST['ordem'] ?? 0;
    $foto_url = $membro['foto_url'];
    $removerImagem = isset($_POST['remover_foto']) && $_POST['remover_foto'] === '1';

    // Remove imagem se solicitado
    if ($removerImagem && $foto_url && file_exists("../" . $foto_url)) {
        unlink("../" . $foto_url);
        $foto_url = '';
    }

    // Verifica nova imagem
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $tamanhoMax = 2 * 1024 * 1024;

        if (!in_array($ext, $permitidas)) {
            $mensagem = "Formato de imagem inválido.";
        } elseif ($_FILES['foto']['size'] > $tamanhoMax) {
            $mensagem = "Imagem excede o limite de 2MB.";
        } else {
            $pasta = "../uploads/equipe/";
            if (!file_exists($pasta)) {
                mkdir($pasta, 0755, true);
            }

            $novo_arquivo = uniqid() . "." . $ext;
            $destino = $pasta . $novo_arquivo;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                if (!empty($foto_url) && file_exists("../" . $foto_url)) {
                    unlink("../" . $foto_url);
                }

                $foto_url = "uploads/equipe/" . $novo_arquivo;
            } else {
                $mensagem = "Erro ao salvar a imagem.";
            }
        }
    }

    if (empty($mensagem)) {
        $stmt = $conn->prepare("UPDATE equipe SET nome=?, cargo=?, descricao=?, foto_url=?, github_url=?, linkedin_url=?, ordem=? WHERE id=?");
        $stmt->bind_param("ssssssii", $nome, $cargo, $descricao, $foto_url, $github_url, $linkedin_url, $ordem, $id);
        $stmt->execute();

        $toast = true;
        $membro['foto_url'] = $foto_url; // Atualiza localmente
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
  <title>Editar Membro | GameZone</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="./assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">

<?php if ($toast): ?>
  <div id="toast" class="fixed top-6 right-6 bg-green-600 text-white px-4 py-2 rounded shadow-lg z-50">
    ✅ Alterações salvas com sucesso!
  </div>
  <script>
    setTimeout(() => document.getElementById("toast").remove(), 3000);
  </script>
<?php endif; ?>

<div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
  <h1 class="text-2xl font-bold mb-4">✏️ Editar Membro</h1>

  <?php if (!empty($mensagem)): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-600 rounded"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="space-y-4">
    <input required name="nome" value="<?= htmlspecialchars($membro['nome']) ?>" class="w-full p-2 border rounded" />
    <input required name="cargo" value="<?= htmlspecialchars($membro['cargo']) ?>" class="w-full p-2 border rounded" />
    <textarea name="descricao" class="w-full p-2 border rounded"><?= htmlspecialchars($membro['descricao']) ?></textarea>

    <div>
      <label class="block text-sm mb-1">Foto Atual:</label>
      <img id="previewImagem" src="../<?= $membro['foto_url'] ?: 'assets/img/placeholder.png' ?>" class="w-20 h-20 rounded-full mb-2 object-cover" />
      <input type="file" name="foto" accept="image/*" class="w-full p-2 border rounded" onchange="previewNovaImagem(event)" />
      <div class="flex items-center mt-1">
        <input type="checkbox" name="remover_foto" id="remover_foto" value="1" class="mr-2" />
        <label for="remover_foto" class="text-sm text-gray-600">Remover imagem atual</label>
      </div>
      <p class="text-xs text-gray-500">Formatos permitidos: jpg, png, webp. Máximo 2MB.</p>
    </div>

    <input name="github_url" value="<?= htmlspecialchars($membro['github_url']) ?>" class="w-full p-2 border rounded" placeholder="GitHub" />
    <input name="linkedin_url" value="<?= htmlspecialchars($membro['linkedin_url']) ?>" class="w-full p-2 border rounded" placeholder="LinkedIn" />
    <input type="number" name="ordem" value="<?= $membro['ordem'] ?>" class="w-full p-2 border rounded" placeholder="Ordem" />

    <div class="flex justify-between">
      <a href="admin_equipe.php" class="text-gray-600 hover:underline">Cancelar</a>
      <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Salvar Alterações</button>
    </div>
  </form>
</div>

<script>
function previewNovaImagem(event) {
  const imagem = document.getElementById("previewImagem");
  imagem.src = URL.createObjectURL(event.target.files[0]);
}
</script>
</body>
</html>