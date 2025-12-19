<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
session_start();


// ==============================
// Verifica modo manutenção
// ==============================
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $stmt = $conn->prepare("SELECT nome, valor FROM configuracoes WHERE nome IN ('modo_manutencao', 'mensagem_manutencao')");
    $stmt->execute();
    $stmt->bind_result($nome, $valor);

    $modo_manutencao = '0';
    $mensagem_manutencao = 'Estamos temporariamente em manutenção. Tente novamente em breve.';

    while ($stmt->fetch()) {
        if ($nome === 'modo_manutencao') {
            $modo_manutencao = $valor;
        }
        if ($nome === 'mensagem_manutencao') {
            $mensagem_manutencao = $valor;
        }
    }
    $stmt->close();

    if ($modo_manutencao === '1') {
        echo "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <title>Manutenção - GameZone</title>
            <style>
                body {
                    background-color: #f9fafb;
                    font-family: Arial, sans-serif;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    color: #333;
                    text-align: center;
                }
                h1 {
                    font-size: 2rem;
                    color: #4F46E5;
                }
                p {
                    max-width: 500px;
                    margin-top: 1rem;
                }
            </style>
        </head>
        <body>
            <h1>🔧 Modo Manutenção Ativado</h1>
            <p>" . htmlspecialchars($mensagem_manutencao) . "</p>
        </body>
        </html>";
        exit();
    }
}


// ==============================
// Histórico de compras (COM CORREÇÃO)
// ==============================
$usuario_id = $_SESSION['user_id'];
$compras = [];

$stmt = $conn->prepare("
    SELECT 
        c.id AS compra_id,
        c.data_compra,
        c.valor_total,
        c.quantidade,
        p.nome,
        p.tipo,
        p.imagem
    FROM compras c
    JOIN produtos p ON c.produto_id = p.id
    WHERE c.usuario_id = ?
    ORDER BY c.data_compra DESC
");

if ($stmt) {
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();

    $resultado = $stmt->get_result();

    while ($row = $resultado->fetch_assoc()) {
        // Decodifica imagem BLOB
        if (!empty($row['imagem'])) {
            $row['imagem'] = "data:image/jpeg;base64," . base64_encode($row['imagem']);
        } else {
            $row['imagem'] = "assets/img/default.png"; // fallback
        }

        $compras[] = $row;
    }

    $stmt->close();
} else {
    die("Erro ao preparar query de compras: " . $conn->error);
}



// ==============================
// Notificações
// ==============================
$notificacoes = [];
$notifCount = 0;

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
        SELECT id, mensagem, lida, criada_em
        FROM notificacoes
        WHERE usuario_id = ?
        ORDER BY criada_em DESC
        LIMIT 5
    ");

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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Histórico de Compras - GameZone</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
</head>

<body class="pt-16 bg-gray-900 text-white px-6 py-8">

<!-- NAVBAR -->
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
            <a href="loja.php" class="text-indigo-400 font-semibold">Loja</a>
        </div>
    </div>

    <div class="relative flex items-center gap-4">
        <?php if (isset($_SESSION['email'])): ?>

        <!-- Notificações -->
        <div class="relative">
            <button id="notifBtn" class="text-gray-300 hover:text-indigo-500 relative">
                <i class="fas fa-bell text-2xl"></i>
                <?php if ($notifCount > 0): ?>
                <span id="notifCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5">
                    <?= $notifCount ?>
                </span>
                <?php endif; ?>
            </button>
            <ul id="notifDropdown" class="absolute right-0 top-full mt-2 w-64 bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
                <li class="px-4 py-2 text-gray-500">Carregando...</li>
            </ul>
        </div>

        <!-- Menu user -->
        <div class="relative">
            <button id="userMenuBtn" class="flex items-center text-gray-300 hover:text-indigo-400 transition">
                <i class="fas fa-user-circle text-2xl mr-1"></i>
                <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
            </button>

            <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
                <li><a href="./conta/perfil.php" class="block px-4 py-2 hover:text-indigo-400">Meu Perfil</a></li>

                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
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



<h1 class="text-3xl font-bold mb-6 text-indigo-400">📜 Histórico de Compras</h1>

<div class="grid gap-4">
  <?php if ($compras): foreach ($compras as $compra): ?>
    <div class="bg-zinc-800 p-4 rounded-xl flex gap-4 items-center border border-zinc-700">
      
      <img src="<?= $compra['imagem'] ?>" 
           alt="<?= htmlspecialchars($compra['nome']) ?>" 
           class="w-20 h-20 object-cover rounded">

      <div class="flex-1">
        <h2 class="text-lg font-semibold">
            <?= htmlspecialchars($compra['nome']) ?> 
            <span class="text-sm text-gray-400">(<?= $compra['tipo'] ?>)</span>
        </h2>

        <p class="text-gray-400 text-sm">
            Comprado em: <?= date('d/m/Y H:i', strtotime($compra['data_compra'])) ?>
        </p>

        <p class="text-gray-400 text-sm">
            Quantidade: <?= $compra['quantidade'] ?>
        </p>
      </div>

      <div class="text-green-400 font-bold text-lg">
        R$ <?= number_format($compra['valor_total'], 2, ',', '.') ?>
      </div>
    </div>

  <?php endforeach; else: ?>
    <p class="text-gray-400">Você ainda não realizou nenhuma compra.</p>
  <?php endif; ?>
</div>


<!-- Rodapé -->
<div class="pt-36">
  <footer class="text-center py-6 text-gray-400 text-sm border-t border-gray-700 mt-14">
      <div class="mb-2">
          <a href="contato.php" class="hover:text-indigo-400 transition mx-2">Contato</a> |
          <a href="privacidade.php" class="hover:text-indigo-400 transition mx-2">Privacidade</a> |
          <a href="sobre.php" class="hover:text-indigo-400 transition mx-2">Sobre</a> |
          <a href="termos.php" class="hover:text-indigo-400 transition mx-2">Termos</a> |
          <a href="equipe.php" class="hover:text-indigo-400 transition mx-2">Equipe</a>
      </div>
      <div>© <?= date('Y') ?> GameZone - Conectando jogadores.</div>
  </footer>
</div>
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
