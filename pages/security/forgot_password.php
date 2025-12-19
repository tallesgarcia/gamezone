<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $token = bin2hex(random_bytes(50));
        $expiration = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $stmt_update = $conn->prepare("UPDATE usuarios SET reset_token = ?, token_expiration = ? WHERE email = ?");
        $stmt_update->bind_param("sss", $token, $expiration, $email);
        $stmt_update->execute();

        $link = "http://seusite.com/reset_password.php?token=" . $token;
        $assunto = "Recuperação de senha";
        $mensagem = "Clique no link para resetar sua senha: $link";
        $headers = "From: no-reply@seusite.com";

        mail($email, $assunto, $mensagem, $headers);

        $_SESSION['recuperacao_sucesso'] = true;
    } else {
        $_SESSION['erro_recuperacao'] = "E-mail não encontrado.";
    }

    header("Location: forgot_password.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Recuperar senha - GameZone</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="../assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-['Oxanium'] flex items-center justify-center min-h-screen px-4">

<div class="bg-gray-800 p-8 rounded-lg shadow-xl w-full max-w-md">
  <h1 class="text-3xl font-bold text-indigo-400 mb-6 text-center">Recuperar <span class="text-white">senha</span></h1>

  <form action="forgot_password.php" method="post" class="flex flex-col gap-4">
    <div>
      <label for="email" class="block mb-1 text-sm">E-mail</label>
      <input type="email" id="email" name="email" required
             class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>

    <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-500 text-white py-2 rounded font-semibold transition duration-200">
      Enviar link
    </button>
  </form>

  <p class="text-center text-sm mt-4 text-gray-400">
    <a href="entrar.php" class="text-indigo-400 hover:underline">Voltar ao login</a>
  </p>
</div>

<?php if (isset($_SESSION['recuperacao_sucesso'])): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'E-mail enviado!',
    text: 'Verifique sua caixa de entrada para resetar a senha.',
    timer: 2500,
    showConfirmButton: false
  });
</script>
<?php unset($_SESSION['recuperacao_sucesso']); endif; ?>

<?php if (isset($_SESSION['erro_recuperacao'])): ?>
<script>
  Swal.fire({
    icon: 'error',
    title: 'Erro',
    text: '<?= $_SESSION['erro_recuperacao'] ?>',
    timer: 2500,
    showConfirmButton: false
  });
</script>
<?php unset($_SESSION['erro_recuperacao']); endif; ?>

</body>
</html>
