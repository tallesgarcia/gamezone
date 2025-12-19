<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config/db.php';
session_start();

/*echo "<pre>";
print_r($_SESSION);
print_r($_POST);
echo "</pre><br>";*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['user_id'] ?? null;
    $nota = $_POST['nota'] ?? null;
    $comentario = $_POST['comentario'] ?? '';

    if ($usuario_id && $nota) {
        $stmt = $conn->prepare("INSERT INTO avaliacoes (usuario_id, nota, comentario, criado_em) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $usuario_id, $nota, $comentario);
        if ($stmt->execute()) {
            $mensagem = "Avaliação enviada com sucesso!";
        } else {
            $mensagem = "Erro ao enviar avaliação.";
        }
        $stmt->close();
    } else {
        $mensagem = "Preencha todos os campos obrigatórios.";
    }
}

// ==============================
// NOTIFICAÇÕES DO USUÁRIO
// ==============================

// Inicializa array de notificações e contador de não-lidas
$notificacoes = [];
$notifCount = 0;

// Se existe user_id na sessão, buscamos as notificações relacionadas
if (isset($_SESSION['user_id'])) {
    // Prepared statement para evitar SQL injection ao usar parâmetros.
    $stmt = $conn->prepare("SELECT id, mensagem, lida, criada_em FROM notificacoes WHERE usuario_id = ? ORDER BY criada_em DESC LIMIT 5");
    if ($stmt) {
        // Liga parâmetro (i = integer) com o id do usuário vindo da sessão
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();

        // Liga colunas de resultado a variáveis
        $stmt->bind_result($nid, $nmsg, $nlida, $ncriada);

        // Itera pelas notificações, monta o array e conta não-lidas
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
  <title>Avaliar Plataforma - GameZone</title>
<!-- Tailwind via CDN (rápido para desenvolvimento). Em produção, prefira build local/configurada. -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- jQuery (usado em algumas partes; se não usar muito, pode tirar e usar apenas Fetch/API nativa) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Font Awesome para ícones (CDN). Dependendo do uso, considere baixar/servir localmente. -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- Google Fonts: Oxanium e Rajdhani (carrega fontes externas). -->
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
<!-- Barra de navegação fixa no topo -->
<nav class="fixed top-0 left-0 right-0 z-30 bg-gray-800 border-b border-gray-700 h-16 flex items-center justify-between px-6 shadow-lg">
  <!-- Container esquerdo: logo e links principais -->
  <div class="flex items-center gap-6">
    <!-- Logo (texto) com estilos — poderia ser <img> se tiver logo gráfico -->
    <a class="text-3xl font-bold text-indigo-500">Game<span class="text-gray-100">Zone</span></a>

    <!-- Menu de navegação principal (escondido em telas pequenas por `hidden md:flex`) -->
    <div class="hidden md:flex gap-4 items-center text-sm">
      <!-- Link para a página atual -->
      <a href="index.php" class="hover:text-indigo-400 transition">Início</a>

      <!-- Dropdown "Comunidade" com comportamento via CSS: .group e .group-hover -->
      <div class="relative group">
        <button class="hover:text-indigo-400 transition">Comunidade</button>

        <!-- Menu que aparece ao passar o mouse (group-hover); contêm links relacionados -->
        <ul class="absolute hidden group-hover:block bg-gray-800 shadow-lg rounded mt-1 p-2 w-44 z-50">
          <li><a href="./pages/minhas_comunidades.php" class="block px-3 py-1 hover:text-indigo-400">Minhas Comunidades</a></li>
          <li><a href="./pages/comunidade/chat.php" class="block px-3 py-1 hover:text-indigo-400">Chat</a></li>
          <li><a href="./pages/comunidade/amigos.php" class="block px-3 py-1 hover:text-indigo-400">Amigos</a></li>
          <li><a href="./pages/comunidade/conversas.php" class="block px-3 py-1 hover:text-indigo-400">Conversas</a></li>
          <li><a href="./pages/comunidade/criar_comunidade.php" class="block px-3 py-1 hover:text-indigo-400">Criar Comunidade</a></li>
        </ul>
      </div>

      <!-- Outros links do menu principal -->
      <a href="./pages/comunidade/explorar_comunidades.php" class="hover:text-indigo-400 transition">Explorar</a>
      <a href="ranking.php" class="hover:text-indigo-400 transition">Ranking</a>
      <a href="loja.php" class="hover:text-indigo-400 transition">Loja</a>
    </div>
  </div>

  <!-- Container direito: notificações / menu usuário ou links Entrar/Cadastrar -->
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>
      <!-- Se a sessão tem 'email', consideramos o usuário logado e mostramos notificações + menu -->

      <!-- Notificações: botão com sino -->
      <div class="relative">
        <button id="notifBtn" class="text-gray-600 hover:text-indigo-500 relative">
          <i class="fas fa-bell text-2xl"></i>
          <!-- Se houver notificações não lidas ($notifCount > 0), exibimos um badge com o número -->
          <?php if($notifCount > 0): ?>
            <span id="notifCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5"><?= $notifCount ?></span>
          <?php endif; ?>
        </button>

        <!-- Dropdown que receberá as notificações (preenchido via JS/AJAX) -->
        <ul id="notifDropdown" class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <!-- Conteúdo inicial enquanto carregamos -->
          <li class="px-4 py-2 text-gray-500">Carregando...</li>
        </ul>
      </div>

      <!-- Menu do usuário (ícone + email) -->
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center text-gray-300 hover:text-indigo-400 transition">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <!-- Mostra o email do usuário (escape para evitar XSS) -->
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>

        <!-- Dropdown do menu do usuário -->
        <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <li><a href="./conta/perfil.php" class="block px-4 py-2 hover:text-indigo-400">Meu Perfil</a></li>

          <!-- Se o tipo de usuário for admin, mostramos link para painel administrativo -->
          <?php if ($_SESSION['tipo_usuario']==='admin'): ?>
          <li><a href="./admin/admin_painel.php" class="block px-4 py-2 hover:text-indigo-400">Painel Administrativo</a></li>
          <?php endif; ?>

          <li><a href="./conta/configuracoes.php" class="block px-4 py-2 hover:text-indigo-400">Configurações</a></li>

          <!-- Link para logout — perceba o caminho relativo (ajuste se necessário) -->
          <li><a href="../pages/security/logout.php" class="block px-4 py-2 text-red-600 hover:text-red-400">Sair</a></li>
        </ul>
      </div>

    <?php else: ?>
      <!-- Se o usuário não estiver logado, exibimos links para Entrar e Cadastrar -->
      <div class="flex gap-2">
        <a href="./pages/security/entrar.php" class="text-sm hover:text-indigo-400 transition">Entrar</a>
        <a href="./pages/security/cadastrar.html" class="text-sm hover:text-indigo-400 transition">Cadastrar</a>
      </div>
    <?php endif; ?>
  </div>
</nav>
  <div class="bg-gray-800 p-6 rounded-xl shadow w-full max-w-lg">
    <h2 class="text-2xl font-bold mb-4">⭐ Avaliar Plataforma</h2>

    <?php if (!empty($mensagem)): ?>
      <div class="mb-4 p-3 bg-green-600 rounded"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm mb-1">Nota (1 a 5) *</label>
        <select name="nota" class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600" required>
          <option value="">Selecione...</option>
          <option value="1">1 - Péssimo</option>
          <option value="2">2 - Ruim</option>
          <option value="3">3 - Regular</option>
          <option value="4">4 - Bom</option>
          <option value="5">5 - Excelente</option>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">Comentário</label>
        <textarea name="comentario" rows="4" class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600"></textarea>
      </div>
      <button type="submit" class="w-full bg-green-600 hover:bg-green-700 py-2 rounded font-semibold">Enviar avaliação</button>
    </form>
  </div>

<script>
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
