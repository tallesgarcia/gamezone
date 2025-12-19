<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';
session_start();


// Verifica se o modo manutenção está ativado para usuários comuns
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $stmt = $conn->prepare("SELECT nome, valor FROM configuracoes WHERE nome IN ('modo_manutencao', 'mensagem_manutencao')");
    $stmt->execute();
    $res = $stmt->get_result();

    $modo_manutencao = '0';
    $mensagem_manutencao = 'Estamos temporariamente em manutenção. Tente novamente em breve.';

    while ($row = $res->fetch_assoc()) {
        if ($row['nome'] === 'modo_manutencao') {
            $modo_manutencao = $row['valor'];
        }
        if ($row['nome'] === 'mensagem_manutencao') {
            $mensagem_manutencao = $row['valor'];
        }
    }

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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Login - GameZone</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../assets/css/estilos.css">
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-900 text-white font-['Oxanium'] flex items-center justify-center min-h-screen px-4">

  <div class="bg-gray-800 p-8 rounded-lg shadow-xl w-full max-w-md">
    <h1 class="text-3xl font-bold text-indigo-400 mb-6 text-center">Entrar no <span class="text-white">GameZone</span></h1>

    <!-- Feedback de cadastro e interesses -->
    <?php if (isset($_SESSION['cadastro_sucesso'])): ?>
      <script>
        document.addEventListener('DOMContentLoaded', () => {
          Swal.fire({
            icon: 'success',
            title: 'Cadastro realizado!',
            text: 'Faça login para continuar.',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
          });
        });
      </script>
      <?php unset($_SESSION['cadastro_sucesso']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['interesses_salvos'])): ?>
      <script>
        document.addEventListener('DOMContentLoaded', () => {
          Swal.fire({
            icon: 'info',
            title: 'Interesses salvos!',
            text: 'Agora entre na sua conta.',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
          });
        });
      </script>
      <?php unset($_SESSION['interesses_salvos']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['erro_login'])): ?>
      <script>
        document.addEventListener('DOMContentLoaded', () => {
          Swal.fire({
            icon: 'error',
            title: 'Erro ao entrar',
            text: '<?= $_SESSION['erro_login'] ?>',
            timer: 3000,
            showConfirmButton: false,
            timerProgressBar: true
          });
        });
      </script>
      <?php unset($_SESSION['erro_login']); ?>
    <?php endif; ?>

    <form action="login.php" method="POST" class="flex flex-col gap-4">
      <div>
        <label for="username" class="block mb-1 text-sm">E-mail</label>
        <input type="email" id="username" name="username" required
               class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label for="password" class="block mb-1 text-sm">Senha</label>
        <input type="password" id="password" name="password" required
               class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
      </div>

      <button type="submit"
              class="bg-indigo-600 hover:bg-indigo-500 text-white py-2 rounded font-semibold transition duration-200">
        Entrar
      </button>
    </form>

    <p class="text-center text-sm mt-4 text-gray-400">
      Não tem conta?
      <a href="cadastrar.html" class="text-indigo-400 hover:underline">Cadastre-se aqui</a>
    </p>
    <p class="text-center text-sm mt-4 text-gray-400">
      Esqueceu a senha?
      <a href="forgot_password.php" class="text-indigo-400 hover:underline">Recuperar senha</a>
    </p>
  </div>

</body>
</html>