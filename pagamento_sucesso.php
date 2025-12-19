<?php
// Configurações de erro (apropriado para desenvolvimento)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
session_start();

// 1. Verificação de Autenticação
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/security/entrar.php");
    exit;
}

// 2. Processamento dos Dados de Compra
// CRITICAL FIX: Salva os dados para exibição e limpa o carrinho/última compra.

$user_id = $_SESSION['user_id'];

// Inicializa a sessão temporária para o relatório, se não existir
if (!isset($_SESSION['pagamento_sucesso'])) {
    $_SESSION['pagamento_sucesso'] = [];
}

// Verifica se há uma última compra a ser exibida (dados recém-chegados do processamento)
if (isset($_SESSION['ultima_compra']) && !empty($_SESSION['ultima_compra'])) {
    // Move os dados da 'ultima_compra' para a 'pagamento_sucesso'
    $_SESSION['pagamento_sucesso']['itens'] = $_SESSION['ultima_compra'];
    $_SESSION['pagamento_sucesso']['total'] = array_sum(array_column($_SESSION['ultima_compra'], 'preco'));
    
    // Limpa a última compra para evitar reexibição em outros contextos
    $_SESSION['ultima_compra'] = [];
}

// Pega os dados a serem exibidos no relatório (podem ter sido carregados nesta ou em sessão anterior)
$itens_comprados = $_SESSION['pagamento_sucesso']['itens'] ?? [];
$total = $_SESSION['pagamento_sucesso']['total'] ?? 0;

// ==============================
// Notificações (código original mantido)
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
<title>GameZone - Pagamento Concluído</title>
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
          <li><a href="./conta/configuracoes_usuario.php" class="block px-4 py-2 hover:text-indigo-400">Configurações</a></li>
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

<div class="max-w-3xl mx-auto p-6 mt-16 bg-zinc-800 rounded-xl shadow-lg text-center">
    <h1 class="text-4xl font-bold mb-4 text-green-500">🎉 Pagamento Concluído!</h1>
    <p class="text-gray-300 mb-6">Sua compra foi registrada com sucesso. Aproveite seus produtos e assinaturas!</p>

    <h2 class="text-2xl font-semibold mb-2 text-indigo-400">Resumo da Compra:</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700 bg-zinc-700 rounded-lg">
            <thead class="bg-zinc-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                        Produto/Serviço
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
                        Preço
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
            <?php if(empty($itens_comprados)): ?>
                <tr>
                    <td colspan="2" class="px-6 py-4 text-center text-gray-400">Nenhum item encontrado no resumo.</td>
                </tr>
            <?php else: ?>
                <?php foreach($itens_comprados as $item): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-left">
                        <span class="font-medium text-white"><?= htmlspecialchars($item['nome']) ?></span>
                        <span class="text-sm text-gray-400"><?= $item['tipo']==='assinatura'?' (Assinatura Mensal)':'' ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-green-400">
                        R$ <?= number_format($item['preco'], 2, ',', '.') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="bg-zinc-600">
                    <td class="px-6 py-3 text-left text-lg font-bold text-white">
                        Total Pago:
                    </td>
                    <td class="px-6 py-3 text-right text-xl font-extrabold text-green-400">
                        R$ <?= number_format($total, 2, ',', '.') ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="flex flex-col sm:flex-row justify-center gap-4 mt-8">
        <a href="loja.php" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg text-white font-semibold transition">🛒 Voltar à Loja</a>
        <a href="historico_compras.php" class="bg-purple-500 hover:bg-purple-400 px-4 py-2 rounded-lg text-white font-semibold transition">📜 Histórico de Compras</a>
    </div>
</div>

<?php
unset($_SESSION['pagamento_sucesso']);
?>

</body>
</html>