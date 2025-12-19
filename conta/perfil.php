<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
session_start(); // Garanta que isso está no topo de perfil.php

// ==============================
// VERIFICAÇÃO DE LOGIN
// ==============================
if (!isset($_SESSION['user_id'])) {
    // Redireciona se o usuário não estiver logado
    header("Location: ../security/entrar.php");
    exit;
}
$userId = $_SESSION['user_id'];


if (isset($_SESSION['mensagem_sucesso'])) {
    // Exibe a mensagem (Ajustei a classe para melhor visualização)
    echo "<div class='p-3 rounded bg-green-600 mb-4 fixed top-20 left-1/2 -translate-x-1/2 z-50 shadow-lg text-white'>" . htmlspecialchars($_SESSION['mensagem_sucesso']) . "</div>";
    
    // Remove a mensagem para não mostrar de novo
    unset($_SESSION['mensagem_sucesso']);
}


// ==============================
// MODO MANUTENÇÃO (Seu código existente)
// ==============================
// ... (Deixe seu código de Modo Manutenção aqui) ...


// ==============================
// 🌟 NOVO: BUSCAR DADOS DO USUÁRIO, XP E AVATAR 🌟
// ==============================
$stmt_user = $conn->prepare("SELECT nome, xp, avatar FROM usuarios WHERE id = ?");
if (!$stmt_user) {
    die("Erro no prepare (usuário): " . $conn->error);
}
$stmt_user->bind_param("i", $userId);
$stmt_user->execute();
$usuario = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$usuario) {
    // Caso o ID na sessão não encontre um usuário
    session_destroy();
    header("Location: ../security/entrar.php");
    exit;
}

$nomeUsuario = htmlspecialchars($usuario['nome']);
$xpTotal = $usuario['xp'];

// Processamento do Avatar (BLOB)
// Seu campo `avatar` é BLOB. Convertemos para Base64 para exibir.
$avatar_src = "../uploads/perfis/default.png"; // Avatar padrão (placeholder)
if (!empty($usuario['avatar'])) {
    // Assumindo que o avatar é uma imagem, e para BLOB é melhor usar base64
    // Se o avatar for um caminho de arquivo, ajuste esta lógica.
    // Pelo seu dump, o usuário 1 tem 'avatar': 0x64656661756c745f6176617461722e706e67 (que é 'default_avatar.png')
    // Se você armazena o *caminho* como BLOB, o ideal é convertê-lo para string antes.
    
    // Opção 1: Se o BLOB armazena o CONTEÚDO BINÁRIO da imagem 
    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($usuario['avatar']);
    $avatar_src = 'data:' . $mime . ';base64,' . base64_encode($usuario['avatar']);
  
}


// Cálculo de Nível e XP
$xpPorNivel = 500; // XP necessário para subir de nível (baseado no seu HTML)
$nivel = floor($xpTotal / $xpPorNivel);
$xpNoNivelAtual = $xpTotal % $xpPorNivel;
$percentualXP = ($xpPorNivel > 0) ? ($xpNoNivelAtual / $xpPorNivel) * 100 : 0;
if ($percentualXP > 100) $percentualXP = 100;

// ==============================
// 🌟 NOVO: BUSCAR CONQUISTAS (Missões Concluídas) 🌟
// Usaremos as tabelas `usuario_missoes` e `missoes`
// ==============================
$conquistas = [];
$stmt_conquistas = $conn->prepare(
    "SELECT m.titulo, m.descricao 
     FROM usuario_missoes um 
     JOIN missoes m ON um.missao_id = m.id 
     WHERE um.usuario_id = ? AND um.concluido = 1 
     ORDER BY um.concluido_em DESC"
);
if ($stmt_conquistas) {
    $stmt_conquistas->bind_param("i", $userId);
    $stmt_conquistas->execute();
    $result_conquistas = $stmt_conquistas->get_result();
    while ($conquista = $result_conquistas->fetch_assoc()) {
        $conquistas[] = $conquista;
    }
    $stmt_conquistas->close();
}


// ==============================
// NOTIFICAÇÕES
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perfil - GameZone</title>
<script src="https://cdn.tailwindcss.com"></script> 
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
  <link rel="stylesheet" href="./assets/css/estilos.css"> 
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>
<body class="dark bg-gray-900 text-white min-h-screen">

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
<main class="pt-24 px-6 max-w-4xl mx-auto">
  <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
    <div class="flex items-center gap-6">
            <img src="<?= $avatar_src ?>" alt="Avatar do Usuário" class="w-24 h-24 rounded-full border-4 border-indigo-500 shadow-md">
      <div>
                <h2 class="text-2xl font-bold"><?= $nomeUsuario ?></h2>
        
                <p class="text-sm text-gray-500 dark:text-gray-400">Nível <span class="font-semibold text-indigo-600"><?= $nivel ?></span></p>
        
                <div class="w-64 h-3 bg-gray-200 dark:bg-gray-700 rounded-full mt-2">
          <div class="h-3 bg-indigo-500 rounded-full" style="width: <?= $percentualXP ?>%;"></div>
        </div>
        
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?= $xpNoNivelAtual ?> / <?= $xpPorNivel ?> XP</p>
      </div>
    </div>

        <div class="mt-8">
      <h3 class="text-lg font-semibold mb-4">🏆 Conquistas (<?= count($conquistas) ?>)</h3>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <?php if (empty($conquistas)): ?>
          <p class="col-span-3 text-gray-500">Nenhuma conquista desbloqueada ainda. Complete missões!</p>
        <?php else: ?>
          <?php foreach ($conquistas as $conquista): ?>
            <div class="flex flex-col items-center p-4 bg-yellow-100 dark:bg-yellow-700 rounded-lg shadow-md border border-yellow-500 transition hover:scale-105" title="<?= htmlspecialchars($conquista['descricao']) ?>">
              <i class="fas fa-trophy text-xl mb-2 text-yellow-800 dark:text-yellow-200"></i>
              <span class="text-sm font-semibold text-yellow-800 dark:text-yellow-200"><?= htmlspecialchars($conquista['titulo']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        
                      </div>
    </div>

        <div class="mt-10 text-center">
      <a href="../conta/missoes.php" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg font-semibold transition">
        🚀 Complete Missões
      </a>
    </div>
  </section>
</main>

<script>
  // Dropdown notificações
  const notifBtn = document.getElementById("notifBtn");
  const notifDropdown = document.getElementById("notifDropdown");
  const userBtn = document.getElementById("userMenuBtn");
  const userDropdown = document.getElementById("userDropdown");
  const notifCountEl = document.getElementById("notifCount");

  // Atualiza notificações via AJAX
  function atualizarNotificacoes() { 
    fetch('buscar_notificacoes.php') 
      .then(res => res.json()) 
      .then(data => {
        if(notifCountEl) { 
          notifCountEl.textContent = data.count;
          notifCountEl.style.display = data.count>0?'inline-block':'none'; 
        }
        if(notifDropdown) { 
          notifDropdown.innerHTML = '';
          if(data.notificacoes.length===0) notifDropdown.innerHTML='<li class="px-4 py-2 text-gray-500">Nenhuma notificação</li>';
          else data.notificacoes.forEach(n=>{
            const li=document.createElement('li');
            li.className='px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700'+(n.lida==0?' font-bold':'');
            li.innerHTML=`${n.mensagem} <span class="text-xs text-gray-400 float-right">${new Date(n.criada_em).toLocaleString('pt-BR',{ day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' })}</span>`;
            notifDropdown.appendChild(li);
          });
        }
      }); 
  }
  setInterval(atualizarNotificacoes,5000);
  atualizarNotificacoes();

  // Toggle dropdown notificações
  if (notifBtn && notifDropdown) {
    notifBtn.addEventListener("click", e=>{
      e.stopPropagation();
      notifDropdown.classList.toggle("hidden");
      if(userDropdown && !userDropdown.classList.contains("hidden")) userDropdown.classList.add("hidden");
      if(!notifDropdown.classList.contains("hidden")) fetch('marcar_notificacoes_lidas.php').then(()=>{if(notifCountEl) notifCountEl.style.display='none';});
    });
  }

  // Toggle dropdown usuário
  if(userBtn && userDropdown){
    userBtn.addEventListener("click", e=>{
      e.stopPropagation();
      userDropdown.classList.toggle("hidden");
      if(notifDropdown && !notifDropdown.classList.contains("hidden")) notifDropdown.classList.add("hidden");
    });
  }

  // Fecha dropdowns ao clicar fora
  window.addEventListener("click",()=>{
    if(userDropdown) userDropdown.classList.add("hidden");
    if(notifDropdown) notifDropdown.classList.add("hidden");
  });
</script>

</body>
</html>