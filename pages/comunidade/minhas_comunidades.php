<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Verifica login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../security/entrar.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar comunidades do usuário (dono ou membro)
$query = "
    SELECT s.* 
    FROM servidores s
    JOIN membros_servidor m ON s.id = m.servidor_id
    WHERE m.usuario_id = ?
    UNION
    SELECT s.*
    FROM servidores s
    WHERE s.dono_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();


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
  <title>Minhas Comunidades | GameZone</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="pt-16 bg-gray-900 text-white">
<!-- Navbar -->
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
  <!-- Logo e navegação -->
  <div class="flex items-center gap-6">
    <a class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">Game<span class="text-gray-800 dark:text-gray-100">Zone</span></a>
    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="../../index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>
      <div class="relative group">
        <button class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>
        <ul class="absolute hidden group-hover:block bg-white dark:bg-gray-800 shadow rounded mt-1 p-2 w-44 z-50">
          <li><a href="minhas_comunidades.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Minhas Comunidades</a></li>
          <li><a href="comunidades_populares.php" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">Populares</a></li>
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
  <!-- Usuário -->
  <div class="relative">
    <?php if (isset($_SESSION['email'])): ?>
      <button id="userMenuBtn" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-500">
        <i class="fas fa-user-circle text-2xl mr-1"></i>
        <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
      </button>
      <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
        <li><a href="../../conta/perfil.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>
        <?php if ($_SESSION['tipo_usuario'] === 'admin'): ?>
          <li><a href="../../admin/admin_painel.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
        <?php endif; ?>
        <li><a href="../../conta/configuracoes.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>
        <li><a href="../security/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>
      </ul>
    <?php endif; ?>
  </div>
</nav>

<!-- Conteúdo -->
<div class="max-w-5xl mx-auto pt-20 p-6">
  <h2 class="text-2xl font-bold mb-4 text-indigo-600">Minhas Comunidades</h2>

  <?php if ($result->num_rows > 0): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php while ($comunidade = $result->fetch_assoc()): ?>
        <div class="bg-gray-800 shadow rounded-lg p-4 flex flex-col items-center text-center">
          <?php if (!empty($comunidade['icone'])): ?>
            <img src="uploads/comunidades/<?= htmlspecialchars($comunidade['icone']) ?>" 
                 alt="Ícone" class="w-20 h-20 rounded-full mb-3 object-cover">
          <?php else: ?>
            <div class="w-20 h-20 flex items-center justify-center bg-gray-200 rounded-full mb-3">
              <span class="text-gray-500 text-lg">📂</span>
            </div>
          <?php endif; ?>
          <h3 class="text-lg font-bold"><?= htmlspecialchars($comunidade['nome']) ?></h3>
          <p class="text-sm text-gray-600 mb-3"><?= htmlspecialchars($comunidade['descricao']) ?: "Sem descrição" ?></p>
          <a href="ver_comunidade.php?id=<?= $comunidade['id'] ?>" 
             class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm">
            Acessar
          </a>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p class="text-gray-600">Você ainda não participa de nenhuma comunidade.</p>
    <a href="criar_comunidade.php" 
       class="inline-block mt-3 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
      Criar Comunidade
    </a>
  <?php endif; ?>
</div>

</body>
</html>
