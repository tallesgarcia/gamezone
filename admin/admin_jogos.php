<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
session_start();

// Verifica se o modo manutenção está ativado
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Reset de Jogos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resetar'])) {
    $conn->query("DELETE FROM jogos");
    $conn->query("ALTER TABLE jogos AUTO_INCREMENT = 1");
    header("Location: admin_jogos.php?reset=ok");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM jogos ORDER BY criado_em DESC");
$stmt->execute();
$jogos = $stmt->get_result();


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
  <title>Gerenciar Jogos - Admin | GameZone</title>
    <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="./assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 flex">

<!-- Sidebar -->
<div class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white shadow-lg">
  <div class="p-4 font-bold text-xl text-indigo-400">Admin GameZone</div>
  <nav class="flex flex-col gap-2 mt-4 px-4">
    <a href="admin_painel.php" class="hover:text-indigo-400">📊 Painel</a>
    <a href="admin_usuarios.php" class="hover:text-indigo-400">👥 Usuários</a>
    <a href="admin_jogos.php" class="text-indigo-400 font-semibold">🎮 Jogos</a>
    <a href="admin_produtos.php" class="hover:text-indigo-400">🛍️ Produtos</a>
    <a href="admin_avaliacoes.php" class="hover:text-indigo-400">⭐ Avaliações</a>
    <a href="admin_denuncias.php" class="hover:text-indigo-400">🚨 Denúncias</a>
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



<!-- Conteúdo -->
<main class="ml-64 pt-20 p-6 w-full">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">🎮 Jogos Cadastrados</h1>
    <a href="jogos_adicionar.php" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded text-sm">+ Novo Jogo</a>
  </div>

  <?php if (isset($_GET['reset']) && $_GET['reset'] === 'ok'): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">Todos os jogos foram apagados com sucesso.</div>
  <?php endif; ?>

  <!-- Botão de Reset -->
  <form id="resetForm" method="POST">
    <input type="hidden" name="resetar" value="1">
    <button type="button" onclick="confirmarReset()" class="mb-4 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
      🧹 Resetar Jogos
    </button>
  </form>

  <!-- Tabela -->
  <div class="overflow-x-auto">
    <table class="min-w-full bg-white shadow rounded">
      <thead class="bg-gray-200 text-sm">
        <tr>
          <th class="py-3 px-4">#</th>
          <th class="py-3 px-4">Nome</th>
          <th class="py-3 px-4">Gênero</th>
          <th class="py-3 px-4">Plataforma</th>
          <th class="py-3 px-4">Preço</th>
          <th class="py-3 px-4">Lançamento</th>
          <th class="py-3 px-4">Ações</th>
        </tr>
      </thead>
      <tbody class="text-sm">
        <?php if ($jogos->num_rows === 0): ?>
          <tr><td colspan="7" class="py-4 px-4 text-center text-gray-500">Nenhum jogo cadastrado.</td></tr>
        <?php else: ?>
          <?php while ($jogo = $jogos->fetch_assoc()): ?>
            <tr class="border-t hover:bg-gray-50">
              <td class="py-2 px-4"><?= $jogo['id'] ?></td>
              <td class="py-2 px-4"><?= htmlspecialchars($jogo['nome']) ?></td>
              <td class="py-2 px-4"><?= htmlspecialchars($jogo['genero']) ?></td>
              <td class="py-2 px-4"><?= htmlspecialchars($jogo['plataforma']) ?></td>
              <td class="py-2 px-4">R$ <?= number_format($jogo['preco'], 2, ',', '.') ?></td>
              <td class="py-2 px-4"><?= date('d/m/Y', strtotime($jogo['data_lancamento'])) ?></td>
              <td class="py-2 px-4 flex gap-2">
                <a href="jogos_editar.php?id=<?= $jogo['id'] ?>" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded text-xs">Editar</a>
                <a href="acoes/jogos_excluir.php?id=<?= $jogo['id'] ?>" onclick="return confirm('Excluir este jogo?')" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded text-xs">Excluir</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
function confirmarReset() {
  Swal.fire({
    title: "Tem certeza?",
    text: "Todos os jogos cadastrados serão excluídos. Isso não poderá ser desfeito!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, apagar!",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById("resetForm").submit();
    }
  });
}
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
