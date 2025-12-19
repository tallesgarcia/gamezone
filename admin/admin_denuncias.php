<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    die("Acesso negado.");
}

// Filtro de status via GET (seguro)
$filtros_permitidos = ['pendente', 'analisando', 'resolvido'];
$filtro = $_GET['status'] ?? '';
$condicao = in_array($filtro, $filtros_permitidos) ? "WHERE d.status = ?" : "";

$query = "SELECT d.*, u1.nome AS denunciante, u2.nome AS denunciado
          FROM denuncias d
          JOIN usuarios u1 ON d.id_denunciante = u1.id
          JOIN usuarios u2 ON d.id_denunciado = u2.id
          $condicao
          ORDER BY d.data_ocorrido DESC";

$stmt = $conn->prepare($query);
if ($condicao !== "") {
    $stmt->bind_param("s", $filtro);
}
$stmt->execute();
$result = $stmt->get_result();


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
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Admin | Denúncias | GameZone</title>
  <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/estilos.css">
  <script>
    function atualizarStatus(id, status) {
      if (confirm("Tem certeza que deseja alterar o status desta denúncia?")) {
        fetch('acoes/atualizar_status_denuncia.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${id}&status=${status}`
        })
        .then(res => res.text())
        .then(msg => {
          alert(msg);
          location.reload();
        });
      }
    }
  </script>
</head>
<body class="bg-gray-100">

<!-- Sidebar -->
<div class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white shadow-lg">
  <div class="p-4 font-bold text-xl text-indigo-400">Admin GameZone</div>
  <nav class="flex flex-col gap-2 mt-4 px-4">
    <a href="admin_painel.php" class="hover:text-indigo-400">📊 Painel</a>
    <a href="admin_usuarios.php" class="hover:text-indigo-400">👥 Usuários</a>
    <a href="admin_jogos.php" class="hover:text-indigo-400">🎮 Jogos</a>
    <a href="admin_produtos.php" class="hover:text-indigo-400">🛍️ Produtos</a>
    <a href="admin_avaliacoes.php" class="hover:text-indigo-400">⭐ Avaliações</a>
    <a href="admin_denuncias.php" class="text-indigo-400 font-semibold">🚨 Denúncias</a>
    <a href="admin_noticias.php" class="hover:text-indigo-400">📰 Notícias</a>
    <a href="admin_comunidades.php" class="hover:text-indigo-400">🌐 Comunidades</a>
    <a href="admin_compras.php" class="hover:text-indigo-400">🧾 Compras</a>
    <a href="admin_equipe.php" class="hover:text-indigo-400">🧑‍💼 Equipe</a>
    <a href="admin_configuracoes.php" class="hover:text-indigo-400">⚙️ Configurações</a>
  </nav>
</div>

<!-- Topbar -->
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

  <!-- Notificações & Usuário -->
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>
      <!-- Notificações -->
      <div class="relative">
        <button id="notifBtn" class="text-gray-600 hover:text-indigo-500 relative">
          <i class="fas fa-bell text-2xl"></i>
          <?php if(isset($notifCount) && $notifCount > 0): ?>
            <span id="notifCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5">
              <?= (int)$notifCount ?>
            </span>
          <?php endif; ?>
        </button>

        <ul id="notifDropdown" class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">

          <?php if (!empty($notificacoes)): ?>
            <?php foreach ($notificacoes as $n): ?>

              <li class="px-4 py-2 border-b last:border-b-0 hover:bg-gray-100 dark:hover:bg-gray-700">

                <a href="notificacao_ver.php?id=<?= isset($n['id']) ? (int)$n['id'] : 0 ?>"
                   class="block text-sm text-gray-800 dark:text-gray-200">

                  <?= htmlspecialchars(
                        mb_strimwidth(
                          $n['mensagem'] ?? 'Notificação inválida',
                          0,
                          100,
                          '...'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                      );
                  ?>

                  <div class="text-xs text-gray-500">
                    <?php
                      if (!empty($n['criada_em'])) {
                        echo date('d/m/Y H:i', strtotime($n['criada_em']));
                      } else {
                        echo '00/00/0000 00:00';
                      }
                    ?>
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

          <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
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



<!-- CONTEÚDO -->
<div class="ml-64 pt-20 p-6 max-w-7xl mx-auto">
  <h2 class="text-2xl font-bold mb-4">Denúncias de Usuários</h2>

  <div class="mb-4 space-x-2">
    <a href="?status=" class="px-3 py-1 rounded border <?= $filtro == '' ? 'bg-blue-200' : '' ?>">Todos</a>
    <a href="?status=pendente" class="px-3 py-1 rounded border <?= $filtro == 'pendente' ? 'bg-yellow-200' : '' ?>">Pendentes</a>
    <a href="?status=analisando" class="px-3 py-1 rounded border <?= $filtro == 'analisando' ? 'bg-orange-200' : '' ?>">Analisando</a>
    <a href="?status=resolvido" class="px-3 py-1 rounded border <?= $filtro == 'resolvido' ? 'bg-green-200' : '' ?>">Resolvidos</a>
  </div>

  <table class="w-full border text-sm bg-white shadow rounded">
    <thead class="bg-gray-200">
      <tr>
        <th class="p-2 border">ID</th>
        <th class="p-2 border">Denunciante</th>
        <th class="p-2 border">Denunciado</th>
        <th class="p-2 border">Motivo</th>
        <th class="p-2 border">Descrição</th>
        <th class="p-2 border">Data</th>
        <th class="p-2 border">Prova</th>
        <th class="p-2 border">Status</th>
        <th class="p-2 border">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($denuncia = $result->fetch_assoc()): ?>
        <tr class="hover:bg-gray-50">
          <td class="border p-2"><?= $denuncia['id'] ?></td>
          <td class="border p-2"><?= htmlspecialchars($denuncia['denunciante']) ?></td>
          <td class="border p-2"><?= htmlspecialchars($denuncia['denunciado']) ?></td>
          <td class="border p-2"><?= htmlspecialchars($denuncia['motivo']) ?></td>
          <td class="border p-2"><?= nl2br(htmlspecialchars($denuncia['descricao'])) ?></td>
          <td class="border p-2"><?= $denuncia['data_ocorrido'] ?></td>
          <td class="border p-2 text-center">
            <?php if (!empty($denuncia['arquivo'])): ?>
              <a href="../pages/reportar/uploads/denuncias/<?= urlencode($denuncia['arquivo']) ?>" target="_blank" class="text-blue-600 underline">Ver Prova</a>
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
          <td class="border p-2 text-center font-semibold"><?= $denuncia['status'] ?></td>
          <td class="border p-2 text-center space-x-1">
            <button onclick="atualizarStatus(<?= $denuncia['id'] ?>, 'analisando')" class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs">Analisar</button>
            <button onclick="atualizarStatus(<?= $denuncia['id'] ?>, 'resolvido')" class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs">Resolver</button>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
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
