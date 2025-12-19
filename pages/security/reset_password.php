<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_GET['token'])) {
    die("Token inválido.");
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND token_expiration > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die("Token inválido ou expirado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $stmt_update = $conn->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, token_expiration = NULL WHERE reset_token = ?");
    $stmt_update->bind_param("ss", $nova_senha, $token);
    $stmt_update->execute();

    $_SESSION['senha_alterada'] = true;
    header("Location: entrar.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Redefinir senha - GameZone</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="../assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-['Oxanium'] flex items-center justify-center min-h-screen px-4">

<div class="bg-gray-800 p-8 rounded-lg shadow-xl w-full max-w-md">
  <h1 class="text-3xl font-bold text-indigo-400 mb-6 text-center">Redefinir <span class="text-white">senha</span></h1>

  <form action="" method="post" class="flex flex-col gap-4">
    <div>
      <label for="senha" class="block mb-1 text-sm">Nova senha</label>
      <input type="password" id="senha" name="senha" required
             class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>

    <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-500 text-white py-2 rounded font-semibold transition duration-200">
      Atualizar senha
    </button>
  </form>

  <p class="text-center text-sm mt-4 text-gray-400">
    <a href="entrar.php" class="text-indigo-400 hover:underline">Voltar ao login</a>
  </p>
</div>

<?php if (isset($_SESSION['senha_alterada'])): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'Senha alterada!',
    text: 'Agora você pode entrar com a nova senha.',
    timer: 2500,
    showConfirmButton: false
  });
</script>
<?php unset($_SESSION['senha_alterada']); endif; ?>

</body>
</html>
