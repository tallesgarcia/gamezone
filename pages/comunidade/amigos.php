<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/amizades_pendentes_count.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../security/entrar.php");
    exit;
}

$usuarioId = $_SESSION['user_id'];

// Buscar amigos aceitos com a estrutura real
$stmt = $conn->prepare("
    SELECT u.id, u.nome, u.avatar
    FROM amizades a
    JOIN usuarios u ON (
        (a.usuario_id = ? AND u.id = a.amigo_id) OR 
        (a.amigo_id = ? AND u.id = a.usuario_id)
    )
    WHERE a.status = 'aceito'
      AND u.id != ?
");
$stmt->bind_param("iii", $usuarioId, $usuarioId, $usuarioId);
$stmt->execute();
$result = $stmt->get_result();
$amigos = $result->fetch_all(MYSQLI_ASSOC);

// Buscar quantidade de pedidos pendentes recebidos
$stmtPendentes = $conn->prepare("
    SELECT COUNT(*) as total
    FROM amizades
    WHERE amigo_id = ? AND status = 'pendente'
");
$stmtPendentes->bind_param("i", $usuarioId);
$stmtPendentes->execute();
$quantidadePendentes = $stmtPendentes->get_result()->fetch_assoc()['total'] ?? 0;


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
  <title>Amigos | GameZone</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
          <li><a href="minhas_comunidades.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Minhas Comunidades</a></li>
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

<div class="flex h-screen">
  <!-- Sidebar -->
  <aside class="w-64 bg-gray-800 p-4">
    <h2 class="text-xl font-bold mb-4">Amigos</h2>
    <ul class="space-y-2">
      <li><a href="amigos.php" class="block px-3 py-2 rounded hover:bg-gray-700">Todos</a></li>
      <li><a href="online.php" class="block px-3 py-2 rounded hover:bg-gray-700">Online</a></li>
      <li>
        <li>
          <a href="amigos_pendentes.php" class="block px-3 py-2 rounded hover:bg-gray-700 flex justify-between items-center">
            <span>Pendentes</span>
            <?php if ($quantidadePendentes > 0): ?>
              <span class="text-xs bg-red-600 px-2 py-0.5 rounded-full"><?= $quantidadePendentes ?></span>
            <?php endif; ?>
          </a>
        </li>
      </li>
      <li><a href="bloqueados.php" class="block px-3 py-2 rounded hover:bg-gray-700">Bloqueados</a></li>
    </ul>
  </aside>

  <!-- Conteúdo principal -->
  <main class="flex-1 p-6 overflow-y-auto">
    <h1 class="text-2xl font-semibold mb-4">Seus amigos</h1>

    <!-- Barra de busca -->
    <form method="GET" class="mb-6 flex max-w-xl">
      <input type="text" name="buscar" placeholder="Procurar usuários por nome ou email"
             class="w-full p-2 rounded-l bg-gray-700 text-white focus:outline-none"
             value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
      <button class="bg-indigo-600 px-4 py-2 rounded-r hover:bg-indigo-700">Buscar</button>
    </form>

    <?php
    if (!empty($_GET['buscar'])):
      $termo = '%' . $_GET['buscar'] . '%';
      $stmtBusca = $conn->prepare("
          SELECT u.id, u.nome, u.avatar, u.email
          FROM usuarios u
          WHERE (u.nome LIKE ? OR u.email LIKE ?)
            AND u.id != ?
            AND u.id NOT IN (
              SELECT CASE 
                  WHEN a.usuario_id = ? THEN a.amigo_id
                  ELSE a.usuario_id
              END
              FROM amizades a
              WHERE (a.usuario_id = ? OR a.amigo_id = ?)
          )
      ");
      $stmtBusca->bind_param("sssiii", $termo, $termo, $usuarioId, $usuarioId, $usuarioId, $usuarioId);
      $stmtBusca->execute();
      $usuariosEncontrados = $stmtBusca->get_result()->fetch_all(MYSQLI_ASSOC);
    ?>
      <h2 class="text-lg font-medium mb-2">Usuários encontrados:</h2>
      <?php if (empty($usuariosEncontrados)): ?>
        <p class="text-gray-400">Nenhum usuário encontrado.</p>
      <?php else: ?>
        <ul class="space-y-4 mb-8">
          <?php foreach ($usuariosEncontrados as $user): ?>
            <li class="bg-gray-800 p-4 rounded flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <img src="<?= htmlspecialchars($user['avatar']) ?>" class="w-10 h-10 rounded-full object-cover">
                <div>
                  <p class="font-semibold"><?= htmlspecialchars($user['nome']) ?></p>
                  <p class="text-sm text-gray-400"><?= htmlspecialchars($user['email']) ?></p>
                </div>
              </div>
              <div class="flex space-x-2">
                <!-- Adicionar Amigo -->
                <form method="POST" action="../../enviar_pedido_amizade.php">
                  <input type="hidden" name="id_destino" value="<?= $user['id'] ?>">
                  <button class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded">Adicionar</button>
                </form>

                <!-- Denunciar Usuário -->
                <a href="../reportar/reportar_usuario.php?usuario_id=<?= $user['id'] ?>" 
                  class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-white">
                  Denunciar
                </a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>

      <?php endif; ?>
    <?php endif; ?>

    <!-- Lista de amigos -->
    <?php if (empty($amigos)): ?>
      <p class="text-gray-400">Você ainda não tem amigos adicionados.</p>
    <?php else: ?>
      <ul class="space-y-4">
        <?php foreach ($amigos as $amigo): ?>
          <li class="bg-gray-800 p-4 rounded-lg flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <img src="<?= htmlspecialchars($amigo['avatar'] ?? 'default_avatar.png') ?>" class="w-10 h-10 rounded-full object-cover">
              <span><?= htmlspecialchars($amigo['nome']) ?></span>
            </div>
            <div class="flex space-x-2">
              <form action="../../bloquear_amigo.php" method="POST">
                <input type="hidden" name="id_amigo" value="<?= $amigo['id'] ?>">
                <button class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded">Bloquear</button>
              </form>
              <form action="../../remover_amizade.php" method="POST">
                <input type="hidden" name="id_amigo" value="<?= $amigo['id'] ?>">
                <button class="bg-gray-600 hover:bg-gray-700 px-3 py-1 rounded">Remover</button>
              </form>
              <form action="conversas.php?amigo_id=" method="POST">
                <input type="hidden" name="id_amigo" value="<?= $amigo['id'] ?>">
                <button class="bg-indigo-600 hover:bg-indigo-700 px-3 py-1 rounded">Conversar</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </main>
</div>
<!-- FOOTER -->
<footer class="text-center py-6 text-gray-400 text-sm border-t border-gray-700 mt-14">
    <div class="mb-2">
        <a href="../contato.php" class="hover:text-indigo-400 transition mx-2">Contato</a> |
        <a href="../privacidade.php" class="hover:text-indigo-400 transition mx-2">Privacidade</a> |
        <a href="../sobre.php" class="hover:text-indigo-400 transition mx-2">Sobre</a> |
        <a href="../termos.php" class="hover:text-indigo-400 transition mx-2">Termos</a> |
        <a href="../equipe.php" class="hover:text-indigo-400 transition mx-2">Equipe</a>
    </div>
    <div>© <?= date('Y') ?> GameZone - Conectando jogadores.</div>
</footer>

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
