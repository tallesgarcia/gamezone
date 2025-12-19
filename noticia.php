<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config/db.php';
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: noticias.php");
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT id, titulo, conteudo, imagem, autor, criado_em FROM noticias WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$noticia = $res->fetch_assoc();
$stmt->close();

if (!$noticia) {
    header("Location: noticias.php");
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($noticia['titulo']) ?> - GameZone</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6">
  <nav class="fixed top-0 left-0 right-0 z-30 bg-gray-800 border-b border-gray-700 h-16 flex items-center justify-between px-6 shadow-lg">
  <!-- Container esquerdo: logo e links principais -->
  <div class="flex items-center gap-6">
    <a class="text-3xl font-bold text-indigo-500">Game<span class="text-gray-100">Zone</span></a>

    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="index.php" class="hover:text-indigo-400 transition">Início</a>

      <div class="relative group">
        <button class="hover:text-indigo-400 transition">Comunidade</button>

        <ul class="absolute hidden group-hover:block bg-gray-800 shadow-lg rounded mt-1 p-2 w-44 z-50">
          <li><a href="./pages/minhas_comunidades.php" class="block px-3 py-1 hover:text-indigo-400">Minhas Comunidades</a></li>
          <li><a href="./pages/comunidade/chat.php" class="block px-3 py-1 hover:text-indigo-400">Chat</a></li>
          <li><a href="./pages/comunidade/amigos.php" class="block px-3 py-1 hover:text-indigo-400">Amigos</a></li>
          <li><a href="./pages/comunidade/conversas.php" class="block px-3 py-1 hover:text-indigo-400">Conversas</a></li>
          <li><a href="./pages/comunidade/criar_comunidade.php" class="block px-3 py-1 hover:text-indigo-400">Criar Comunidade</a></li>
        </ul>
      </div>

      <a href="./pages/comunidade/explorar_comunidades.php" class="hover:text-indigo-400 transition">Explorar</a>
      <a href="ranking.php" class="hover:text-indigo-400 transition">Ranking</a>
      <a href="loja.php" class="hover:text-indigo-400 transition">Loja</a>
    </div>
  </div>

  <!-- Container direito -->
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>

      <!-- NOTIFICAÇÕES -->
      <div class="relative">
        <button id="notifBtn" class="text-gray-300 hover:text-indigo-400 relative">
          <i class="fas fa-bell text-2xl"></i>

          <?php if (!empty($notifCount)): ?>
            <span id="notifCount"
                  class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5">
              <?= (int)$notifCount ?>
            </span>
          <?php endif; ?>
        </button>

        <!-- DROPDOWN -->
        <ul id="notifDropdown"
            class="absolute right-0 top-full mt-2 w-72 bg-gray-900 shadow-lg rounded-lg py-2 hidden z-50">

          <?php if (!empty($notificacoes)): ?>

            <?php foreach ($notificacoes as $n): ?>
              <?php
                $nid = isset($n['id']) ? (int)$n['id'] : 0;
                $mensagem = $n['mensagem'] ?? 'Notificação inválida';
                $mensagem = htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8');
                $mensagem = mb_strimwidth($mensagem, 0, 100, '...');

                $data = !empty($n['criada_em'])
                        ? date('d/m/Y H:i', strtotime($n['criada_em']))
                        : '00/00/0000 00:00';
              ?>

              <li class="px-4 py-2 border-b border-gray-700 last:border-0 hover:bg-gray-800 transition">
                <a href="notificacao_ver.php?id=<?= $nid ?>" class="block text-sm text-gray-200">
                  <?= $mensagem ?>
                  <div class="text-xs text-gray-500"><?= $data ?></div>
                </a>
              </li>

            <?php endforeach; ?>

          <?php else: ?>
            <li class="px-4 py-2 text-gray-400">Nenhuma notificação.</li>
          <?php endif; ?>

        </ul>
      </div>

      <!-- MENU USUÁRIO -->
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center text-gray-300 hover:text-indigo-400 transition">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>

        <ul id="userDropdown"
            class="absolute right-0 mt-2 w-48 bg-gray-900 shadow-lg rounded-lg py-2 hidden z-50">

          <li><a href="./conta/perfil.php"
                 class="block px-4 py-2 text-gray-200 hover:text-indigo-400">Meu Perfil</a></li>

          <?php if (!empty($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="./admin/admin_painel.php"
                   class="block px-4 py-2 text-gray-200 hover:text-indigo-400">Painel Administrativo</a></li>
          <?php endif; ?>

          <li><a href="./conta/configuracoes.php"
                 class="block px-4 py-2 text-gray-200 hover:text-indigo-400">Configurações</a></li>

          <li><a href="./pages/security/logout.php"
                 class="block px-4 py-2 text-red-500 hover:text-red-400">Sair</a></li>

        </ul>
      </div>

    <?php else: ?>

      <!-- USUÁRIO DESLOGADO -->
      <div class="flex gap-2">
        <a href="./pages/security/entrar.php" class="text-sm hover:text-indigo-400 transition">Entrar</a>
        <a href="./pages/security/cadastrar.html" class="text-sm hover:text-indigo-400 transition">Cadastrar</a>
      </div>

    <?php endif; ?>
  </div>
</nav>
  <div class="max-w-4xl mx-auto">
    <a href="noticias.php" class="text-indigo-400 hover:underline">&larr; Voltar</a>

    <div class="bg-white text-gray-900 p-6 rounded-lg shadow mt-4">
      <?php if (!empty($noticia['imagem'])): ?>
        <img src="data:image/jpeg;base64,<?= base64_encode($noticia['imagem']) ?>" alt="Imagem" class="w-full h-64 object-cover rounded mb-4">
      <?php endif; ?>

      <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($noticia['titulo']) ?></h1>
      <p class="text-sm text-gray-600 mb-6">Por <?= htmlspecialchars($noticia['autor']) ?> em <?= date('d/m/Y H:i', strtotime($noticia['criado_em'])) ?></p>

      <div class="text-gray-800 leading-relaxed whitespace-pre-line">
        <?= nl2br(htmlspecialchars($noticia['conteudo'])) ?>
      </div>
    </div>
  </div>
</body>
</html>
