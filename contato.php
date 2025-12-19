<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
session_start();


// Verifica se o modo manutenção está ativado para usuários comuns
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $stmt = $conn->prepare("SELECT nome, valor FROM configuracoes WHERE nome IN ('modo_manutencao', 'mensagem_manutencao')");
    $stmt->execute();
    $res = $stmt->get_result();

    $modo_manutencao = '0';
    $mensagem_manutencao = 'Estamos temporariamente em manutenção. Tente novamente em breve.';

    while ($row = $res->fetch_assoc()) {
        if ($row['nome'] === 'modo_manutencao') {
            $modo_manutencao = $row['valor'];
        }
        if ($row['nome'] === 'mensagem_manutencao') {
            $mensagem_manutencao = $row['valor'];
        }
    }

    if ($modo_manutencao === '1') {
        echo "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <title>Manutenção - GameZone</title>
            <style>
                body {
                    background-color: #f9fafb;
                    font-family: Arial, sans-serif;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    color: #333;
                    text-align: center;
                }
                h1 {
                    font-size: 2rem;
                    color: #4F46E5;
                }
                p {
                    max-width: 500px;
                    margin-top: 1rem;
                }
            </style>
        </head>
        <body>
            <h1>🔧 Modo Manutenção Ativado</h1>
            <p>" . htmlspecialchars($mensagem_manutencao) . "</p>
        </body>
        </html>";
        exit();
    }
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
  <meta charset="UTF-8" />
  <title>Contato - GameZone</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="./assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-6">
  <div class="bg-gray-800 p-8 rounded-lg shadow-lg max-w-lg w-full text-center">
    <h1 class="text-3xl font-bold mb-6 text-indigo-400">Fale com a equipe GameZone</h1>
    <p class="text-gray-300 mb-6">Escolha um dos canais abaixo para entrar em contato conosco:</p>

<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
        <!-- Logo e links principais -->
        <div class="flex items-center gap-6">
          <a class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">Game<span class="text-gray-800 dark:text-gray-100">Zone</span></a>

          <div class="hidden md:flex gap-4 items-center text-sm">
            <a href="index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>

            <div class="relative group">
              <button class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>
              <ul class="absolute hidden group-hover:block bg-white dark:bg-gray-800 shadow rounded mt-1 p-2 w-44 z-50">
                <li><a href="./pages/minhas_comunidades.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Minhas Comunidade</a></li>
                <li><a href="./pages/comunidade/chat.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Chat</a></li>
                <li><a href="./pages/comunidade/amigos.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Amigos</a></li>
                <li><a href="./pages/comunidade/conversas.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Conversas</a></li>
                <li><a href="./pages/comunidade/criar_comunidade.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Criar Comunidade</a></li>
              </ul>
            </div>

            <a href="./pages/comunidade/explorar_comunidades.php" class="hover:underline text-gray-700 dark:text-gray-300">Explorar</a>
            <a href="ranking.php" class="hover:underline text-gray-700 dark:text-gray-300">Ranking</a>
          </div>

          <a href="loja.php" class="hover:underline text-gray-700 dark:text-gray-300">Loja</a>
        </div>

        <div class="relative flex items-center gap-4">
          <?php if (isset($_SESSION['email'])): ?>
            <!-- Notificações -->
            <div class="relative">
              <button id="notifBtn" class="text-gray-600 hover:text-indigo-500 relative">
                <i class="fas fa-bell text-2xl"></i>
                <?php if($notifCount > 0): ?>
                  <span id="notifCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5"><?= $notifCount ?></span>
                <?php endif; ?>
              </button>
              <ul id="notifDropdown" class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
                <li class="px-4 py-2 text-gray-500">Carregando...</li>
              </ul>
            </div>

            <!-- Menu do usuário -->
            <div class="relative">
              <button id="userMenuBtn" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-500">
                <i class="fas fa-user-circle text-2xl mr-1"></i>
                <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
              </button>
              <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
                <li><a href="./conta/perfil.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>
                <?php if ($_SESSION['tipo_usuario'] === 'admin'): ?>
                  <li><a href="admin/admin_painel.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
                <?php endif; ?>
                <li><a href="./conta/configuracoes.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>
                <li><a href="./pages/security/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>
              </ul>
            </div>
          <?php else: ?>
            <div class="flex gap-2">
              <a href="./pages/security/entrar.php" class="text-sm hover:underline">Entrar</a>
              <a href="./pages/security/cadastrar.html" class="text-sm hover:underline">Cadastrar</a>
            </div>
          <?php endif; ?>
        </div>
      </nav>

    <div class="flex flex-col gap-4">

      <!-- WhatsApp (ícone de chat) -->
      <a href="seulink" 
         target="_blank"
         class="flex items-center justify-center gap-2 bg-green-500 hover:bg-green-400 text-white font-semibold py-2 px-4 rounded transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8a9 9 0 100-18 9 9 0 000 18z" />
        </svg>
        WhatsApp
      </a>

      <!-- Twitter (X) -->
      <a href="https://twitter.com/seuPerfil" 
         target="_blank"
         class="flex items-center justify-center gap-2 bg-blue-500 hover:bg-blue-400 text-white font-semibold py-2 px-4 rounded transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M22.46 6c-.77.35-1.5.58-2.24.69a4.07 4.07 0 001.8-2.24 8.25 8.25 0 01-2.6.99A4.13 4.13 0 0016.1 4a4.13 4.13 0 00-4.13 4.13c0 .32.04.64.1.95A11.7 11.7 0 013 5.15a4.12 4.12 0 001.28 5.5c-.6-.02-1.16-.18-1.65-.45v.05c0 2 .93 3.77 2.46 4.75a4.13 4.13 0 01-1.86.07 4.14 4.14 0 003.86 2.88A8.29 8.29 0 012 19.53a11.66 11.66 0 006.29 1.84c7.55 0 11.69-6.26 11.69-11.69 0-.18 0-.36-.01-.54A8.36 8.36 0 0024 5.55a8.2 8.2 0 01-2.37.65z"/>
        </svg>
        Twitter (X)
      </a>

      <!-- Discord -->
      <a href="https://discord.gg/seulink" 
         target="_blank"
         class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2 px-4 rounded transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
          <path d="M20.317 4.369a19.791 19.791 0 00-4.885-1.515.07.07 0 00-.075.035c-.21.372-.444.86-.608 1.24a18.203 18.203 0 00-5.44 0 12.14 12.14 0 00-.622-1.24.07.07 0 00-.074-.035 19.736 19.736 0 00-4.886 1.515.063.063 0 00-.028.027C2.291 9.042 1.6 13.579 2.01 18.057a.08.08 0 00.031.056c2.053 1.504 4.042 2.416 5.993 3.032a.078.078 0 00.084-.027c.46-.63.873-1.295 1.226-1.994a.076.076 0 00-.041-.106 11.507 11.507 0 01-1.646-.798.077.077 0 01-.008-.13c.111-.084.222-.17.328-.26a.074.074 0 01.077-.01c3.443 1.577 7.16 1.577 10.558 0a.074.074 0 01.078.009c.107.091.218.177.33.261a.077.077 0 01-.006.129 11.261 11.261 0 01-1.648.797.076.076 0 00-.04.107c.36.699.774 1.364 1.225 1.993a.076.076 0 00.084.028c1.951-.616 3.94-1.528 5.994-3.032a.078.078 0 00.03-.056c.5-5.177-.838-9.655-3.548-13.661a.062.062 0 00-.03-.028zM8.02 15.331c-1.182 0-2.155-1.085-2.155-2.419 0-1.333.955-2.419 2.155-2.419 1.21 0 2.175 1.096 2.155 2.419 0 1.334-.955 2.419-2.155 2.419zm7.974 0c-1.182 0-2.155-1.085-2.155-2.419 0-1.333.955-2.419 2.155-2.419 1.21 0 2.175 1.096 2.155 2.419 0 1.334-.944 2.419-2.155 2.419z"/>
        </svg>
        Discord
      </a>

      <!-- Instagram -->
      <a href="https://instagram.com/seuPerfil" 
         target="_blank"
         class="flex items-center justify-center gap-2 bg-pink-500 hover:bg-pink-400 text-white font-semibold py-2 px-4 rounded transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M7.75 2A5.75 5.75 0 002 7.75v8.5A5.75 5.75 0 007.75 22h8.5A5.75 5.75 0 0022 16.25v-8.5A5.75 5.75 0 0016.25 2h-8.5zM4.5 7.75a3.25 3.25 0 013.25-3.25h8.5a3.25 3.25 0 013.25 3.25v8.5a3.25 3.25 0 01-3.25 3.25h-8.5A3.25 3.25 0 014.5 16.25v-8.5zM12 8a4 4 0 100 8 4 4 0 000-8zm0 1.5a2.5 2.5 0 110 5 2.5 2.5 0 010-5zm5.25-.75a.75.75 0 100 1.5.75.75 0 000-1.5z"/>
        </svg>
        Instagram
      </a>

    </div>
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


  <footer class="dark:bg-gray-900 text-center py-6 text-gray-400 text-sm border-t border-gray-700 mt-14">
    <div class="mb-2">
        <a href="contato.php" class="hover:text-indigo-400 transition mx-2">Contato</a> |
        <a href="privacidade.php" class="hover:text-indigo-400 transition mx-2">Privacidade</a> |
        <a href="sobre.php" class="hover:text-indigo-400 transition mx-2">Sobre</a> |
        <a href="termos.php" class="hover:text-indigo-400 transition mx-2">Termos</a> |
        <a href="equipe.php" class="hover:text-indigo-400 transition mx-2">Equipe</a>
    </div>
    <div>© <?= date('Y') ?> GameZone - Conectando jogadores.</div>
  </footer>



</body>
</html>
