<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/security/entrar.php");
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Buscar comunidades criadas pelo usuário
$stmt = $conn->prepare("SELECT id, nome, descricao, icone FROM comunidades WHERE dono_id = ?");
if(!$stmt){
    die("Erro no prepare: " . $conn->error);
}
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$comunidades = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameZone | Minhas Comunidades</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Oxanium', sans-serif; background-color: #f9fafb; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200">

<!-- NAVBAR FIXA -->
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">

  <!-- LOGO + MENU -->
  <div class="flex items-center gap-6">
    <a class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
      Game<span class="text-gray-800 dark:text-gray-100">Zone</span>
    </a>

    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="../index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>

      <div class="relative group">
        <button class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>
        <ul class="absolute hidden group-hover:block bg-white dark:bg-gray-800 shadow rounded mt-1 p-2 w-44 z-50">
          <li><a href="minhas_comunidades.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Minhas Comunidades</a></li>
          <li><a href="../pages/comunidade/chat.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Chat</a></li>
          <li><a href="../pages/comunidade/amigos.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Amigos</a></li>
          <li><a href="../pages/comunidade/conversas.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Conversas</a></li>
          <li><a href="../pages/comunidade/criar_comunidade.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Criar Comunidade</a></li>
        </ul>
      </div>

      <a href="../pages/comunidade/explorar_comunidades.php" class="hover:underline text-gray-700 dark:text-gray-300">Explorar</a>
      <a href="../ranking.php" class="hover:underline text-gray-700 dark:text-gray-300">Ranking</a>
    </div>

    <a href="../loja.php" class="hover:underline text-gray-700 dark:text-gray-300">Loja</a>
  </div>

  <!-- NOTIFICAÇÕES E USUÁRIO -->
  <div class="relative flex items-center gap-4">

    <?php if (isset($_SESSION['email'])): ?>

      <!-- 🔔 NOTIFICAÇÕES -->
      <div class="relative">
        <button id="notifBtn" class="text-gray-600 hover:text-indigo-500 relative">
          <i class="fas fa-bell text-2xl"></i>

          <?php if (!empty($notifCount)): ?>
            <span id="notifCount"
                  class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5">
                  <?= (int)$notifCount ?>
            </span>
          <?php endif; ?>
        </button>

        <ul id="notifDropdown"
            class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">

          <?php if (!empty($notificacoes)): ?>

            <?php foreach ($notificacoes as $n): ?>

              <?php
                // TRATAMENTO COMPLETO DAS STRINGS
                $nid   = isset($n['id']) ? (int)$n['id'] : 0;

                $mensagem = $n['mensagem'] ?? 'Notificação indisponível';
                $mensagem = htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8');
                $mensagem = mb_strimwidth($mensagem, 0, 100, '...');

                $data = !empty($n['criada_em'])
                      ? date('d/m/Y H:i', strtotime($n['criada_em']))
                      : "00/00/0000 00:00";
              ?>

              <li class="px-4 py-2 border-b last:border-b-0 hover:bg-gray-100 dark:hover:bg-gray-700">
                <a href="notificacao_ver.php?id=<?= $nid ?>"
                   class="block text-sm text-gray-800 dark:text-gray-200">
                  <?= $mensagem ?>
                  <div class="text-xs text-gray-500"><?= $data ?></div>
                </a>
              </li>

            <?php endforeach; ?>

          <?php else: ?>
            <li class="px-4 py-2 text-gray-500">Nenhuma notificação.</li>
          <?php endif; ?>

        </ul>
      </div>

      <!-- 👤 MENU DO USUÁRIO -->
      <div class="relative">
        <button id="userMenuBtn"
                class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-500">

          <i class="fas fa-user-circle text-2xl mr-1"></i>

          <span class="hidden sm:inline text-sm">
            <?= htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8') ?>
          </span>

        </button>

        <ul id="userDropdown"
            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">

          <li><a href="../conta/perfil.php"
                 class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>

          <?php if (!empty($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="../admin/admin_painel.php"
                   class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
          <?php endif; ?>

          <li><a href="../conta/configuracoes.php"
                 class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>

          <li><a href="../security/logout.php"
                 class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>

        </ul>
      </div>

    <?php endif; ?>

  </div>
</nav>


<!-- CONTEÚDO PRINCIPAL -->
<main class="pt-36 px-4 md:px-10 lg:px-20 min-h-screen">
    <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-6">Minhas Comunidades</h1>

    <?php if (count($comunidades) === 0): ?>
        <p class="text-gray-600 dark:text-gray-300">Você ainda não criou nenhuma comunidade. <a href="../pages/comunidade/criar_comunidade.php" class="text-indigo-600 dark:text-indigo-400 hover:underline">Crie uma agora</a>.</p>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($comunidades as $comunidade): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition p-5 flex flex-col gap-2">
                <?php
                // LÓGICA PARA ÍCONE DA COMUNIDADE (BLOB -> BASE64)
                $imagemSrc = "../assets/images/comunidade_exemplo.jpg"; // Imagem padrão (caminho relativo)

                if (!empty($comunidade['icone'])) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($comunidade['icone']);
                    if (!$mimeType) $mimeType = 'image/jpeg';
                    
                    $base64 = base64_encode($comunidade['icone']);
                    $imagemSrc = "data:$mimeType;base64,$base64";
                }
                ?>
                
                <img src="<?= $imagemSrc ?>" 
                     alt="<?= htmlspecialchars($comunidade['nome']) ?>" 
                     class="rounded-lg w-full h-40 object-cover">
                     
                <h2 class="font-semibold text-lg text-gray-800 dark:text-gray-200">
                    <?= htmlspecialchars($comunidade['nome']) ?>
                </h2>
                
                <p class="text-gray-600 dark:text-gray-300 text-sm">
                    <?= htmlspecialchars($comunidade['descricao'] ?? '') ?>
                </p>
                
                <a href="../pages/comunidade/ver_comunidade.php?id=<?= $comunidade['id'] ?>" 
                   class="mt-auto text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                   Entrar
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

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
