<?php
// produtos_adicionar.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Verifica admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $tipo = $_POST['tipo'] ?? 'digital';
    $preco = floatval($_POST['preco'] ?? 0);

    // Validações simples
    if ($nome === '' || $preco < 0) {
        $mensagem = "Preencha o nome e preço corretamente.";
    } else {
        $imagemBlob = null;
        $allowed = ['image/jpeg','image/png','image/webp'];

        if (!empty($_FILES['imagem']['name'])) {
            if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
                $mensagem = "Erro no upload da imagem.";
            } elseif ($_FILES['imagem']['size'] > 2 * 1024 * 1024) {
                $mensagem = "A imagem deve ter no máximo 2MB.";
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['imagem']['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mime, $allowed)) {
                    $mensagem = "Formato inválido. Envie JPG, PNG ou WEBP.";
                } else {
                    $imagemBlob = file_get_contents($_FILES['imagem']['tmp_name']);
                }
            }
        }

        if (!$mensagem) {
            // Inserção com BLOB
            $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, tipo, imagem, ativo) VALUES (?, ?, ?, ?, ?, 1)");
            if (!$stmt) {
                $mensagem = "Erro no banco: " . $conn->error;
            } else {
                // bind_param: s s d s s
                $null = null;
                // se $imagemBlob for null, ainda passamos variável (null)
                $imgParam = $imagemBlob;
                $stmt->bind_param("ssdss", $nome, $descricao, $preco, $tipo, $imgParam);

                // Se for blob grande, usar send_long_data
                if ($imagemBlob !== null) {
                    // índice do parâmetro da imagem: 4 (0-based)
                    $stmt->send_long_data(4, $imagemBlob);
                }

                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: admin_produtos.php?add=ok");
                    exit;
                } else {
                    $mensagem = "Erro ao salvar: " . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Adicionar Produto - Admin | GameZone</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white">

<!-- Sidebar -->
<div class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white shadow-lg">
  <div class="p-4 font-bold text-xl text-indigo-400">Admin GameZone</div>
  <nav class="flex flex-col gap-2 mt-4 px-4">
    <a href="admin_painel.php" class="hover:text-indigo-400">📊 Painel</a>
    <a href="admin_usuarios.php" class="hover:text-indigo-400">👥 Usuários</a>
    <a href="admin_jogos.php" class="hover:text-indigo-400">🎮 Jogos</a>
    <a href="admin_produtos.php" class="text-indigo-400 font-semibold">🛍️ Produtos</a>
    <a href="admin_avaliacoes.php" class="hover:text-indigo-400">⭐ Avaliações</a>
    <a href="admin_denuncias.php" class="hover:text-indigo-400">🚨 Denúncias</a>
    <a href="admin_noticias.php" class="hover:text-indigo-400">📰 Notícias</a>
    <a href="admin_comunidades.php" class="hover:text-indigo-400">🌐 Comunidades</a>
    <a href="admin_compras.php" class="hover:text-indigo-400">🧾 Compras</a>
    <a href="admin_equipe.php" class="hover:text-indigo-400">🧑‍💼 Equipe</a>
    <a href="admin_configuracoes.php" class="hover:text-indigo-400">⚙️ Configurações</a>
  </nav>
</div>
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
  <!-- Logo e links principais -->
  <div class="flex items-center gap-6">
    <a class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
      Game<span class="text-gray-800 dark:text-gray-100">Zone</span>
    </a>

    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="../index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>

      <div class="relative group">
        <button class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>
        <ul class="absolute hidden group-hover:block bg-white dark:bg-gray-800 shadow rounded mt-1 p-2 w-44 z-50">
          <li><a href="../pages/minhas_comunidades.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Minhas Comunidades</a></li>
          <li><a href="../pages/comunidade/chat.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Chat</a></li>
          <li><a href="../pages/comunidade/amigos.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Amigos</a></li>
          <li><a href="../pages/comunidade/conversas.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Conversas</a></li>
          <li><a href="../pages/comunidade/criar_comunidade.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Criar Comunidade</a></li>
        </ul>
      </div>

      <div class="relative group">
        <a href="../pages/comunidade/explorar_comunidades.php" class="hover:underline text-gray-700 dark:text-gray-300">Explorar</a>
      </div>

      <a href="../ranking.php" class="hover:underline text-gray-700 dark:text-gray-300">Ranking</a>
    </div>

    <a href="../loja.php" class="hover:underline text-gray-700 dark:text-gray-300">Loja</a>
  </div>

  <!-- Notificações & Usuário (visíveis só com email na sessão) -->
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>
      <!-- Notificações -->
      <div class="relative">
        <button id="notifBtn" class="text-gray-600 hover:text-indigo-500 relative">
          <i class="fas fa-bell text-2xl"></i>
          <?php if($notifCount > 0): ?>
            <span id="notifCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5"><?= (int)$notifCount ?></span>
          <?php endif; ?>
        </button>

        <ul id="notifDropdown" class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <?php if (!empty($notificacoes)): ?>
            <?php foreach ($notificacoes as $n): ?>
              <li class="px-4 py-2 border-b last:border-b-0 hover:bg-gray-100 dark:hover:bg-gray-700">
                <a href="notificacao_ver.php?id=<?= (int)$n['id'] ?>" class="block text-sm text-gray-800 dark:text-gray-200">
                  <?= htmlspecialchars(mb_strimwidth((string)$n['mensagem'], 0, 100, '...'), ENT_QUOTES, 'UTF-8') ?>
                  <div class="text-xs text-gray-500">
                    <?= date('d/m/Y H:i', strtotime((string)$n['criada_em'])) ?>
                  </div>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="px-4 py-2 text-gray-500">Nenhuma notificação.</li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Menu do usuário -->
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-500">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>
        <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <li><a href="../conta/perfil.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>
          <?php if ($_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="../admin/admin_painel.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
          <?php endif; ?>
          <li><a href="../conta/configuracoes.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>
          <li><a href="../pages/security/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>
        </ul>
      </div>
    <?php else: ?>
      <div class="flex gap-2">
        <a href="../pages/security/entrar.php" class="text-sm hover:underline">Entrar</a>
        <a href="../pages/security/cadastrar.html" class="text-sm hover:underline">Cadastrar</a>
      </div>
    <?php endif; ?>
  </div>
</nav>

<main class="ml-64 pt-20 p-6">
  <h1 class="text-2xl font-bold mb-4">Adicionar Produto</h1>

  <?php if ($mensagem): ?>
    <div class="mb-4 p-3 bg-red-500 text-white rounded"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="bg-gray-800 p-6 rounded w-full max-w-xl">
    <label class="block mb-2 font-semibold">Nome *</label>
    <input name="nome" required class="w-full p-2 rounded bg-gray-700 mb-3">

    <label class="block mb-2 font-semibold">Descrição</label>
    <textarea name="descricao" class="w-full p-2 rounded bg-gray-700 mb-3"></textarea>

    <label class="block mb-2 font-semibold">Tipo *</label>
    <select name="tipo" class="w-full p-2 rounded bg-gray-700 mb-3">
      <option value="fisico">Produto Físico</option>
      <option value="digital" selected>Produto Digital</option>
      <option value="assinatura">Assinatura</option>
    </select>

    <label class="block mb-2 font-semibold">Preço (R$) *</label>
    <input name="preco" type="number" step="0.01" required class="w-full p-2 rounded bg-gray-700 mb-3">

    <label class="block mb-2 font-semibold">Imagem (JPG/PNG/WEBP, max 2MB)</label>
    <input name="imagem" type="file" accept="image/*" class="mb-4">

    <div class="flex justify-end">
      <a href="admin_produtos.php" class="mr-2 px-4 py-2 rounded bg-gray-600 hover:bg-gray-500">Voltar</a>
      <button class="px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-500">Salvar</button>
    </div>
  </form>
</main>
<script>
const notifBtn = document.getElementById("notifBtn");
    const notifDropdown = document.getElementById("notifDropdown");
    const userBtn = document.getElementById("userMenuBtn");
    const userDropdown = document.getElementById("userDropdown");
    const notifCountEl = document.getElementById("notifCount");

// Atualiza notificações
function atualizarNotificacoes() { 
  fetch('buscar_notificacoes.php') 
    .then(res => res.json()) 
    .then(data => {
      if(notifCountEl) { 
        notifCountEl.textContent = data.count;
        notifCountEl.style.display = data.count > 0 ? 'inline-block' : 'none'; 
      }
      if(notifDropdown) { 
        notifDropdown.innerHTML = '';
        if(data.notificacoes.length === 0) { 
          notifDropdown.innerHTML = '<li class="px-4 py-2 text-gray-500">Nenhuma notificação</li>'; 
        } else { 
          data.notificacoes.forEach(n => { 
            const li = document.createElement('li');
            li.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700' + (n.lida==0 ? ' font-bold' : '');
            li.innerHTML = `${n.mensagem} <span class="text-xs text-gray-400 float-right">${new Date(n.criada_em).toLocaleString('pt-BR',{ day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' })}</span>`;
            notifDropdown.appendChild(li); 
          });
        } 
      }
    }); 
}
setInterval(atualizarNotificacoes, 5000);
atualizarNotificacoes();

// Toggle dropdown notificações
if (notifBtn && notifDropdown) {
  notifBtn.addEventListener("click", e => { 
    e.stopPropagation();
    notifDropdown.classList.toggle("hidden");
    if (userDropdown && !userDropdown.classList.contains("hidden")) userDropdown.classList.add("hidden");
    if (!notifDropdown.classList.contains("hidden")) fetch('marcar_notificacoes_lidas.php').then(()=>{if(notifCountEl) notifCountEl.style.display='none';});
  });
}

// Toggle dropdown usuário
if (userBtn && userDropdown) {
  userBtn.addEventListener("click", e => {
    e.stopPropagation();
    userDropdown.classList.toggle("hidden");
    if (notifDropdown && !notifDropdown.classList.contains("hidden")) notifDropdown.classList.add("hidden");
  });
}

// Fecha dropdowns ao clicar fora
window.addEventListener("click", () => {
  if(userDropdown) userDropdown.classList.add("hidden");
  if(notifDropdown) notifDropdown.classList.add("hidden");
});
</script>
</body>
</html>
