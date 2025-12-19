<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../config/db.php';

// Verifica login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../security/entrar.php");
    exit;
}

$mensagem = "";

// Se enviou o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $dono_id = $_SESSION['user_id'];
    $icone = null;

    // Upload do ícone
    if (!empty($_FILES['icone']['name']) && $_FILES['icone']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/../../uploads/comunidades/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['icone']['name'], PATHINFO_EXTENSION));
        $nomeArquivo = uniqid("icone_") . "." . $ext;
        $destino = $uploadDir . $nomeArquivo;

        if (move_uploaded_file($_FILES['icone']['tmp_name'], $destino)) {
            $icone = "uploads/comunidades/" . $nomeArquivo;
        }
    }

    if (!empty($nome)) {
        $stmt = $conn->prepare("INSERT INTO comunidades (nome, descricao, genero, icone, dono_id, membros) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssssi", $nome, $descricao, $genero, $icone, $dono_id);

        if ($stmt->execute()) {
            $comunidade_id = $stmt->insert_id;

            // Dono entra automaticamente como membro
            $stmt2 = $conn->prepare("INSERT INTO membros_comunidade (comunidade_id, usuario_id) VALUES (?, ?)");
            $stmt2->bind_param("ii", $comunidade_id, $dono_id);
            $stmt2->execute();

            // Redireciona para a página inicial
            header("Location: ../../index.php");
            exit;
        } else {
            $mensagem = "Erro ao criar comunidade.";
        }
    } else {
        $mensagem = "O nome da comunidade é obrigatório.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Criar Comunidade | GameZone</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="pt-16 bg-gray-900 text-white">

<!-- NAVBAR -->
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
  <div class="flex items-center gap-6">
    <a class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">Game<span class="text-gray-800 dark:text-gray-100">Zone</span></a>
    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="../../index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>
      <div class="relative group">
        <button class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>
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

  <!-- Usuário -->
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-500">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>
        <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <li><a href="../../conta/perfil.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>
          <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="../../admin/admin_painel.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
          <?php endif; ?>
          <li><a href="../../conta/configuracoes.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>
          <li><a href="../security/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</nav>

<!-- FORMULÁRIO -->
<div class="max-w-2xl mx-auto mt-10 dark:bg-gray-800 text-gray-900 p-6 rounded shadow">
  <h2 class="text-2xl font-bold mb-4 text-indigo-600">Criar Nova Comunidade</h2>

  <?php if (!empty($mensagem)): ?>
    <div class="mb-4 p-2 bg-blue-100 text-blue-800 rounded"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>  

  <form action="" method="post" enctype="multipart/form-data" class="space-y-4">
    <div>
      <label class="block font-semibold text-white">Nome da Comunidade: *</label>
      <input type="text" name="nome" required class="w-full border rounded px-3 py-2 bg-white text-gray-900">
    </div>

    <div>
      <label class="block font-semibold text-white">Descrição:</label>
      <textarea name="descricao" rows="4" class="w-full border rounded px-3 py-2 bg-white text-gray-900"></textarea>
    </div>

    <div>
      <label class="block font-semibold text-white">Gênero:</label>
      <select name="genero" class="w-full border rounded px-3 py-2 bg-white text-gray-900">
        <option value="acao">Ação</option>
        <option value="aventura">Aventura</option>
        <option value="rpg">RPG</option>
        <option value="terror">Terror</option>
      </select>
    </div>

    <div>
      <label class="block font-semibold text-white">Ícone (opcional):</label>
      <input type="file" name="icone" accept="image/*" class="w-full text-gray-800">
    </div>

    <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition">
      Criar Comunidade
    </button>
  </form>
</div>

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
