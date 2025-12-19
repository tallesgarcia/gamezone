<?php
session_start();
require_once '../config/db.php';

// =============================
// Verifica login
// =============================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../security/entrar.php");
    exit;
}

$userId = $_SESSION['user_id'];
$aba = $_POST['aba'] ?? $_GET['aba'] ?? 'conta';
$mensagem = "";

// Notificações
$notifCount = 0;

// Avatar padrão
$imagemPadrao = "../uploads/perfis/default.png";

// =============================
// BUSCA OS DADOS DO USUÁRIO
// =============================
$stmt = $conn->prepare("SELECT nome, email, senha, avatar FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    die("Erro ao carregar dados do usuário.");
}

// =============================
// ATUALIZAÇÕES
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aba === 'conta') {

    $novoNome = trim($_POST['nome']);
    $novoEmail = trim($_POST['email']);
    $senhaAtual = trim($_POST['senhaatual']);
    $novaSenha = trim($_POST['novasenha'] ?? '');

    $dadosAtualizados = false;
    $imagemAtualizada = false;

    // 1 - Atualizar nome/email
    if ($novoNome !== $usuario['nome'] || $novoEmail !== $usuario['email']) {
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $novoNome, $novoEmail, $userId);
        if ($stmt->execute()) {
            $dadosAtualizados = true;
        } else {
            $mensagem = "❌ Erro ao atualizar nome/email: " . $stmt->error;
        }
        $stmt->close();
    }

    // 2 - Atualizar senha
    if (!empty($novaSenha)) {

          // Verifica senha atual
          if (!password_verify($senhaAtual, $usuario['senha'])) {
              $mensagem = "❌ A senha atual está incorreta.";
          } else {

              $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);

              $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
              $stmt->bind_param("si", $senhaHash, $userId);

              if ($stmt->execute()) {
                  $dadosAtualizados = true;
              } else {
                  $mensagem = "❌ Erro ao atualizar senha: " . $stmt->error;
              }
              $stmt->close();
          }
    }



    // 3 - Upload do avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {

        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $permitidas)) {
            if ($_FILES['avatar']['size'] <= 2 * 1024 * 1024) {

                $conteudo = file_get_contents($_FILES['avatar']['tmp_name']);
                $stmt = $conn->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?");
                $null = null;
                $stmt->bind_param("bi", $null, $userId);
                $stmt->send_long_data(0, $conteudo);

                if ($stmt->execute()) {
                    $imagemAtualizada = true;
                } else {
                    $mensagem = "❌ Erro ao salvar imagem: " . $stmt->error;
                }

                $stmt->close();

            } else {
                $mensagem = "⚠️ A imagem excede o limite de 2MB.";
            }
        } else {
            $mensagem = "⚠️ Formato inválido. Use JPG, PNG ou GIF.";
        }
    }

    // Se não houve erro → redireciona
    if (empty($mensagem)) {

        if ($dadosAtualizados || $imagemAtualizada) {
            $_SESSION['mensagem_sucesso'] = "✅ Perfil atualizado com sucesso!";
        }

        header("Location: perfil.php");
        exit;
    }
}

// =============================
// Converte avatar BLOB para base64
// =============================
$usuario['avatar_src'] = $imagemPadrao;

if (!empty($usuario['avatar'])) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($usuario['avatar']);

    if (substr($mime, 0, 6) === "image/") {
        $usuario['avatar_src'] = "data:$mime;base64," . base64_encode($usuario['avatar']);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Configurações do Usuário - GameZone</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script> 
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
  <link rel="stylesheet" href="./assets/css/estilos.css"> 
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>
<body class="dark bg-gray-900 text-white min-h-screen">
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
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
    </div>
  </div>
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>
      <div class="relative">
        <button id="notifBtn" class="text-gray-600 dark:text-gray-300 hover:text-indigo-500 relative">
          <i class="fas fa-bell text-2xl"></i>
          
          <?php if($notifCount > 0): ?>
            <span id="notifCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5">
              <?= $notifCount ?>
            </span>
          <?php endif; ?>
          
        </button>
        <ul id="notifDropdown" class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          </ul>
      </div>
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

<div class="max-w-3xl mx-auto p-6 mt-20">
  <h1 class="text-3xl font-bold mb-6 text-center">⚙️ Configurações de Conta</h1>

  <?php if (!empty($mensagem)): ?>
    <div class="mb-4 p-3 rounded <?= str_contains($mensagem, 'sucesso') ? 'bg-green-600' : 'bg-red-600'; ?>">
      <?= htmlspecialchars($mensagem) ?>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="space-y-6 bg-gray-800 p-6 rounded-xl shadow-lg">
    <input type="hidden" name="aba" value="conta">

    <div>
      <label for="nome" class="block text-sm font-medium text-gray-300">Nome</label>
      <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required
        class="mt-1 w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
    </div>

    <div>
      <label for="email" class="block text-sm font-medium text-gray-300">E-mail</label>
      <input type="email" name="email" id="email" value="<?= htmlspecialchars($usuario['email']) ?>" required
        class="mt-1 w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
    </div>

    <label for="senhaatual" class="block text-sm font-medium text-gray-300">Senha Atual</label>
    <input type="password" name="senhaatual" id="senhaatual"
      class="mt-1 w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none">

    <label for="novasenha" class="block text-sm font-medium text-gray-300">Nova Senha</label>
    <input type="password" name="novasenha" id="novasenha"
      class="mt-1 w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none">


    <div>
      <p class="text-sm text-gray-400 mb-2">Imagem atual:</p>
      
      <img src="<?= htmlspecialchars($usuario['avatar_src']) ?>" alt="Imagem de Perfil" class="w-24 h-24 rounded-full object-cover border border-gray-600">
      
    </div>

    <div>
      <label for="avatar" class="block text-sm font-medium text-gray-300">Nova imagem de perfil</label>
      <input type="file" name="avatar" id="avatar" accept="image/*"
        class="mt-1 w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
      <p class="text-xs text-gray-500 mt-1">Tamanho máximo: 2 MB (formatos: JPG, PNG, GIF)</p>
    </div>

    <div class="text-center">
      <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 px-5 py-2 rounded text-white font-medium transition">
        💾 Salvar Alterações
      </button>
    </div>
  </form>
</div>

<script>
  const abas = document.querySelectorAll(".aba-tab");
  const formularios = document.querySelectorAll(".aba-form");
  abas.forEach(tab => {
    tab.addEventListener("click", () => {
      const aba = tab.dataset.aba;
      abas.forEach(t => t.classList.remove("border-indigo-500", "text-indigo-600", "dark:text-indigo-400"));
      tab.classList.add("border-indigo-500", "text-indigo-600", "dark:text-indigo-400");
      formularios.forEach(f => f.classList.toggle("hidden", f.dataset.aba !== aba));
    });
  });

  // Ativar aba correta após POST
  window.addEventListener("DOMContentLoaded", () => {
    const ativa = "<?= $_POST['aba'] ?? 'conta' ?>";
    document.querySelectorAll(".aba-tab").forEach(t => {
      t.classList.toggle("border-indigo-500", t.dataset.aba === ativa);
      t.classList.toggle("text-indigo-600", t.dataset.aba === ativa);
      t.classList.toggle("dark:text-indigo-400", t.dataset.aba === ativa);
    });

    document.querySelectorAll(".aba-form").forEach(f => {
      f.classList.toggle("hidden", f.dataset.aba !== ativa);
    });
  });
</script>
</body>
</html>