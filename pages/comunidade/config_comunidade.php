<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Verifica login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../security/entrar.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$comunidade_id = intval($_GET['id'] ?? 0);

// Busca comunidade
$stmt = $conn->prepare("SELECT * FROM comunidades WHERE id = ?");
$stmt->bind_param("i", $comunidade_id);
$stmt->execute();
$result = $stmt->get_result();
$comunidade = $result->fetch_assoc();
$stmt->close();

if (!$comunidade) {
    die("Comunidade não encontrada.");
}

// Permissão
if ($comunidade['dono_id'] != $user_id) {
    die("Você não tem permissão para editar esta comunidade.");
}

$mensagem = "";

// Atualização (Lógica de POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    
    // Verifica se houve upload de nova imagem
    $novoIcone = null;
    if (isset($_FILES['icone']) && $_FILES['icone']['error'] === UPLOAD_ERR_OK) {
        $novoIcone = file_get_contents($_FILES['icone']['tmp_name']);
    }

    if ($novoIcone !== null) {
        // Se tem imagem nova, atualiza tudo + imagem (b = blob)
        // Nota: Para BLOBs grandes, usamos null no bind e send_long_data
        $stmt = $conn->prepare("UPDATE comunidades SET nome = ?, descricao = ?, icone = ? WHERE id = ?");
        $null = null; // Placeholder para o blob
        $stmt->bind_param("ssbi", $nome, $descricao, $null, $comunidade_id);
        $stmt->send_long_data(2, $novoIcone); // Envia os dados binários para o 3º parâmetro (índice 2)
    } else {
        // Se não tem imagem nova, mantém a antiga (não altera a coluna icone)
        $stmt = $conn->prepare("UPDATE comunidades SET nome = ?, descricao = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nome, $descricao, $comunidade_id);
    }

    if ($stmt->execute()) {
        $mensagem = "Configurações atualizadas com sucesso!";
        // Atualiza os dados da variável $comunidade para exibir na página imediatamente
        $comunidade['nome'] = $nome;
        $comunidade['descricao'] = $descricao;
        if ($novoIcone !== null) {
            $comunidade['icone'] = $novoIcone;
        }
    } else {
        $mensagem = "Erro ao atualizar configurações: " . $stmt->error;
    }
    $stmt->close();
}


// NOTIFICAÇÕES
$notificacoes = [];
$notifCount = 0;

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id, mensagem, lida, criada_em FROM notificacoes 
                            WHERE usuario_id = ? ORDER BY criada_em DESC LIMIT 5");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($nid, $nmsg, $nlida, $ncriada);

    while ($stmt->fetch()) {
        $notificacoes[] = ['id'=>$nid, 'mensagem'=>$nmsg, 'lida'=>$nlida, 'criada_em'=>$ncriada];
        if ($nlida == 0) $notifCount++;
    }
    $stmt->close();
}

// =====================================================
// LÓGICA CORRIGIDA PARA EXIBIR O ÍCONE (BLOB -> BASE64)
// =====================================================
$iconeSrc = "https://via.placeholder.com/150"; // Imagem padrão

if (!empty($comunidade['icone'])) {
    // 1. Detecta o tipo MIME real do binário (png, jpeg, gif, etc.)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($comunidade['icone']);
    
    // Fallback se não detectar
    if (!$mimeType) $mimeType = 'image/jpeg';

    // 2. Converte para Base64
    $base64 = base64_encode($comunidade['icone']);
    
    // 3. Monta a string Data URI correta
    $iconeSrc = "data:$mimeType;base64,$base64";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Configurações da Comunidade</title>
<script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">

  <div class="flex items-center gap-6">
    <a href="../../index.php" class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
      Game<span class="text-gray-800 dark:text-gray-100">Zone</span>
    </a>

    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="../../index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>

      <div class="relative group">
        <button type="button" class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>

        <ul class="absolute hidden group-hover:block bg-white dark:bg-gray-800 shadow rounded mt-1 p-2 w-44 z-50">
          <li><a href="../minhas_comunidades.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Minhas Comunidades</a></li>
          <li><a href="chat.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Chat</a></li>
          <li><a href="amigos.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Amigos</a></li>
          <li><a href="conversas.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Conversas</a></li>
          <li><a href="criar_comunidade.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Criar Comunidade</a></li>
        </ul>
      </div>

      <a href="explorar_comunidades.php" class="hover:underline text-gray-700 dark:text-gray-300">Explorar</a>
      <a href="../../ranking.php" class="hover:underline text-gray-700 dark:text-gray-300">Ranking</a>
    </div>

    <a href="../../loja.php" class="hover:underline text-gray-700 dark:text-gray-300">Loja</a>
  </div>

  <div class="relative flex items-center gap-4">

    <?php if (isset($_SESSION['email'])): ?>

      <div class="relative">
        <button id="notifBtn" type="button" class="text-gray-600 hover:text-indigo-500 relative">
          <i class="fas fa-bell text-2xl"></i>

          <?php if (!empty($notifCount) && $notifCount > 0): ?>
            <span id="notifCount"
                  class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5">
              <?= (int)$notifCount ?>
            </span>
          <?php endif; ?>

        </button>

        <ul id="notifDropdown"
            class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">

          <?php if (!empty($notificacoes)): ?>

            <?php foreach ($notificacoes as $n): ?>
              <li class="px-4 py-2 border-b last:border-b-0 hover:bg-gray-100 dark:hover:bg-gray-700">
                <a href="notificacao_ver.php?id=<?= (int)$n['id'] ?>"
                   class="block text-sm text-gray-800 dark:text-gray-200">

                  <?= htmlspecialchars((string)mb_strimwidth((string)$n['mensagem'], 0, 100, '...'), ENT_QUOTES, 'UTF-8') ?>

                  <div class="text-xs text-gray-500">
                    <?php 
                      $data = !empty($n['criada_em']) ? (string)$n['criada_em'] : 'now';
                      ?>
                      <?= date('d/m/Y H:i', strtotime($data)) ?>
                  </div>

                </a>
              </li>
            <?php endforeach; ?>

          <?php else: ?>
            <li class="px-4 py-2 text-gray-500">Nenhuma notificação.</li>
          <?php endif; ?>

        </ul>
      </div>

      <div class="relative">
        <button id="userMenuBtn" type="button"
                class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-500">

          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm">
            <?= htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8') ?>
          </span>

        </button>

        <ul id="userDropdown"
            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">

          <li><a href="../../conta/perfil.php"
                 class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>

          <?php if (!empty($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="../../admin/admin_painel.php"
                   class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
          <?php endif; ?>

          <li><a href="../../conta/configuracoes.php"
                 class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>

          <li><a href="../security/logout.php"
                 class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>

        </ul>
      </div>

    <?php endif; ?>

  </div>
</nav>

<div class="bg-gray-800 p-8 rounded-xl shadow-xl w-full max-w-2xl pt-24">
    <h2 class="text-2xl font-bold mb-6">Configurações da Comunidade</h2>

    <?php if (!empty($mensagem)): ?>
        <div class="bg-green-600 p-3 rounded mb-4"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">

        <div>
            <label class="block mb-1">Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($comunidade['nome']) ?>"
                   class="w-full p-2 rounded text-black" required>
        </div>

        <div>
            <label class="block mb-1">Descrição</label>
            <textarea name="descricao" rows="4"
                      class="w-full p-2 rounded text-black"><?= htmlspecialchars($comunidade['descricao']) ?></textarea>
        </div>

        <div>
            <label class="block mb-2">Ícone atual</label>
            <img src="<?= $iconeSrc ?>" class="w-24 h-24 rounded-full mb-4 object-cover border-2 border-indigo-500">
            
            <label class="block text-sm mb-1">Alterar Ícone</label>
            <input type="file" name="icone" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded-lg font-bold transition">
                Salvar Alterações
            </button>

            <a href="ver_comunidade.php?id=<?= $comunidade['id'] ?>"
               class="bg-gray-600 hover:bg-gray-700 px-6 py-2 rounded-lg font-bold transition">
                Voltar
            </a>
        </div>
    </form>
</div>

<script>
    // Scripts básicos para os dropdowns
    const notifBtn = document.getElementById("notifBtn");
    const notifDropdown = document.getElementById("notifDropdown");
    const userBtn = document.getElementById("userMenuBtn");
    const userDropdown = document.getElementById("userDropdown");

    if (notifBtn) {
        notifBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle("hidden");
            userDropdown.classList.add("hidden");
        });
    }

    if (userBtn) {
        userBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle("hidden");
            notifDropdown.classList.add("hidden");
        });
    }

    window.addEventListener("click", () => {
        if (notifDropdown) notifDropdown.classList.add("hidden");
        if (userDropdown) userDropdown.classList.add("hidden");
    });
</script>

</body>
</html>