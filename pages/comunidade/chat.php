<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Você precisa estar logado para acessar o chat.");
}

// --- Carrega a conexão com o banco (tenta caminhos comuns) ---
$db_paths = [
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/config/db.php'
];
$included = false;
foreach ($db_paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $included = true;
        break;
    }
}
if (!$included || !isset($conn)) {
    die("Erro: conexão com o banco de dados não encontrada.");
}

// ==============================
// NOTIFICAÇÕES
// ==============================
$notificacoes = [];
$notifCount = 0;
$user_id = (int) ($_SESSION['user_id'] ?? 0);

if ($user_id > 0) {
    $sql = "SELECT id, mensagem, lida, criada_em FROM notificacoes WHERE usuario_id = ? ORDER BY criada_em DESC LIMIT 5";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($nid, $nmsg, $nlida, $ncriada);
        while ($stmt->fetch()) {
            $notificacoes[] = [
                'id' => $nid,
                'mensagem' => $nmsg,
                'lida' => $nlida,
                'criada_em' => $ncriada
            ];
            if ((int)$nlida === 0) $notifCount++;
        }
        $stmt->close();
    } else {
        // opcional: log de erro
        // error_log("Erro prepare notificacoes: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Chat Global | GameZone</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body { font-family: Arial, sans-serif; background: #111; color: #fff; }
    #chat-box { height: 300px; overflow-y: scroll; border: 1px solid #444; padding: 10px; margin-bottom: 10px; background: #222; }
    .mensagem { margin-bottom: 5px; }
    .mensagem strong { color: #00f; }
    input, button { padding: 8px; }
  </style>
</head>
<body class="pt-16 bg-gray-900 text-white">
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
  <!-- Logo e navegação -->
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
                  <?= htmlspecialchars(mb_strimwidth($n['mensagem'], 0, 100, '...')) ?>
                  <div class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($n['criada_em'])) ?></div>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="px-4 py-2 text-gray-500">Nenhuma notificação.</li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Usuário -->
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

<main class="max-w-2xl mx-auto px-4 mt-4">
  <h1 class="text-xl font-bold mb-4">💬 Chat Global</h1>
  <div id="chat-box" class="h-80 overflow-y-scroll border border-gray-600 bg-gray-800 rounded p-4 mb-4"></div>
  <form id="form-chat" class="flex gap-2">
    <input type="text" id="mensagem" placeholder="Digite sua mensagem..." required
        class="flex-1 p-2 rounded bg-gray-700 text-white border border-gray-600">
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">Enviar</button>
    <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
      <button id="limpar-chat" type="button" class="mb-4 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
        🧹 Limpar Chat
      </button>
    <?php endif; ?>
  </form>
</main>

<!-- FOOTER -->
<footer class="text-center py-6 text-gray-400 text-sm border-t border-gray-700 mt-14">
    <div class="mb-2">
        <a href="../../contato.php" class="hover:text-indigo-400 transition mx-2">Contato</a> |
        <a href="../../privacidade.php" class="hover:text-indigo-400 transition mx-2">Privacidade</a> |
        <a href="../../sobre.php" class="hover:text-indigo-400 transition mx-2">Sobre</a> |
        <a href="../../termos.php" class="hover:text-indigo-400 transition mx-2">Termos</a> |
        <a href="../../equipe.php" class="hover:text-indigo-400 transition mx-2">Equipe</a>
    </div>
    <div>© <?= date('Y') ?> GameZone - Conectando jogadores.</div>
</footer>

<script>
function carregarMensagens() {
  $.get('chat_carregar.php', function(data) {
    $('#chat-box').html(data);
    $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
  });
}
setInterval(carregarMensagens, 3000); // Atualiza a cada 3s

$('#form-chat').submit(function(e) {
  e.preventDefault();
  var mensagem = $('#mensagem').val().trim();
  if (!mensagem) return;
  var $submitBtn = $(this).find('button[type="submit"]');
  $submitBtn.prop('disabled', true);

  $.post('chat_enviar.php', { mensagem: mensagem }, function() {
    $('#mensagem').val('');
    carregarMensagens();
    $submitBtn.prop('disabled', false);
  }).fail(function() {
    alert("Erro ao enviar mensagem.");
    $submitBtn.prop('disabled', false);
  });
});

carregarMensagens();

$('#limpar-chat').click(function (e) {
  e.preventDefault();
  if (confirm("Tem certeza que deseja limpar o chat? Esta ação não pode ser desfeita.")) {
    $.post('chat_limpar.php', function(response) {
      carregarMensagens();
    }).fail(function() {
      alert("Erro ao limpar o chat.");
    });
  }
});


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
