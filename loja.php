<?php
session_start();
require_once __DIR__ . '/config/db.php';

// ==========================
// Contador de notificações (corrige erro de variável indefinida)
// ==========================
$notifCount = 0;

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];

    $sqlNotif = "SELECT COUNT(*) AS total FROM notificacoes WHERE usuario_id = $uid AND lida = 0";
    $resNotif = $conn->query($sqlNotif);

    if ($resNotif && $resNotif->num_rows > 0) {
        $notifCount = $resNotif->fetch_assoc()['total'];
    }
}

// ==========================
// Listar produtos
// ==========================
$produtos = [];
$sql = "SELECT id, nome, tipo, preco, imagem FROM produtos ORDER BY id DESC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    if (!empty($row['imagem'])) {
        $row['imagem'] = "data:image/jpeg;base64," . base64_encode($row['imagem']);
    } else {
        // Fallback caso não exista imagem no banco
        $row['imagem'] = "assets/img/produto_padrao.png";
    }

    $produtos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Loja - GameZone</title>

<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">

</head>
<body class="bg-gray-900 text-white pt-16 px-6">

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

<div id="meus-produtos" class="flex items-center justify-between mb-6">
    
    <!-- Título -->
    <h1 class="text-3xl font-bold text-indigo-400">🛒 Loja</h1>

    <!-- Botão -->
    <form action="meus_podutos.php" method="POST">
        <input type="hidden" name="user_id">
        <button class="bg-orange-600 hover:bg-orange-500 px-5 py-2 rounded font-semibold text-white">
            Meus Produtos
        </button>
    </form>
</div>
<div class="flex items-center justify-between mb-6">
<!-- Botão -->
    <form action="historico_compras.php" method="POST">
        <input type="hidden" name="user_id">
        <button class="bg-pink-600 hover:bg-orange-500 px-5 py-2 rounded font-semibold text-white">
            Histórico de Compras
        </button>
    </form>
</div>
<div class="grid md:grid-cols-3 gap-6">
<?php foreach ($produtos as $p): ?>
<div class="bg-zinc-800 p-4 rounded-xl shadow border border-zinc-700 produto-card tipo-<?= strtolower($p['tipo']) ?>">
    <img src="<?= $p['imagem'] ?>" class="w-full h-48 object-cover rounded">
    
    <h2 class="text-xl font-bold mt-3"><?= htmlspecialchars($p['nome']) ?></h2>
    <p class="text-indigo-300 text-sm"><?= htmlspecialchars($p['tipo']) ?></p>

    <p class="text-green-400 text-lg font-bold mt-2">
        R$ <?= number_format($p['preco'], 2, ',', '.') ?>
    </p>

    <form action="processar_compra.php" method="POST" class="mt-3">
        <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
        <button class="bg-indigo-600 hover:bg-indigo-500 w-full py-2 rounded font-semibold">
            Comprar
        </button>
    </form>
</div>
<?php endforeach; ?>
</div>


<!-- SCRIPT FINAL -->
<script>
function filtrarProdutos(tipo, btn) {
    const cards = document.querySelectorAll('.produto-card');

    cards.forEach(card => {
        const categoria = card.classList.contains('tipo-' + tipo);
        card.style.display = (tipo === 'todos' || categoria) ? 'block' : 'none';
    });

    document.querySelectorAll('.filtro-btn')
        .forEach(b => b.classList.remove('bg-indigo-600'));

    btn.classList.add('bg-indigo-600');
}

/* Dropdown notificações */
$("#notifBtn").on("click", function () {
    $("#notifDropdown").toggleClass("hidden");

    $.post("ajax/notificacoes.php", function (data) {
        $("#notifDropdown").html(data);
        $("#notifCount").remove();
    });
});

/* Dropdown usuário */
$("#userMenuBtn").on("click", function () {
    $("#userDropdown").toggleClass("hidden");
});
</script>

</body>
</html>
