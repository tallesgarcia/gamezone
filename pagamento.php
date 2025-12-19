<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config/db.php';
session_start();

// ======================
// Validação inicial
// ======================
// Removemos a validação por $_GET, pois eles não são enviados no POST do pagamento.

// Se o usuário não estiver logado, redireciona
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/security/entrar.php");
    exit;
}

// Verifica se o carrinho existe e se está vazio. Se estiver, morre aqui.
if (!isset($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
    die("<div style='max-width: 500px; margin: 100px auto; padding: 20px; background-color: #333; color: white; border-radius: 8px; text-align: center; font-family: sans-serif;'>
            <h1 style='color: #ef4444; margin-bottom: 15px;'>Carrinho Vazio</h1>
            <p>Não há itens para finalizar a compra.</p>
            <a href='loja.php' style='display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #4f46e5; color: white; text-decoration: none; border-radius: 6px;'>Voltar para a loja</a>
         </div>");
}

// Variáveis para exibição:
$itens_carrinho = $_SESSION['carrinho'];
// Calcula o total novamente (mais seguro) ou usa o total da sessão/GET se disponível
$total = array_sum(array_column($itens_carrinho, 'preco'));

// ======================
// Se o botão pagar for clicado
// ======================
if (isset($_POST['pagar'])) {
    // 1. Armazena os itens para exibição na próxima página
    $_SESSION['ultima_compra'] = $_SESSION['carrinho'];
    
    // 2. Limpa o carrinho
    $_SESSION['carrinho'] = [];
    
    // 3. Redireciona
    header("Location: pagamento_sucesso.php");
    exit;
}

// ==============================
// Notificações
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
            if ($nlida == 0) $notifCount++;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>GameZone - Pagamento</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-['Oxanium']">
<nav class="fixed top-0 left-0 right-0 z-30 bg-gray-800 border-b border-gray-700 h-16 flex items-center justify-between px-6 shadow-lg">
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
        <button id="userMenuBtn" class="flex items-center text-gray-300 hover:text-indigo-400 transition">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>
        <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <li><a href="./conta/perfil.php" class="block px-4 py-2 hover:text-indigo-400">Meu Perfil</a></li>
          <?php if ($_SESSION['tipo_usuario']==='admin'): ?>
          <li><a href="./admin/admin_painel.php" class="block px-4 py-2 hover:text-indigo-400">Painel Administrativo</a></li>
          <?php endif; ?>
          <li><a href="./conta/configuracoes.php" class="block px-4 py-2 hover:text-indigo-400">Configurações</a></li>
          <li><a href="../pages/security/logout.php" class="block px-4 py-2 text-red-600 hover:text-red-400">Sair</a></li>
        </ul>
      </div>
    <?php else: ?>
      <div class="flex gap-2">
        <a href="./pages/security/entrar.php" class="text-sm hover:text-indigo-400 transition">Entrar</a>
        <a href="./pages/security/cadastrar.html" class="text-sm hover:text-indigo-400 transition">Cadastrar</a>
      </div>
    <?php endif; ?>
  </div>
</nav>
<div class="max-w-3xl mx-auto p-6 mt-16 bg-zinc-800 rounded-xl shadow-lg">
    <h1 class="text-3xl font-bold mb-4 text-indigo-400">💳 Finalizar Pagamento</h1>

    <h2 class="text-xl font-semibold mb-2">Resumo da Compra:</h2>
    <ul class="mb-4 border-b border-gray-700 pb-2">
        <?php foreach($itens_carrinho as $item): ?>
        <li class="flex justify-between py-1">
            <span><?= htmlspecialchars($item['nome']) ?><?= $item['tipo']==='assinatura'?' (Assinatura)':'' ?></span>
            <span>R$ <?= number_format($item['preco'],2,',','.') ?></span>
        </li>
        <?php endforeach; ?>
    </ul>

    <p class="text-right font-bold text-lg mb-6">Total: R$ <?= number_format($total,2,',','.') ?></p>

    <form method="post">
        <button type="submit" name="pagar" class="w-full bg-green-600 hover:bg-green-500 px-4 py-2 rounded-lg text-white text-lg font-semibold">✅ Pagar Agora</button>
    </form>

    <a href="loja.php" class="block mt-4 text-center text-indigo-400 hover:underline">Cancelar e voltar para a loja</a>
</div>
<script>
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

function atualizarNotificacoes() {
 fetch('buscar_notificacoes.php')
   .then(res => res.json())
   .then(data => {
     if(notifCountEl) {
       notifCountEl.textContent = data.count;
       notifCountEl.style.display = data.count>0?'inline-block':'none';
     }

     if(notifDropdown) {
       notifDropdown.innerHTML = '';

       if(data.notificacoes.length===0) {
         notifDropdown.innerHTML='<li class="px-4 py-2 text-gray-500">Nenhuma notificação</li>';
       } else {
         data.notificacoes.forEach(n=>{
           const li = document.createElement('li');
           li.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700'+(n.lida==0?' font-bold':'');
           li.innerHTML = `${n.mensagem} <span class="text-xs text-gray-400 float-right">${new Date(n.criada_em).toLocaleString('pt-BR',{ day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' })}</span>`;
           notifDropdown.appendChild(li);
         });
       }
     }
   })
   .catch(err => {
     console.error("Erro ao buscar notificações:", err);
   });
}

setInterval(atualizarNotificacoes, 5000);
atualizarNotificacoes();
</script>
</body>
</html>