<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/db.php';
session_start();

// Verifica se usuário é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Obtém nome do autor corretamente
$autor = "Admin";
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmtAutor = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
    if ($stmtAutor) {
        $stmtAutor->bind_param("i", $uid);
        $stmtAutor->execute();
        $stmtAutor->bind_result($nomeAutor);
        if ($stmtAutor->fetch()) {
            $autor = $nomeAutor;
        }
        $stmtAutor->close();
    }
}

// Inserir notícia (imagem como BLOB)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'], $_POST['conteudo'])) {
    $titulo = $_POST['titulo'];
    $conteudo = $_POST['conteudo'];
    $imagemBinario = null;
    if (!empty($_FILES['imagem']['tmp_name']) && is_uploaded_file($_FILES['imagem']['tmp_name'])) {
        $imagemBinario = file_get_contents($_FILES['imagem']['tmp_name']);
    }

    $stmt = $conn->prepare("INSERT INTO noticias (titulo, conteudo, imagem, autor) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        die("Erro na preparação: " . $conn->error);
    }

    $null = NULL;
    $stmt->bind_param("ssbs", $titulo, $conteudo, $null, $autor);

    if ($imagemBinario !== null) {
        $stmt->send_long_data(2, $imagemBinario);
    }

    if (!$stmt->execute()) {
        error_log("Erro ao inserir notícia: " . $stmt->error);
    }
    $stmt->close();

    header("Location: admin_noticias.php?sucesso=1");
    exit;
}

// Excluir notícia
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM noticias WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_noticias.php?removido=1");
    exit;
}

// Listar notícias
$result = $conn->query("SELECT id, titulo, autor, criado_em FROM noticias ORDER BY criado_em DESC");

// NOTIFICAÇÕES
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
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Gerenciar Notícias - Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 text-gray-900">

<!-- Sidebar -->
<div class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white shadow-lg z-40">
  <div class="p-4 font-bold text-xl text-indigo-400">Admin GameZone</div>
  <nav class="flex flex-col gap-2 mt-4 px-4">
    <a href="admin_painel.php" class="hover:text-indigo-400">📊 Painel</a>
    <a href="admin_usuarios.php" class="hover:text-indigo-400">👥 Usuários</a>
    <a href="admin_jogos.php" class="hover:text-indigo-400">🎮 Jogos</a>
    <a href="admin_produtos.php" class="hover:text-indigo-400">🛍️ Produtos</a>
    <a href="admin_avaliacoes.php" class="hover:text-indigo-400">⭐ Avaliações</a>
    <a href="admin_denuncias.php" class="hover:text-indigo-400">🚨 Denúncias</a>
    <a href="admin_noticias.php" class="text-indigo-400 font-semibold">📰 Notícias</a>
    <a href="admin_comunidades.php" class="hover:text-indigo-400">🌐 Comunidades</a>
    <a href="admin_compras.php" class="hover:text-indigo-400">🧾 Compras</a>
    <a href="admin_equipe.php" class="hover:text-indigo-400">🧑‍💼 Equipe</a>
    <a href="admin_configuracoes.php" class="hover:text-indigo-400">⚙️ Configurações</a>
  </nav>
</div>

<!-- Topbar -->
<nav class="fixed top-0 left-64 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
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

<!-- Conteúdo Principal -->
<div class="ml-64 mt-16 p-6">
  <div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Gerenciar Notícias</h1>

    <form method="POST" enctype="multipart/form-data" class="mb-6 bg-white p-4 rounded shadow">
      <label class="block mb-2">
        <span class="text-sm font-medium">Título</span>
        <input type="text" name="titulo" required class="mt-1 block w-full border p-2 rounded">
      </label>

      <label class="block mb-2">
        <span class="text-sm font-medium">Conteúdo</span>
        <textarea name="conteudo" rows="6" required class="mt-1 block w-full border p-2 rounded"></textarea>
      </label>

      <label class="block mb-4">
        <span class="text-sm font-medium">Imagem (opcional)</span>
        <input type="file" name="imagem" accept="image/*" class="mt-1 block">
        <p class="text-xs text-gray-600 mt-1">A imagem será armazenada diretamente no banco (BLOB).</p>
      </label>

      <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Salvar</button>
    </form>

    <div class="bg-white p-4 rounded shadow">
      <h2 class="font-semibold mb-3">Notícias Publicadas</h2>
      <?php if ($result && $result->num_rows > 0): ?>
        <table class="w-full border-collapse">
          <thead>
            <tr class="bg-gray-200">
              <th class="p-2 text-left">Título</th>
              <th class="p-2 text-left">Autor</th>
              <th class="p-2 text-center">Data</th>
              <th class="p-2 text-center">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="border-t">
              <td class="p-2"><?= htmlspecialchars($row['titulo']) ?></td>
              <td class="p-2"><?= htmlspecialchars($row['autor']) ?></td>
              <td class="p-2 text-center"><?= date('d/m/Y H:i', strtotime($row['criado_em'])) ?></td>
              <td class="p-2 text-center">
                <a href="noticia.php?id=<?= $row['id'] ?>" class="text-indigo-600 hover:underline mr-3">Ver</a>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Excluir?')" class="text-red-600 hover:underline">Excluir</a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>Nenhuma notícia cadastrada.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.getElementById('notifBtn')?.addEventListener('click', function() {
  document.getElementById('notifDropdown')?.classList.toggle('hidden');
});

document.getElementById('userMenuBtn')?.addEventListener('click', function() {
  document.getElementById('userDropdown')?.classList.toggle('hidden');
});

document.addEventListener('click', function(e) {
  if (!e.target.closest('#notifBtn') && !e.target.closest('#notifDropdown')) {
    document.getElementById('notifDropdown')?.classList.add('hidden');
  }
  if (!e.target.closest('#userMenuBtn') && !e.target.closest('#userDropdown')) {
    document.getElementById('userDropdown')?.classList.add('hidden');
  }
});
</script>

</body>
</html>