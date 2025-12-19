<?php
session_start();
require_once __DIR__ . './../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Você precisa estar logado para denunciar.");
}

$usuario_id = $_SESSION['user_id'];

// ==============================
// Processar envio
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_denunciante = $usuario_id;
    $id_denunciado = (int)$_POST['id_denunciado'];
    $motivo = trim($_POST['motivo']);
    $descricao = trim($_POST['descricao']);
    $data_ocorrido = $_POST['data_ocorrido'] ?? date('Y-m-d H:i:s');
    $arquivo = null;

    // Upload de prova (opcional)
    if (!empty($_FILES['arquivo']['name'])) {
        $uploadDir = __DIR__ . "/uploads/denuncias/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $nomeArquivo = time() . "_" . basename($_FILES['arquivo']['name']);
        $destino = $uploadDir . $nomeArquivo;

        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
            $arquivo = $nomeArquivo;
        }
    }

    // ==============================
    // Validar se o denunciado é amigo aceito do denunciante
    // ==============================
    $check = $conn->prepare("SELECT 1 
        FROM amizades 
        WHERE status = 'aceito' 
          AND ((usuario_id = ? AND amigo_id = ?) OR (usuario_id = ? AND amigo_id = ?)) 
        LIMIT 1");
    $check->bind_param("iiii", $id_denunciante, $id_denunciado, $id_denunciado, $id_denunciante);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        echo "<script>alert('Você só pode denunciar usuários que estejam na sua lista de contatos.'); window.location.href='../../pages/reportar/denunciar_usuario.php';</script>";
        exit;
    }
    $check->close();

    // ==============================
    // Inserir denúncia
    // ==============================
    $stmt = $conn->prepare("INSERT INTO denuncias 
        (id_denunciante, id_denunciado, motivo, descricao, arquivo, data_ocorrido) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $id_denunciante, $id_denunciado, $motivo, $descricao, $arquivo, $data_ocorrido);

    if ($stmt->execute()) {
        echo "<script>alert('Denúncia enviada com sucesso!'); window.location.href='../../index.php';</script>";
        exit;
    } else {
        echo "Erro: " . $stmt->error;
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
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Denunciar Usuário | GameZone</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Font Awesome para ícones (CDN). Dependendo do uso, considere baixar/servir localmente. -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- Google Fonts: Oxanium e Rajdhani (carrega fontes externas). -->
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <style>
    #sugestoes {
      position: absolute;
      background: white;
      border: 1px solid #ddd;
      width: 100%;
      max-height: 200px;
      overflow-y: auto;
      z-index: 1000;
    }
    #sugestoes div {
      padding: 8px;
      cursor: pointer;
    }
    #sugestoes div:hover {
      background: #f3f4f6;
    }
  </style>
</head>
<body class="pt-16 bg-gray-100 p-6">
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
  <!-- Logo e navegação -->
  <div class="flex items-center gap-6">
    <a class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">Game<span class="text-gray-800 dark:text-gray-100">Zone</span></a>
    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="../../index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>
      <div class="relative group">
        <button class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>
        <ul class="absolute hidden group-hover:block bg-white dark:bg-gray-800 shadow rounded mt-1 p-2 w-44 z-50">
          <li><a href="../minhas_comunidades.php" class="block px-3 py-1 text-white hover:bg-gray-100 dark:hover:bg-gray-700">Minhas Comunidades</a></li>
          <li><a href="../chat.php" class="block px-3 py-1 text-white hover:bg-gray-100 dark:hover:bg-gray-700">Chat</a></li>
          <li><a href="../amigos.php" class="block px-3 py-1 text-white hover:bg-gray-100 dark:hover:bg-gray-700">Amigos</a></li>
          <li><a href="../conversas.php" class="block px-3 py-1 text-white hover:bg-gray-100 dark:hover:bg-gray-700">Conversas</a></li>
          <li><a href="../criar_comunidade.php" class="block px-3 py-1 text-white hover:bg-gray-100 dark:hover:bg-gray-700">Criar Comunidade</a></li>
        </ul>
      </div>
      <a href="../explorar_comunidades.php" class="hover:underline text-gray-700 dark:text-gray-300">Explorar</a>
      <a href="../../ranking.php" class="hover:underline text-gray-700 dark:text-gray-300">Ranking</a>
    </div>
    <a href="../../loja.php" class="hover:underline text-gray-700 dark:text-gray-300">Loja</a>
  </div>

  <!-- Notificações & Usuário (visíveis só com email na sessão) -->
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>
      <!-- Notificações -->
      <div class="relative">
        <button id="notifBtn" class="text-gray-600 hover:text-indigo-500 relative">
          <i class="fas fa-bell text-2xl"></i>
          <?php if($notifCount > 0): ?>
            <span id="notifCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5"><?= (int)$notifCount ?></span>
          <?php endif; ?>
        </button>

        <ul id="notifDropdown" class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <?php if (!empty($notificacoes)): ?>
            <?php foreach ($notificacoes as $n): ?>
              <li class="px-4 py-2 border-b last:border-b-0 hover:bg-gray-100 dark:hover:bg-gray-700">
                <a href="notificacao_ver.php?id=<?= (int)$n['id'] ?>" class="block text-sm text-gray-800 dark:text-gray-200">
                  <?= htmlspecialchars(mb_strimwidth($n['mensagem'], 0, 100, '...')) ?>
                  <div class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($n['criada_em'])) ?></div>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="px-4 py-2 text-gray-500">Nenhuma notificação.</li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Usuário -->
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-500">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>
        <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <li><a href="../../conta/perfil.php" class="block px-4 py-2 text-white hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>
          <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="../../admin/admin_painel.php" class="block px-4 py-2 text-white hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
          <?php endif; ?>
          <li><a href="../../conta/configuracoes.php" class="block px-4 py-2 text-white hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>
          <li><a href="../security/logout.php" class="block px-4 py-2 text-red-600 text-red hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</nav>

  <div class="max-w-lg mx-auto bg-white p-6 shadow rounded">
    <h2 class="text-xl font-bold mb-4">Denunciar Usuário</h2>

    <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">

      <!-- Autocomplete usuário -->
      <div class="relative">
        <label class="block font-semibold mb-1">Usuário a ser denunciado:</label>
        <input type="text" id="buscaUsuario" class="w-full border p-2 rounded" placeholder="Digite o nome do amigo..." autocomplete="off">
        <input type="hidden" name="id_denunciado" id="id_denunciado">
        <div id="sugestoes" class="hidden"></div>
      </div>

      <div>
        <label class="block font-semibold mb-1">Motivo:</label>
        <input type="text" name="motivo" required class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block font-semibold mb-1">Descrição:</label>
        <textarea name="descricao" rows="4" required class="w-full border p-2 rounded"></textarea>
      </div>

      <div>
        <label class="block font-semibold mb-1">Data do ocorrido:</label>
        <input type="datetime-local" name="data_ocorrido" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block font-semibold mb-1">Prova (imagem ou documento):</label>
        <input type="file" name="arquivo" class="w-full border p-2 rounded">
      </div>

      <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
        Enviar Denúncia
      </button>
    </form>
  </div>

<script>
$(document).ready(function(){
  $("#buscaUsuario").on("input", function(){
    let termo = $(this).val();
    if (termo.length < 2) {
      $("#sugestoes").hide();
      return;
    }
    $.get("buscar_amigos.php", {q: termo}, function(data){
      let sugestoes = JSON.parse(data);
      let html = "";
      sugestoes.forEach(amigo => {
        html += `<div data-id="${amigo.id}">${amigo.nome}</div>`;
      });
      $("#sugestoes").html(html).show();
    });
  });

  $(document).on("click", "#sugestoes div", function(){
    $("#buscaUsuario").val($(this).text());
    $("#id_denunciado").val($(this).data("id"));
    $("#sugestoes").hide();
  });
});

// Seleciona elementos do DOM usados nos dropdowns e notificações.
// document.getElementById retorna null se o elemento não existir — por isso verificamos antes de usar.
const userBtn = document.getElementById("userMenuBtn");
const userDropdown = document.getElementById("userDropdown");
const notifBtn = document.getElementById("notifBtn");
const notifDropdown = document.getElementById("notifDropdown");
const notifCountEl = document.getElementById("notifCount");

// Se userBtn e userDropdown existem, adicionamos evento de clique para alternar visibilidade.
// e.stopPropagation() evita que o clique no botão feche o dropdown por causa do listener global.
if(userBtn && userDropdown){
    userBtn.addEventListener("click", e => { 
        e.stopPropagation();            // Impede propagação para o window.click abaixo
        userDropdown.classList.toggle("hidden"); // Alterna a classe hidden (mostra/oculta)
    });
}

// Se notifBtn e notifDropdown existem, adicionamos o clique para mostrar/ocultar notificações.
// Ao abrir, chama marcar_notificacoes_lidas.php para marcar no backend como lidas (requisição simples).
if(notifBtn && notifDropdown){
    notifBtn.addEventListener("click", e => { 
        e.stopPropagation(); 
        notifDropdown.classList.toggle("hidden"); 

        // Se o dropdown acabou de ser aberto (não contém 'hidden'), chamamos endpoint para marcar lidas.
        if (!notifDropdown.classList.contains("hidden")) {
            fetch('marcar_notificacoes_lidas.php')
              .then(()=>{
                // Se há elemento do contador, escondemos (após marcar como lidas).
                if(notifCountEl) notifCountEl.style.display='none';
              })
              .catch(err=>{
                // Em caso de erro, opcionalmente tratar ou logar no console
                console.error("Erro ao marcar notificações lidas:", err);
              });
        }
    });
}

// Listener global para fechar dropdowns ao clicar fora (window capture)
window.addEventListener("click", () => { 
    if(userDropdown) userDropdown.classList.add("hidden"); 
    if(notifDropdown) notifDropdown.classList.add("hidden"); 
});
</script>

</body>
</html>
