<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

session_start();

//Verifica se o usuário é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Carrega as configurações atuais
$configs = [];
$res = $conn->query("SELECT nome, valor FROM configuracoes");
while ($row = $res->fetch_assoc()) {
    $configs[$row['nome']] = $row['valor'];
}

// Salvar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novas_configs = [
        'modo_manutencao' => isset($_POST['modo_manutencao']) ? '1' : '0',
        'tema_padrao' => $_POST['tema_padrao'] ?? 'claro',
        'limite_membros_comunidade' => $_POST['limite_membros_comunidade'] ?? '100',
        'mensagem_manutencao' => $_POST['mensagem_manutencao'] ?? '',
        'habilitar_loja' => isset($_POST['habilitar_loja']) ? '1' : '0',
        'taxa_transacao' => $_POST['taxa_transacao'] ?? '0.05'
    ];

    $stmt = $conn->prepare("UPDATE configuracoes SET valor = ? WHERE nome = ?");
    $stmt->bind_param("ss", $valor, $nome);

    foreach ($novas_configs as $nome => $valor) {
        $stmt->execute();
        $configs[$nome] = $valor;
    }

    $mensagem = "✅ Todas as configurações foram salvas com sucesso.";
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
  <title>Configurações | Admin | GameZone</title>
  <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/estilos.css">
</head>
<body class="bg-gray-100 flex">
  <!-- Sidebar -->
<div class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white shadow-lg">
  <div class="p-4 font-bold text-xl text-indigo-400">Admin GameZone</div>
  <nav class="flex flex-col gap-2 mt-4 px-4">
    <a href="admin_painel.php" class="hover:text-indigo-400">📊 Painel</a>
    <a href="admin_usuarios.php" class="hover:text-indigo-400">👥 Usuários</a>
    <a href="admin_jogos.php" class="hover:text-indigo-400">🎮 Jogos</a>
    <a href="admin_produtos.php" class="hover:text-indigo-400">🛍️ Produtos</a>
    <a href="admin_avaliacoes.php" class="hover:text-indigo-400">⭐ Avaliações</a>
    <a href="admin_denuncias.php" class="hover:text-indigo-400">🚨 Denúncias</a>
    <a href="admin_noticias.php" class="hover:text-indigo-400">📰 Notícias</a>
    <a href="admin_comunidades.php" class="hover:text-indigo-400">🌐 Comunidades</a>
    <a href="admin_compras.php" class="hover:text-indigo-400">🧾 Compras</a>
    <a href="admin_equipe.php" class="hover:text-indigo-400">🧑‍💼 Equipe</a>
    <a href="admin_configuracoes.php" class="text-indigo-400 font-semibold">⚙️ Configurações</a>
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



  <main class="ml-64 p-6 w-full pt-20">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">⚙️ Configurações da Plataforma</h1>

    <?php if (!empty($mensagem)): ?>
      <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?= $mensagem ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-6 rounded shadow space-y-6 max-w-2xl">
      <!-- Modo Manutenção -->
      <div class="flex items-center">
        <input type="checkbox" name="modo_manutencao" id="modo_manutencao" class="mr-3"
               <?= $configs['modo_manutencao'] === '1' ? 'checked' : '' ?>>
        <label for="modo_manutencao" class="text-sm text-gray-700">
          Ativar modo manutenção (site indisponível para usuários comuns)
        </label>
      </div>

      <!-- Tema padrão -->
      <div>
        <label for="tema_padrao" class="block text-sm font-medium text-gray-700">Tema padrão</label>
        <select name="tema_padrao" id="tema_padrao" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
          <option value="claro" <?= $configs['tema_padrao'] === 'claro' ? 'selected' : '' ?>>Claro</option>
          <option value="escuro" <?= $configs['tema_padrao'] === 'escuro' ? 'selected' : '' ?>>Escuro</option>
          <option value="sistema" <?= $configs['tema_padrao'] === 'sistema' ? 'selected' : '' ?>>Detectar pelo sistema</option>
        </select>
      </div>

      <!-- Limite de membros -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Limite de membros por comunidade</label>
        <input type="number" name="limite_membros_comunidade" min="1"
               value="<?= htmlspecialchars($configs['limite_membros_comunidade']) ?>"
               class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
      </div>

      <!-- Mensagem de manutenção -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Mensagem de manutenção</label>
        <textarea name="mensagem_manutencao" rows="2"
                  class="mt-1 block w-full border border-gray-300 rounded px-3 py-2"><?= htmlspecialchars($configs['mensagem_manutencao']) ?></textarea>
      </div>

      <!-- Habilitar loja -->
      <div class="flex items-center">
        <input type="checkbox" name="habilitar_loja" id="habilitar_loja" class="mr-3"
               <?= $configs['habilitar_loja'] === '1' ? 'checked' : '' ?>>
        <label for="habilitar_loja" class="text-sm text-gray-700">Habilitar loja na plataforma</label>
      </div>

      <!-- Taxa de transação -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Taxa de transação (%)</label>
        <input type="number" step="0.01" min="0" max="1" name="taxa_transacao"
               value="<?= htmlspecialchars($configs['taxa_transacao']) ?>"
               class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
      </div>

      <!-- Botão -->
      <div class="flex justify-end">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded text-sm">
          💾 Salvar Configurações
        </button>
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
