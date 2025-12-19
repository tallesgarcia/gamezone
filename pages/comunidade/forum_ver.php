<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// =============================
// Verifica se o usuário está logado
// =============================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../security/entrar.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$forum_id = intval($_GET['id'] ?? 0);

// =============================
// Busca dados do fórum
// =============================
$stmt = $conn->prepare("
    SELECT 
        f.id, f.titulo, f.descricao, f.criado_em, 
        c.nome AS comunidade_nome, c.id AS comunidade_id
    FROM foruns f
    JOIN comunidades c ON c.id = f.comunidade_id
    WHERE f.id = ?
");
if (!$stmt) {
    die("Erro ao preparar consulta: " . $conn->error);
}
$stmt->bind_param("i", $forum_id);
$stmt->execute();
$result = $stmt->get_result();
$forum = $result->fetch_assoc();
$stmt->close();

if (!$forum) {
    die("Fórum não encontrado.");
}

// =============================
// Notificações do usuário
// =============================
$notificacoes = [];
$notifCount = 0;

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
        SELECT id, mensagem, lida, criada_em 
        FROM notificacoes 
        WHERE usuario_id = ? 
        ORDER BY criada_em DESC 
        LIMIT 5
    ");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($nid, $nmsg, $nlida, $ncriada);
        while ($stmt->fetch()) {
            $notificacoes[] = [
                'id' => $nid,
                'mensagem' => $nmsg,
                'lida' => $nlida,
                'criada_em' => $ncriada
            ];
            if ($nlida == 0) $notifCount++;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($forum['titulo']) ?> - Fórum</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="text-base bg-gray-900 text-white">

<!-- ============================= -->
<!-- NAVBAR -->
<!-- ============================= -->
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
  <!-- Logo e Navegação -->
  <div class="flex items-center gap-6">
    <a href="../../index.php" class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">Game<span class="text-gray-800 dark:text-gray-100">Zone</span></a>
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

  <!-- Notificações e Usuário -->
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>
      <!-- Notificações -->
      <div class="relative">
        <button id="notifBtn" class="text-gray-600 hover:text-indigo-500 relative">
          <i class="fas fa-bell text-2xl"></i>
          <?php if ($notifCount > 0): ?>
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

      <!-- Usuário -->
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-500">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>
        <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <li><a href="../../conta/perfil.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>
          <?php if (!empty($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="../../admin/admin_painel.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
          <?php endif; ?>
          <li><a href="../../conta/configuracoes.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>
          <li><a href="../security/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</nav>

<!-- ============================= -->
<!-- CONTEÚDO DO FÓRUM -->
<!-- ============================= -->
<div class="pt-20 max-w-4xl mx-auto mt-10 bg-gray-800 p-6 rounded shadow">
    <a href="ver_comunidade.php?id=<?= (int)$forum['comunidade_id'] ?>" class="text-indigo-400 hover:underline">
    &larr; Voltar para <?= htmlspecialchars($forum['comunidade_nome']) ?>
</a>

    
    <h1 class="text-2xl font-bold mt-2"><?= htmlspecialchars($forum['titulo']) ?></h1>
    <p class="text-gray-300 mt-1"><?= nl2br(htmlspecialchars($forum['descricao'])) ?></p>
    <p class="text-gray-500 text-sm mt-1">Criado em <?= date('d/m/Y H:i', strtotime($forum['criado_em'])) ?></p>
</div>

<!-- ============================= -->
<!-- MENSAGENS -->
<!-- ============================= -->
<div class="max-w-4xl mx-auto mt-6 bg-gray-800 p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-3">Mensagens</h2>
    <div id="listaMensagens" class="h-80 overflow-y-auto border border-gray-700 p-3 rounded bg-gray-900"></div>

    <form id="formMensagem" class="flex gap-2 mt-3">
        <input type="hidden" name="forum_id" value="<?= (int)$forum_id ?>">
        <input type="text" name="mensagem" id="mensagem" placeholder="Escreva sua mensagem..." class="flex-1 p-2 rounded text-black" required>
        <button type="submit" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700">Enviar</button>
        <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
      <button id="limpar-chat" type="button" class="mb-4 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
        🧹 Limpar Chat
      </button>
    <?php endif; ?>
    </form>
</div>

<script>
$(document).ready(function(){
    function carregarMensagens(){
        $.get("get_forum_mensagens.php", {forum_id: <?= (int)$forum_id ?>}, function(data){
            $("#listaMensagens").html(data);
            $("#listaMensagens").scrollTop($("#listaMensagens")[0].scrollHeight);
        });
    }
    carregarMensagens();
    setInterval(carregarMensagens, 3000);

    $("#formMensagem").submit(function(e){
        e.preventDefault();
        $.post("enviar_forum_mensagem.php", $(this).serialize(), function(res){
            if(res.trim() === "ok"){
                $("#mensagem").val("");
                carregarMensagens();
            }
        });
    });
});

$('#limpar-chat').click(function (e) {
  e.preventDefault();
  if (confirm("Tem certeza que deseja limpar o chat? Esta ação não pode ser desfeita.")) {
    $.post('limpar_mensagens_forum.php', function(response) {
      carregarMensagens();
    }).fail(function() {
      alert("Erro ao limpar o chat.");
    });
  }
});

// =============================
// Dropdowns e Notificações
// =============================
const notifBtn = document.getElementById("notifBtn");
const notifDropdown = document.getElementById("notifDropdown");
const userBtn = document.getElementById("userMenuBtn");
const userDropdown = document.getElementById("userDropdown");
const notifCountEl = document.getElementById("notifCount");

function atualizarNotificacoes() {
  fetch('buscar_notificacoes.php')
    .then(res => res.json())
    .then(data => {
      if (notifCountEl) {
        notifCountEl.textContent = data.count;
        notifCountEl.style.display = data.count > 0 ? 'inline-block' : 'none';
      }
      if (notifDropdown) {
        notifDropdown.innerHTML = '';
        if (data.notificacoes.length === 0) {
          notifDropdown.innerHTML = '<li class="px-4 py-2 text-gray-500">Nenhuma notificação</li>';
        } else {
          data.notificacoes.forEach(n => {
            const li = document.createElement('li');
            li.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700' + (n.lida == 0 ? ' font-bold' : '');
            li.innerHTML = `${n.mensagem} <span class="text-xs text-gray-400 float-right">${new Date(n.criada_em).toLocaleString('pt-BR')}</span>`;
            notifDropdown.appendChild(li);
          });
        }
      }
    });
}
setInterval(atualizarNotificacoes, 5000);
atualizarNotificacoes();

if (notifBtn && notifDropdown) {
  notifBtn.addEventListener("click", e => {
    e.stopPropagation();
    notifDropdown.classList.toggle("hidden");
    if (userDropdown && !userDropdown.classList.contains("hidden")) userDropdown.classList.add("hidden");
    if (!notifDropdown.classList.contains("hidden"))
      fetch('marcar_notificacoes_lidas.php').then(() => { if (notifCountEl) notifCountEl.style.display = 'none'; });
  });
}

if (userBtn && userDropdown) {
  userBtn.addEventListener("click", e => {
    e.stopPropagation();
    userDropdown.classList.toggle("hidden");
    if (notifDropdown && !notifDropdown.classList.contains("hidden")) notifDropdown.classList.add("hidden");
  });
}

window.addEventListener("click", () => {
  if (userDropdown) userDropdown.classList.add("hidden");
  if (notifDropdown) notifDropdown.classList.add("hidden");
});
</script>

</body>
</html>
