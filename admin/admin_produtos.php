<?php
// admin_produtos.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Verifica se é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Buscando produtos
$stmt = $conn->prepare("SELECT id, nome, descricao, preco, tipo, imagem, ativo, criado_em FROM produtos ORDER BY criado_em DESC");
$stmt->execute();
$res = $stmt->get_result();
$produtos = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ==============================
// NOTIFICAÇÕES DO USUÁRIO
// ==============================

// Inicializa array de notificações e contador de não-lidas
$notificacoes = [];
$notifCount = 0;

// Se existe user_id na sessão, buscamos as notificações relacionadas
if (isset($_SESSION['user_id'])) {
    // Prepared statement para evitar SQL injection ao usar parâmetros.
    $stmt = $conn->prepare("SELECT id, mensagem, lida, criada_em FROM notificacoes WHERE usuario_id = ? ORDER BY criada_em DESC LIMIT 5");
    if ($stmt) {
        // Liga parâmetro (i = integer) com o id do usuário vindo da sessão
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();

        // Liga colunas de resultado a variáveis
        $stmt->bind_result($nid, $nmsg, $nlida, $ncriada);

        // Itera pelas notificações, monta o array e conta não-lidas
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
<meta charset="utf-8">
<title>Admin - Produtos | GameZone</title>
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

<!-- Conteúdo principal -->
<main class="ml-64 pt-20 p-6">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">🛍️ Produtos</h1>
    <a href="produtos_adicionar.php" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded">+ Novo Produto</a>
  </div>

  <div class="overflow-x-auto bg-white text-gray-900 rounded shadow">
    <table class="min-w-full">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-3 text-left">ID</th>
          <th class="p-3 text-left">Imagem</th>
          <th class="p-3 text-left">Nome</th>
          <th class="p-3 text-left">Tipo</th>
          <th class="p-3 text-left">Preço</th>
          <th class="p-3 text-left">Status</th>
          <th class="p-3 text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($produtos as $p): ?>
        <tr class="border-t">
          <td class="p-3 align-middle"><?= (int)$p['id'] ?></td>
          <td class="p-3 align-middle">
            <?php if (!empty($p['imagem'])): 
              // Detecta mime do blob
              $finfo = new finfo(FILEINFO_MIME_TYPE);
              $mime = $finfo->buffer($p['imagem']) ?: 'image/jpeg';
              $base64 = base64_encode($p['imagem']);
            ?>
              <img src="data:<?= htmlspecialchars($mime) ?>;base64,<?= $base64 ?>" alt="thumb" class="w-16 h-16 object-cover rounded">
            <?php else: ?>
              <div class="w-16 h-16 bg-gray-200 rounded text-center text-sm text-gray-600 flex items-center justify-center">sem imagem</div>
            <?php endif; ?>
          </td>
          <td class="p-3 align-middle"><?= htmlspecialchars($p['nome']) ?></td>
          <td class="p-3 align-middle"><?= htmlspecialchars(ucfirst($p['tipo'])) ?></td>
          <td class="p-3 align-middle">R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
          <td class="p-3 align-middle">
            <?= $p['ativo'] ? '<span class="text-green-600 font-medium">Ativo</span>' : '<span class="text-gray-500 italic">Inativo</span>' ?>
          </td>
          <td class="p-3 align-middle text-center">
            <div class="inline-flex gap-2">
              <?php if ($p['ativo']): ?>
                <a href="acoes/desativar_produto.php?id=<?= (int)$p['id'] ?>" class="bg-yellow-400 text-white px-2 py-1 rounded text-xs">Desativar</a>
              <?php else: ?>
                <a href="acoes/ativar_produto.php?id=<?= (int)$p['id'] ?>" class="bg-green-600 text-white px-2 py-1 rounded text-xs">Ativar</a>
              <?php endif; ?>
              <a href="produtos_editar.php?id=<?= (int)$p['id'] ?>" class="bg-blue-600 text-white px-2 py-1 rounded text-xs">Editar</a>
              <a href="acoes/excluir_produto.php?id=<?= (int)$p['id'] ?>" onclick="return confirm('Excluir este produto?')" class="bg-red-600 text-white px-2 py-1 rounded text-xs">Excluir</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($produtos)): ?>
        <tr><td class="p-3" colspan="7">Nenhum produto cadastrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
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
