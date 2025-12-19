<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/getImagemProduto.php';

// ======================================
// VERIFICAR LOGIN
// ======================================
if (!isset($_SESSION['user_id'])) {
    header("Location: ./pages/security/entrar.php");
    exit;
}

$usuario_id = $_SESSION['user_id'];
$produto_id = intval($_POST['produto_id'] ?? 0);

// Se não existir produto_id
if ($produto_id <= 0) {
    die("Produto inválido.");
}

// ======================================
// BUSCAR PRODUTO NO BANCO
// ======================================
$stmt = $conn->prepare("SELECT id, nome, preco, imagem FROM produtos WHERE id = ?");
$stmt->bind_param("i", $produto_id);
$stmt->execute();
$result = $stmt->get_result();
$produto = $result->fetch_assoc();
$stmt->close();

if (!$produto) {
    die("Produto não encontrado.");
}else{
    if (!empty($produto['imagem'])) {
       $imagem = "data:image/jpeg;base64," . base64_encode($produto['imagem']);
    } else {
        // Fallback caso não exista imagem no banco
        $imagem = "assets/img/produto_padrao.png";
    }
}

// Obter imagem usando o sistema de imagem seguro
#$imagem = getImagemProduto($produto['imagem']);

// ======================================
// CONFIRMAR COMPRA
// ======================================
if (isset($_POST['confirmar']) && $_POST['confirmar'] == 1) {

    $valor = floatval($produto['preco']);

    $stmt = $conn->prepare("
        INSERT INTO compras (usuario_id, produto_id, quantidade, valor_total, status, data_compra)
        VALUES (?, ?, 1, ?, 'pago', NOW())
    ");
    $stmt->bind_param("iid", $usuario_id, $produto_id, $valor);
    $stmt->execute();
    $stmt->close();

    header("Location: historico_compras.php?ok=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Confirmar Compra</title>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">

</head>

<body class="bg-[#0b0f19] text-white font-['Oxanium']">

<div class="max-w-4xl mx-auto mt-14 p-8 bg-[#111827] rounded-2xl shadow-2xl border border-indigo-500/20">

    <!-- TÍTULO -->
    <h1 class="text-4xl font-extrabold text-indigo-400 mb-8 flex items-center gap-3">
        <i class="fa-solid fa-cart-shopping text-indigo-300"></i>
        Confirmar Compra
    </h1>

    <!-- CARD DO PRODUTO -->
    <div class="bg-[#1f2937] rounded-xl overflow-hidden border border-gray-700 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3">
            
            <!-- IMAGEM -->
            <div class="p-6 flex justify-center items-center bg-[#111827]">
                <img src="<?= htmlspecialchars($imagem) ?>"
                     class="w-40 h-40 object-cover rounded-lg shadow-lg transform hover:scale-105 transition">
            </div>

            <!-- INFO -->
            <div class="col-span-2 p-6 flex flex-col justify-center">
                <h2 class="text-2xl font-semibold text-gray-200 mb-2">
                    <?= htmlspecialchars($produto['nome']) ?>
                </h2>

                <p class="text-green-400 text-2xl font-bold">
                    R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                </p>
            </div>

        </div>
    </div>

    <!-- OPÇÕES DE PAGAMENTO -->
    <h2 class="text-2xl font-semibold text-indigo-300 mb-4">Opções de Pagamento</h2>

    <div class="space-y-4 mb-10">

        <label class="flex items-center gap-3 bg-[#1f2937] p-4 rounded-lg cursor-pointer border border-gray-700 hover:border-indigo-400 transition">
            <input type="radio" name="pagamento_opcao" checked>
            <span class="flex items-center gap-2 text-gray-200">
                <i class="fa-solid fa-qrcode text-indigo-300"></i>
                Pix
            </span>
        </label>

        <label class="flex items-center gap-3 bg-[#1f2937] p-4 rounded-lg cursor-pointer border border-gray-700 hover:border-indigo-400 transition">
            <input type="radio" name="pagamento_opcao">
            <span class="flex items-center gap-2 text-gray-200">
                <i class="fa-solid fa-credit-card text-indigo-300"></i>
                Cartão de Crédito
            </span>
        </label>

        <label class="flex items-center gap-3 bg-[#1f2937] p-4 rounded-lg cursor-pointer border border-gray-700 hover:border-indigo-400 transition">
            <input type="radio" name="pagamento_opcao">
            <span class="flex items-center gap-2 text-gray-200">
                <i class="fa-solid fa-wallet text-indigo-300"></i>
                Saldo da Conta
            </span>
        </label>

    </div>

    <!-- BOTÕES -->
    <form method="POST">
        <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
        <input type="hidden" name="confirmar" value="1">

        <div class="flex justify-between">

            <!-- Cancelar -->
            <a href="loja.php"
               class="px-6 py-3 rounded-lg bg-gray-600 hover:bg-gray-500 font-semibold shadow-md transition">
                Cancelar
            </a>

            <!-- Confirmar -->
            <button type="submit"
               class="px-8 py-3 rounded-lg bg-green-600 hover:bg-green-500 font-semibold shadow-lg transition transform hover:scale-105">
               Confirmar Compra
            </button>

        </div>
    </form>

</div>

</body>
</html>
