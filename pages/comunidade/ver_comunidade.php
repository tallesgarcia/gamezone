<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Verifica login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../security/entrar.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$comunidade_id = intval($_GET['id'] ?? 0);

// Busca dados da comunidade
$stmt = $conn->prepare("SELECT * FROM comunidades WHERE id = ?");
$stmt->bind_param("i", $comunidade_id);
$stmt->execute();
$result = $stmt->get_result();
$comunidade = $result->fetch_assoc();
$stmt->close();

if (!$comunidade) {
    die("Comunidade não encontrada.");
}

// Notificações
$notificacoes = [];
$notifCount = 0;
$stmt = $conn->prepare("SELECT id, mensagem, lida, criada_em FROM notificacoes WHERE usuario_id = ? ORDER BY criada_em DESC LIMIT 5");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($nid, $nmsg, $nlida, $ncriada);
while ($stmt->fetch()) {
    $notificacoes[] = ['id'=>$nid,'mensagem'=>$nmsg,'lida'=>$nlida,'criada_em'=>$ncriada];
    if ($nlida==0) $notifCount++;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($comunidade['nome']) ?> - Comunidade</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">

<!-- Navbar -->
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">

  <div class="flex items-center gap-6">
    <a class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
      Game<span class="text-gray-800 dark:text-gray-100">Zone</span>
    </a>

    <div class="hidden md:flex gap-4 items-center text-sm">

      <a href="../../index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>

      <div class="relative group">
        <button class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>

        <ul class="absolute hidden group-hover:block bg-white dark:bg-gray-800 shadow rounded mt-1 p-2 w-44 z-50">
          <li><a href="../minhas_comunidades.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Minhas Comunidades</a></li>
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


  <!-- NOTIFICAÇÕES + USUÁRIO -->
  <div class="relative flex items-center gap-4">

    <?php if (isset($_SESSION['email'])): ?>

      <!-- 🔔 Notificações -->
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
                // 🔐 TRATAMENTO DE STRINGS
                $nid = isset($n['id']) ? (int)$n['id'] : 0;

                $mensagem = $n['mensagem'] ?? 'Notificação indisponível';
                $mensagem = htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8');
                $mensagem = mb_strimwidth($mensagem, 0, 100, '...');

                $data = !empty($n['criada_em'])
                        ? date('d/m/Y H:i', strtotime($n['criada_em']))
                        : "00/00/0000 00:00";
              ?>

              <li class="px-4 py-2 border-b last:border-b-0 hover:bg-gray-100 dark:hover:bg-gray-700">
                <a href="notificacao_ver.php?id=<?= $nid ?>" class="block text-sm text-gray-800 dark:text-gray-200">
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


      <!-- 👤 Menu Usuário -->
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

          <li><a href="../../conta/perfil.php"
                 class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>

          <?php if (($_SESSION['tipo_usuario'] ?? '') === 'admin'): ?>
            <li><a href="../../admin/admin_painel.php"
                   class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
          <?php endif; ?>

          <li><a href="../../conta/configuracoes.php"
                 class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>

          <li><a href="../security/logout.php"
                 class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>

        </ul>
      </div>

    <?php endif; ?>

  </div>

</nav>


<!-- Conteúdo -->
<div class="pt-20 max-w-6xl mx-auto grid grid-cols-3 gap-6">

    <!-- Painel da comunidade -->
    <div class="col-span-1 bg-gray-800 p-6 rounded-xl shadow">
    <div class="flex items-center gap-4">

        <?php
$iconeSrc = "https://via.placeholder.com/150"; // fallback
$mimeType = "image/png";

if (isset($comunidade['icone']) && !is_null($comunidade['icone']) && strlen($comunidade['icone']) > 0) {

    // Detecta MIME apenas se fileinfo existir
    if (extension_loaded('fileinfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($comunidade['icone']);

        if ($detected && $detected !== 'application/octet-stream') {
            $mimeType = $detected;
        }
    }

    $iconeSrc = "data:$mimeType;base64," . base64_encode($comunidade['icone']);
}
?>
<img src="<?= $iconeSrc ?>" alt="Ícone da comunidade" class="w-16 h-16 rounded-full object-cover">

        <h1 class="text-2xl font-bold"><?= htmlspecialchars($comunidade['nome']) ?></h1>
    </div>

    <p class="mt-4 text-gray-300"><?= nl2br(htmlspecialchars($comunidade['descricao'])) ?></p>

    <div class="mt-4 flex flex-col gap-2">
        <a href="forum.php?id=<?= $comunidade_id ?>" class="bg-indigo-600 hover:bg-indigo-700 px-3 py-2 rounded text-center">
            Ver Todos os Fóruns
        </a>

        <?php if ($comunidade['dono_id'] == $user_id): ?>
            <a href="config_comunidade.php?id=<?= $comunidade_id ?>" class="bg-gray-600 hover:bg-gray-700 px-3 py-2 rounded text-center">
                Configurações da Comunidade
            </a>
        <?php endif; ?>
    </div>
</div>


    <!-- Fórum -->
    <div class="col-span-2 bg-gray-800 p-6 rounded-xl shadow">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Fóruns</h2>
            <button id="abrirModal" class="bg-indigo-600 hover:bg-indigo-700 px-3 py-1 rounded">Novo Fórum</button>
        </div>
        <div id="listaForuns" class="space-y-3"></div>
    </div>

    <!-- Chat -->
    <div class="col-span-3 bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-xl font-bold mb-3">Chat da Comunidade</h2>
        <div id="chatBox" class="h-64 overflow-y-auto border border-gray-700 p-3 mb-3 rounded bg-gray-900 flex flex-col gap-1"></div>
        <form id="formChat" class="flex gap-2">
            <input type="text" id="mensagem" name="mensagem" placeholder="Digite sua mensagem..." class="flex-1 p-2 rounded text-black">
            <button type="submit" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700">Enviar</button>
            <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
              <button id="limpar-chat" type="button" class="mb-4 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                🧹 Limpar Chat
              </button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Modal Novo Fórum -->
<div id="modalForum" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-gray-800 p-6 rounded-xl shadow w-96">
        <h2 class="text-xl font-bold mb-4">Criar Fórum</h2>
        <form id="formForum" class="space-y-3">
            <input type="hidden" name="comunidade_id" value="<?= $comunidade_id ?>">
            <div>
                <label class="block">Título</label>
                <input type="text" name="titulo" class="w-full p-2 rounded text-black" required>
            </div>
            <div>
                <label class="block">Descrição</label>
                <textarea name="descricao" class="w-full p-2 rounded text-black"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="fecharModal" class="px-3 py-1 bg-gray-600 rounded">Cancelar</button>
                <button type="submit" class="px-3 py-1 bg-indigo-600 rounded">Criar</button>
            </div>
        </form>
    </div>
</div>

<!-- Footer -->
<footer class="text-center py-6 text-gray-400 text-sm border-t border-gray-700 mt-14">
    <div class="mb-2">
        <a href="../../contato.php" class="hover:text-indigo-400 transition mx-2">Contato</a> |
        <a href="../../privacidade.php" class="hover:text-indigo-400 transition mx-2">Privacidade</a> |
        <a href="../../sobre.php" class="hover:text-indigo-400 transition mx-2">Sobre</a> |
        <a href="../../termos.php" class="hover:text-indigo-400 transition mx-2">Termos</a> |
        <a href="../../equipe.php" class="hover:text-indigo-400 transition mx-2">Equipe</a>
    </div>
    <div>© <?= date('Y') ?> GameZone - Conectando jogadores.</div>
</footer>

<script>
$(document).ready(function() {
    // Modal
    $("#abrirModal").click(()=>$("#modalForum").show());
    $("#fecharModal").click(()=>$("#modalForum").hide());

    // Carregar fóruns
    function carregarForuns() {
        $.get("get_foruns.php", {comunidade_id: <?= $comunidade_id ?>}, data=>{
            $("#listaForuns").html(data);
        });
    }
    carregarForuns();

    // Criar fórum
    $("#formForum").submit(function(e){
        e.preventDefault();
        $.post("criar_forum.php", $(this).serialize(), res=>{
            let r = JSON.parse(res);
            if(r.sucesso){
                $("#modalForum").hide();
                $("#formForum")[0].reset();
                carregarForuns();
            }else{
                alert(r.mensagem);
            }
        });
    });

    // Chat
    function carregarChat(){
        $.get("get_chat.php", {comunidade_id: <?= $comunidade_id ?>}, data=>{
            $("#chatBox").html(data);
            $("#chatBox").scrollTop($("#chatBox")[0].scrollHeight);
        });
    }
    carregarChat();
    setInterval(carregarChat, 3000);

    $("#formChat").submit(function(e){
        e.preventDefault();
        $.post("enviar_chat.php", {comunidade_id: <?= $comunidade_id ?>, mensagem: $("#mensagem").val()}, res=>{
            if(res==="ok"){
                $("#mensagem").val("");
                carregarChat();
            }
        });
    });
});

$('#limpar-chat').click(function (e) {
  e.preventDefault();
  if (confirm("Tem certeza que deseja limpar o chat? Esta ação não pode ser desfeita.")) {
    $.post('limpar_mensagens_comunidade.php', function(response) {
      carregarMensagens();
    }).fail(function() {
      alert("Erro ao limpar o chat.");
    });
  }
});

// ==============================
// Notificações
// ==============================
const notifBtn = document.getElementById("notifBtn");
const notifDropdown = document.getElementById("notifDropdown");
const userBtn = document.getElementById("userMenuBtn");
const userDropdown = document.getElementById("userDropdown");
const notifCountEl = document.getElementById("notifCount");

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

if (notifBtn && notifDropdown) {
  notifBtn.addEventListener("click", e => { 
    e.stopPropagation();
    notifDropdown.classList.toggle("hidden");
    if (userDropdown && !userDropdown.classList.contains("hidden")) userDropdown.classList.add("hidden");
    if (!notifDropdown.classList.contains("hidden")) fetch('marcar_notificacoes_lidas.php').then(()=>{if(notifCountEl) notifCountEl.style.display='none';});
  });
}

if (userBtn && userDropdown) {
  userBtn.addEventListener("click", e => {
    e.stopPropagation();
    userDropdown.classList.toggle("hidden");
    if (notifDropdown && !notifDropdown.classList.contains("hidden")) notifDropdown.classList.add("hidden");
  });
}

window.addEventListener("click", () => {
  if(userDropdown) userDropdown.classList.add("hidden");
  if(notifDropdown) notifDropdown.classList.add("hidden");
});
</script>
</body>
</html>
