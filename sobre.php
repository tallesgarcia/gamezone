<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
session_start();


// ==============================
// VERIFICA MODO MANUTENÇÃO
// ==============================
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $stmt = $conn->prepare("SELECT nome, valor FROM configuracoes WHERE nome IN ('modo_manutencao', 'mensagem_manutencao')");
    $stmt->execute();
    $stmt->store_result(); // NECESSÁRIO antes de bind_result no MySQLi
    $stmt->bind_result($nomeConfig, $valorConfig);

    $modo_manutencao = '0';
    $mensagem_manutencao = 'Estamos temporariamente em manutenção. Tente novamente em breve.';

    while ($stmt->fetch()) {
        if ($nomeConfig === 'modo_manutencao') {
            $modo_manutencao = $valorConfig;
        }
        if ($nomeConfig === 'mensagem_manutencao') {
            $mensagem_manutencao = $valorConfig;
        }
    }
    $stmt->close();

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

$user_logado   = isset($_SESSION['user_id']);
$nome_usuario  = $user_logado ? ($_SESSION['nome'] ?? 'Usuário') : null;
$tipo_usuario  = $_SESSION['tipo_usuario'] ?? null;


// ==============================
// NOTIFICAÇÕES
// ==============================
$notificacoes = [];
$notifCount = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id, mensagem, lida, criada_em FROM notificacoes WHERE usuario_id = ? ORDER BY criada_em DESC LIMIT 5");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($nid, $nmsg, $nlida, $ncriada);

    while ($stmt->fetch()) {
        $notificacoes[] = [
            'id'        => $nid,
            'mensagem'  => $nmsg,
            'lida'      => $nlida,
            'criada_em' => $ncriada
        ];
        if ($nlida == 0) {
            $notifCount++;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Sobre - GameZone</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col">

  <!-- Header -->
  <header class="bg-gray-800 shadow">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
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
    </div>
  </header>

  <!-- Conteúdo -->
  <main class="container mx-auto px-4 py-10 max-w-4xl flex-grow">
    <h1 class="text-4xl font-bold text-indigo-400 mb-6">Sobre o GameZone</h1>

    <p class="text-gray-300 mb-6">
      O <strong>GameZone</strong> é uma plataforma criada para conectar jogadores, comunidades e desenvolvedores de jogos em um só lugar.
      Aqui você pode encontrar comunidades temáticss, participar de fóruns, adicionar amigos e acompanhar os jogos mais populares da atualidade.
    </p>

    <!-- Missão, Visão, Valores -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center mb-12">
      <div class="bg-gray-800 rounded-xl p-6 shadow-md">
        <h2 class="text-xl font-semibold text-indigo-300 mb-2">Missão</h2>
        <p class="text-gray-400">Oferecer uma rede social para gamers com recursos colaborativos, seguros e divertidos.</p>
      </div>
      <div class="bg-gray-800 rounded-xl p-6 shadow-md">
        <h2 class="text-xl font-semibold text-indigo-300 mb-2">Visão</h2>
        <p class="text-gray-400">Ser referência nacional em integração social para jogadores e desenvolvedores.</p>
      </div>
      <div class="bg-gray-800 rounded-xl p-6 shadow-md">
        <h2 class="text-xl font-semibold text-indigo-300 mb-2">Valores</h2>
        <p class="text-gray-400">Inovação, acessibilidade, respeito, comunidade e segurança.</p>
      </div>
    </div>

    <!-- Equipe -->
    <h2 class="text-2xl font-bold text-indigo-300 mb-4">Nossa Equipe</h2>
    <ul class="space-y-2 text-gray-300 mb-12">
      <li>🎮 <strong>Talles Garcia</strong> — Fundador e Desenvolvedor Backend</li>
      <li>🎨 <strong></strong> — UI/UX Designer</li>
      <li>🛠️ <strong></strong> — Desenvolvedor Frontend</li>
      <li>📢 <strong></strong> — Comunidade e Suporte</li>
    </ul>

    <!-- Linha do Tempo -->
    <h2 class="text-2xl font-bold text-indigo-300 mb-4">Linha do Tempo do Projeto</h2>
    <ol class="relative border-l border-gray-700 pl-6 space-y-6 text-gray-400 mb-10">
      <li>
        <div class="absolute w-3 h-3 bg-indigo-500 rounded-full -left-1.5 mt-1"></div>
        <time class="text-sm text-gray-500">Fevereiro 2025</time>
        <h3 class="font-semibold text-white">Início do desenvolvimento</h3>
        <p class="text-sm">A ideia do GameZone foi criada e a arquitetura do sistema foi planejada.</p>
      </li>
      <li>
        <div class="absolute w-3 h-3 bg-indigo-500 rounded-full -left-1.5 mt-1"></div>
        <time class="text-sm text-gray-500">Março 2025</time>
        <h3 class="font-semibold text-white">Protótipo inicial</h3>
        <p class="text-sm">Primeiras funcionalidades implementadas: cadastro, login e comunidade básica.</p>
      </li>
      <li>
        <div class="absolute w-3 h-3 bg-indigo-500 rounded-full -left-1.5 mt-1"></div>
        <time class="text-sm text-gray-500">Maio - Junho 2025</time>
        <h3 class="font-semibold text-white">Painel administrativo</h3>
        <p class="text-sm">Administração de usuários, vendas e comunidades integrado com gráficos e dados reais.</p>
      </li>
      <li>
        <div class="absolute w-3 h-3 bg-indigo-500 rounded-full -left-1.5 mt-1"></div>
        <time class="text-sm text-gray-500">Julho - Setembro 2025</time>
        <h3 class="font-semibold text-white">Chat, Notificações e Comunidades</h3>
        <p class="text-sm">Chat, criação de comunidades e um sistema de notificações em sincronização em tempo real.</p>
      </li>
    </ol>

    <!-- Botão Contato -->
    <div class="text-center">
      <a href="contato.php" class="inline-block bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-3 px-6 rounded-lg transition">
        📩 Entrar em Contato com a Equipe
      </a>
    </div>

    <p class="text-gray-500 text-sm mt-10">
      Última atualização: 12 de Setembro de 2025
    </p>
  </main>

  <!-- Rodapé -->
  <footer class="text-center py-6 text-gray-400 text-sm border-t border-gray-700 mt-14">
    <div class="mb-2">
        <a href="contato.php" class="hover:text-indigo-400 transition mx-2">Contato</a> |
        <a href="privacidade.php" class="hover:text-indigo-400 transition mx-2">Privacidade</a> |
        <a href="sobre.php" class="hover:text-indigo-400 transition mx-2">Sobre</a> |
        <a href="termos.php" class="hover:text-indigo-400 transition mx-2">Termos</a> |
        <a href="equipe.php" class="hover:text-indigo-400 transition mx-2">Equipe</a>
    </div>
    <div>© <?= date('Y') ?> GameZone - Conectando jogadores.</div>
  </footer>
  <script>
  // Dropdown usuário e notificações
const userBtn = document.getElementById("userMenuBtn");
const userDropdown = document.getElementById("userDropdown");
const notifBtn = document.getElementById("notifBtn");
const notifDropdown = document.getElementById("notifDropdown");
const notifCountEl = document.getElementById("notifCount");

if(userBtn && userDropdown){
    userBtn.addEventListener("click", e => { e.stopPropagation(); userDropdown.classList.toggle("hidden"); });
}
if(notifBtn && notifDropdown){
    notifBtn.addEventListener("click", e => { 
        e.stopPropagation(); 
        notifDropdown.classList.toggle("hidden"); 
        if (!notifDropdown.classList.contains("hidden")) fetch('marcar_notificacoes_lidas.php').then(()=>{if(notifCountEl) notifCountEl.style.display='none';});
    });
}
window.addEventListener("click", () => { if(userDropdown) userDropdown.classList.add("hidden"); if(notifDropdown) notifDropdown.classList.add("hidden"); });

// Atualiza notificações via AJAX
function atualizarNotificacoes() { 
  fetch('buscar_notificacoes.php') 
    .then(res => res.json()) 
    .then(data => {
      if(notifCountEl) { notifCountEl.textContent = data.count; notifCountEl.style.display = data.count>0?'inline-block':'none'; }
      if(notifDropdown) {
        notifDropdown.innerHTML = '';
        if(data.notificacoes.length===0) notifDropdown.innerHTML='<li class="px-4 py-2 text-gray-500">Nenhuma notificação</li>';
        else data.notificacoes.forEach(n=>{
          const li = document.createElement('li');
          li.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700'+(n.lida==0?' font-bold':'');
          li.innerHTML = `${n.mensagem} <span class="text-xs text-gray-400 float-right">${new Date(n.criada_em).toLocaleString('pt-BR',{ day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' })}</span>`;
          notifDropdown.appendChild(li);
        });
      }
    }); 
}
setInterval(atualizarNotificacoes, 5000);
atualizarNotificacoes();
  </script>
</body>
</html>
