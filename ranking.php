<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config/db.php';
session_start();

// ==============================
// 1. Verifica modo manutenção
// ==============================
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $stmt = $conn->prepare("SELECT nome, valor FROM configuracoes WHERE nome IN ('modo_manutencao', 'mensagem_manutencao')");
    if ($stmt) {
        $stmt->execute();
        $stmt->bind_result($nome, $valor);

        $modo_manutencao = '0';
        $mensagem_manutencao = 'Estamos temporariamente em manutenção. Tente novamente em breve.';

        while ($stmt->fetch()) {
            if ($nome === 'modo_manutencao') $modo_manutencao = $valor;
            if ($nome === 'mensagem_manutencao') $mensagem_manutencao = $valor;
        }
        $stmt->close();

        if ($modo_manutencao === '1') {
            echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Manutenção</title>
            <style>body{background:#f9fafb;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;flex-direction:column;color:#333;text-align:center}
            h1{color:#4F46E5;font-size:2rem}p{max-width:500px;margin-top:1rem}</style></head><body>
            <h1>🔧 Modo Manutenção Ativado</h1><p>" . htmlspecialchars($mensagem_manutencao) . "</p></body></html>";
            exit();
        }
    }
}

// ==============================
// 2. Função Auxiliar para Imagens (CORREÇÃO DO BUG)
// ==============================
function getImagemSrc($dadosImagem) {
    // Se não houver dados, retorna a imagem padrão
    if (empty($dadosImagem)) {
        return 'img/default_server.png';
    }
    // Converte os dados binários (BLOB) para Base64 para o navegador entender
    return 'data:image/jpeg;base64,' . base64_encode($dadosImagem);
}

// ==============================
// 3. Consultas de ranking
// ==============================
$sql_membros = "SELECT c.id, c.nome, c.descricao, c.icone, COUNT(ms.usuario_id) AS total_membros 
                FROM comunidades c
                LEFT JOIN membros_comunidade ms ON c.id = ms.comunidade_id 
                GROUP BY c.id 
                ORDER BY total_membros DESC LIMIT 10";

$sql_votos = "SELECT c.id, c.nome, c.descricao, c.icone, COALESCE(SUM(v.voto), 0) AS total_votos 
              FROM comunidades c
              LEFT JOIN votos_comunidade v ON c.id = v.comunidade_id 
              GROUP BY c.id 
              ORDER BY total_votos DESC LIMIT 10";

$sql_xp = "SELECT c.id, c.nome, c.descricao, c.icone, COALESCE(SUM(xp.xp), 0) AS total_xp 
           FROM comunidades c
           LEFT JOIN xp_comunidade xp ON c.id = xp.comunidade_id 
           GROUP BY c.id 
           ORDER BY total_xp DESC LIMIT 10";

// ==============================
// 4. Notificações
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
<html lang="pt-BR" class="dark">
<head>
  <meta charset="UTF-8">
  <title>Ranking de Comunidades - GameZone</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-900 text-white pt-20 font-['Oxanium']">

<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
  <div class="flex items-center gap-6">
    <a class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">Game<span class="text-gray-800 dark:text-gray-100">Zone</span></a>
    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>
      <div class="relative group">
        <button class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>
        <ul class="absolute hidden group-hover:block bg-white dark:bg-gray-800 shadow rounded mt-1 p-2 w-44 z-50">
          <li><a href="./pages/minhas_comunidades.php" class="block px-3 py-1">Minhas Comunidades</a></li>
          <li><a href="./pages/comunidade/chat.php" class="block px-3 py-1">Chat</a></li>
          <li><a href="./pages/comunidade/amigos.php" class="block px-3 py-1">Amigos</a></li>
          <li><a href="./pages/comunidade/conversas.php" class="block px-3 py-1">Conversas</a></li>
          <li><a href="./pages/comunidade/criar_comunidade.php" class="block px-3 py-1">Criar Comunidade</a></li>
        </ul>
      </div>
      <a href="./pages/comunidade/explorar_comunidades.php" class="hover:underline">Explorar</a>
      <a href="ranking.php" class="hover:underline">Ranking</a>
    </div>
    <a href="loja.php" class="hover:underline">Loja</a>
  </div>

  <div class="relative flex items-center gap-4">
      <?php if (isset($_SESSION['email'])): ?>
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
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center text-gray-300 hover:text-indigo-500">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>
        <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <li><a href="./conta/perfil.php" class="block px-4 py-2">Meu Perfil</a></li>
          <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="admin/admin_painel.php" class="block px-4 py-2">Painel Administrativo</a></li>
          <?php endif; ?>
          <li><a href="./conta/configuracoes.php" class="block px-4 py-2">Configurações</a></li>
          <li><a href="./pages/security/logout.php" class="block px-4 py-2 text-red-600">Sair</a></li>
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

<div class="max-w-6xl mx-auto py-10 px-4">
  <h1 class="text-4xl font-bold text-indigo-400 mb-8 text-center">🏆 Ranking de Comunidades</h1>

  <div class="flex justify-center gap-4 mb-8">
    <button onclick="mostrarAba('membros')" id="btn-membros" class="aba-btn bg-indigo-600 px-4 py-2 rounded transition">Membros</button>
    <button onclick="mostrarAba('votos')" id="btn-votos" class="aba-btn bg-gray-700 px-4 py-2 rounded transition">Votadas</button>
    <button onclick="mostrarAba('xp')" id="btn-xp" class="aba-btn bg-gray-700 px-4 py-2 rounded transition">XP</button>
  </div>

  <div id="aba-membros">
    <?php 
    $res = $conn->query($sql_membros); 
    if ($res):
        while ($row = $res->fetch_assoc()): ?>
        <div class="bg-gray-800 p-4 mb-4 rounded-lg flex items-center shadow-md hover:bg-gray-750 transition">
            <img src="<?= getImagemSrc($row['icone']) ?>" class="w-14 h-14 rounded-full mr-4 object-cover bg-gray-700">
            <div>
            <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($row['nome']) ?></h3>
            <p class="text-sm text-gray-400"><?= htmlspecialchars($row['descricao']) ?></p>
            <p class="text-sm text-indigo-400 font-semibold">👥 <?= $row['total_membros'] ?> membros</p>
            </div>
        </div>
        <?php endwhile; 
    else: ?>
        <p class="text-gray-500 text-center">Nenhuma comunidade encontrada.</p>
    <?php endif; ?>
  </div>

  <div id="aba-votos" class="hidden">
    <?php 
    $res = $conn->query($sql_votos); 
    if ($res):
        while ($row = $res->fetch_assoc()): ?>
        <div class="bg-gray-800 p-4 mb-4 rounded-lg flex items-center shadow-md hover:bg-gray-750 transition">
            <img src="<?= getImagemSrc($row['icone']) ?>" class="w-14 h-14 rounded-full mr-4 object-cover bg-gray-700">
            <div>
            <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($row['nome']) ?></h3>
            <p class="text-sm text-gray-400"><?= htmlspecialchars($row['descricao']) ?></p>
            <p class="text-sm text-green-400 font-semibold">👍 <?= $row['total_votos'] ?> votos</p>
            </div>
        </div>
        <?php endwhile; 
    else: ?>
        <p class="text-gray-500 text-center">Nenhuma comunidade encontrada.</p>
    <?php endif; ?>
  </div>

  <div id="aba-xp" class="hidden">
    <?php 
    $res = $conn->query($sql_xp); 
    if ($res):
        while ($row = $res->fetch_assoc()): ?>
        <div class="bg-gray-800 p-4 mb-4 rounded-lg flex items-center shadow-md hover:bg-gray-750 transition">
            <img src="<?= getImagemSrc($row['icone']) ?>" class="w-14 h-14 rounded-full mr-4 object-cover bg-gray-700">
            <div>
            <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($row['nome']) ?></h3>
            <p class="text-sm text-gray-400"><?= htmlspecialchars($row['descricao']) ?></p>
            <p class="text-sm text-yellow-400 font-semibold">⚡ <?= $row['total_xp'] ?> XP</p>
            </div>
        </div>
        <?php endwhile; 
    else: ?>
        <p class="text-gray-500 text-center">Nenhuma comunidade encontrada.</p>
    <?php endif; ?>
  </div>

</div>

<script>
// Lógica das Abas
function mostrarAba(aba) {
  const abas = ["membros", "votos", "xp"];
  abas.forEach(id => {
    const el = document.getElementById("aba-" + id);
    const btn = document.getElementById("btn-" + id);
    
    if (id === aba) {
      el.classList.remove("hidden");
      btn.classList.add("bg-indigo-600", "text-white");
      btn.classList.remove("bg-gray-700", "text-gray-300");
    } else {
      el.classList.add("hidden");
      btn.classList.add("bg-gray-700", "text-gray-300");
      btn.classList.remove("bg-indigo-600", "text-white");
    }
  });
}

// Lógica dos Dropdowns (Navbar)
const userBtn = document.getElementById("userMenuBtn");
const userDropdown = document.getElementById("userDropdown");
const notifBtn = document.getElementById("notifBtn");
const notifDropdown = document.getElementById("notifDropdown");
const notifCountEl = document.getElementById("notifCount");

if(userBtn && userDropdown){
    userBtn.addEventListener("click", e => { 
        e.stopPropagation(); 
        userDropdown.classList.toggle("hidden"); 
    });
}

if(notifBtn && notifDropdown){
    notifBtn.addEventListener("click", e => { 
        e.stopPropagation(); 
        notifDropdown.classList.toggle("hidden"); 

        if (!notifDropdown.classList.contains("hidden")) {
            fetch('marcar_notificacoes_lidas.php')
              .then(()=>{
                if(notifCountEl) notifCountEl.style.display='none';
              })
              .catch(err=>{
                console.error("Erro ao marcar notificações lidas:", err);
              });
        }
    });
}

window.addEventListener("click", () => { 
    if(userDropdown) userDropdown.classList.add("hidden"); 
    if(notifDropdown) notifDropdown.classList.add("hidden"); 
});

// Inicializa na aba "membros"
document.addEventListener("DOMContentLoaded", () => mostrarAba("membros"));
</script>

</body>
</html>