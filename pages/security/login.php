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

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if ($row['nome'] === 'modo_manutencao') {
                $modo_manutencao = $row['valor'];
            }
            if ($row['nome'] === 'mensagem_manutencao') {
                $mensagem_manutencao = $row['valor'];
            }
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

// Processamento do login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $_SESSION['erro_login'] = "Preencha todos os campos.";
        header("Location: entrar.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, email, senha, tipo_usuario FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $user_data = $res->fetch_assoc();

        if (password_verify($password, $user_data['senha'])) {
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['email'] = $user_data['email'];
            $_SESSION['tipo_usuario'] = $user_data['tipo_usuario'] ?? 'comum';

            $stmt->close();
            $conn->close();

            header("Location: ../../index.php");
            exit();
        } else {
            $_SESSION['erro_login'] = "Senha incorreta.";
            $stmt->close();
            $conn->close();
            header("Location: entrar.php");
            exit();
        }
    } else {
        $_SESSION['erro_login'] = "Usuário não encontrado.";
        $stmt->close();
        $conn->close();
        header("Location: entrar.php");
        exit();
    }
}
?>
