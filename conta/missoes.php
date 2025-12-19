<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
session_start();

// =====================
// Verifica Modo Manutenção
// =====================
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $stmt = $conn->prepare("SELECT nome, valor FROM configuracoes WHERE nome IN ('modo_manutencao', 'mensagem_manutencao')");
    $stmt->execute();
    $res = $stmt->get_result();

    $modo_manutencao = '0';
    $mensagem_manutencao = 'Estamos temporariamente em manutenção. Tente novamente em breve.';

    while ($row = $res->fetch_assoc()) {
        if ($row['nome'] === 'modo_manutencao') $modo_manutencao = $row['valor'];
        if ($row['nome'] === 'mensagem_manutencao') $mensagem_manutencao = $row['valor'];
    }

    if ($modo_manutencao === '1') {
        echo "<!DOCTYPE html>
        <html lang='pt-BR'><head><meta charset='UTF-8'><title>Manutenção</title></head>
        <body style='font-family:Arial,sans-serif;text-align:center;margin-top:100px;'>
        <h1>🔧 Modo Manutenção Ativado</h1>
        <p>" . htmlspecialchars($mensagem_manutencao) . "</p>
        </body></html>";
        exit();
    }
}

// ==============================
// NOTIFICAÇÕES (EXEMPLO)
// ==============================
$notifCount = 0;
if (isset($_SESSION['user_id'])) {
    $notificacoes = [];
    $stmt = $conn->prepare("SELECT id, mensagem, lida, criada_em FROM notificacoes WHERE usuario_id=? ORDER BY criada_em DESC LIMIT 5");
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
// =====================
// Buscar missões do banco
// =====================
$missoes = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $res = $conn->query("SELECT m.id, m.titulo, m.descricao, m.xp, m.tipo,
        COALESCE(um.concluido, 0) as concluido
        FROM missoes m
        LEFT JOIN usuario_missoes um
        ON m.id = um.missao_id AND um.usuario_id = $user_id
        ORDER BY m.tipo ASC, m.id ASC");

    while ($row = $res->fetch_assoc()) {
        $missoes[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Missões | GameZone</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-200 font-rajdhani">

<!-- NAVBAR -->
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
  <!-- Logo e links principais -->
  <div class="flex items-center gap-6">
    <a class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">Game<span class="text-gray-800 dark:text-gray-100">Zone</span></a>

    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="../index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>

      <div class="relative group">
        <button class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>
        <ul class="absolute hidden group-hover:block bg-white dark:bg-gray-800 shadow rounded mt-1 p-2 w-44 z-50">
          <li><a href="./../pages/minhas_comunidades.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Minhas Comunidades</a></li>
          <li><a href="./../pages/comunidade/chat.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Chat</a></li>
          <li><a href="./../pages/comunidade/amigos.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Amigos</a></li>
          <li><a href="./../pages/comunidade/conversas.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Conversas</a></li>
          <li><a href="./../pages/comunidade/criar_comunidade.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Criar Comunidade</a></li>
        </ul>
      </div>

      <a href="./../pages/comunidade/explorar_comunidades.php" class="hover:underline text-gray-700 dark:text-gray-300">Explorar</a>
      <a href="../ranking.php" class="hover:underline text-gray-700 dark:text-gray-300">Ranking</a>
      <a href="loja.php" class="hover:text-indigo-400 transition">Loja</a>
    </div>
  </div>

    <!-- Menu do usuário com notificações -->
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>
      <!-- Notificações -->
      <div class="relative">
        <button id="notifBtn" class="text-gray-600 dark:text-gray-300 hover:text-indigo-500 relative">
          <i class="fas fa-bell text-2xl"></i>
          <?php if($notifCount > 0): ?>
            <!-- Badge de notificações -->
          <span id="notifCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5">
            <?= $notifCount ?>
          </span>
          <?php endif; ?>
        </button>
        <ul id="notifDropdown" class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <!-- Notificações carregadas via JS/AJAX -->
        </ul>
      </div>

      <!-- Usuário -->
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-500">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>
        <ul id="userDropdown" class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <li><a href="perfil.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>
          <?php if ($_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="../admin/admin_painel.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
          <?php endif; ?>
          <li><a href="configuracoes.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>
          <li><a href="../pages/security/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>
        </ul>
      </div>
    <?php else: ?>
      <div class="flex gap-2">
        <a href="./../pages/security/entrar.php" class="text-sm hover:underline">Entrar</a>
        <a href="./../pages/security/cadastrar.html" class="text-sm hover:underline">Cadastrar</a>
      </div>
    <?php endif; ?>
  </div>
</nav>

<!-- MISSÕES -->
<main class="pt-24 px-6 max-w-5xl mx-auto">
    <h1 class="text-3xl font-bold mb-8 text-center">🎯 Missões</h1>

    <?php
    $tipos = ['diaria'=>'⏳ Missões Diárias', 'semanal'=>'📅 Missões Semanais', 'geral'=>'🏆 Missões Gerais'];
    foreach ($tipos as $tipoKey => $tipoTitulo):
        echo "<section class='mb-10'>";
        echo "<h2 class='text-xl font-semibold mb-4'>$tipoTitulo</h2>";
        echo "<div class='grid md:grid-cols-2 gap-6'>";
        foreach ($missoes as $missao):
            if ($missao['tipo'] !== $tipoKey) continue;
            $concluido = intval($missao['concluido']) === 1;
    ?>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md flex justify-between items-center">
        <div>
            <h3 class="font-bold"><?= htmlspecialchars($missao['titulo']) ?></h3>
            <p class="text-sm text-gray-500">Ganhe +<?= $missao['xp'] ?> XP</p>
        </div>
        <?php if($concluido): ?>
            <span class="text-green-500 font-bold">✔ Concluído</span>
        <?php else: ?>
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 concluirMissao" data-id="<?= $missao['id'] ?>">Concluir</button>
        <?php endif; ?>
    </div>
    <?php
        endforeach;
        echo "</div></section>";
    endforeach;
    ?>
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

// Concluir missão via AJAX
document.querySelectorAll('.concluirMissao').forEach(btn => {
  btn.addEventListener('click', () => {
    const missaoId = btn.dataset.id;
    fetch('concluir_missao.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `missao_id=${missaoId}`
    })
    .then(res => res.json())
    .then(data => {
      if(data.success){
        const span = document.createElement('span');
        span.className = 'text-green-500 font-bold';
        span.textContent = '✔ Concluído';
        btn.parentNode.replaceChild(span, btn);
        if(data.xp>0) alert(`Você ganhou ${data.xp} XP!`);
      } else {
        alert(data.error || 'Erro ao concluir missão');
      }
    }).catch(() => alert('Erro de conexão'));
  });
});
</script>
</body>
</html>
