<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../config/db.php';

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

// Verificação dos campos obrigatórios
if (
    !isset($_POST['nome'], $_POST['email'], $_POST['senha'], $_POST['conf_senha'], $_POST['interesses']) ||
    empty($_POST['nome']) || empty($_POST['email']) || empty($_POST['senha']) || empty($_POST['conf_senha'])
) {
    die("Preencha todos os campos obrigatórios.");
}

$nome = trim($_POST['nome']);
$email = trim($_POST['email']);
$senha = $_POST['senha'];
$conf_senha = $_POST['conf_senha'];
$interesses = $_POST['interesses']; // array de interesses (checkbox ou múltipla escolha)

// Verifica se selecionou pelo menos 2 interesses
if (!is_array($interesses) || count($interesses) < 2) {
    die("Você precisa selecionar no mínimo 2 interesses.");
}

// Verifica se as senhas coincidem
if ($senha !== $conf_senha) {
    die("As senhas não coincidem.");
}

// =========================================================
// NOVO CÓDIGO: VALIDAÇÃO DE FORÇA DA SENHA
// =========================================================

$erros_senha = [];

// 1. Comprimento mínimo (8 caracteres)
if (strlen($senha) < 8) {
    $erros_senha[] = "ter no mínimo 8 caracteres";
}

// 2. Pelo menos uma letra maiúscula
if (!preg_match('/[A-Z]/', $senha)) {
    $erros_senha[] = "pelo menos uma letra maiúscula";
}

// 3. Pelo menos 1 número
if (!preg_match('/[0-9]/', $senha)) {
    $erros_senha[] = "pelo menos 1 número";
}

// 4. Não permitir espaçamentos (espaço em branco, tab, nova linha, etc. O '_' é a exceção do requisito de espaçamento, mas espaços literais são proibidos)
if (preg_match('/\s/', $senha)) {
    $erros_senha[] = "não conter espaçamentos em branco";
}

// 5. Pelo menos 1 caractere especial (qualquer caractere que não seja letra, número ou underscore)
// A regex /[^a-zA-Z0-9_]/ procura por qualquer caractere que não esteja no grupo de letras, números ou underscore.
if (!preg_match('/[^a-zA-Z0-9_]/', $senha)) {
    $erros_senha[] = "pelo menos 1 caractere especial (símbolo)";
}

if (!empty($erros_senha)) {
    // Monta uma mensagem de erro clara e informativa
    $msg = "❌ Senha fraca. Sua senha deve atender aos seguintes requisitos:\n- " . implode("\n- ", $erros_senha);
    die($msg);
}

// =========================================================
// FIM DA VALIDAÇÃO DE FORÇA DA SENHA

// Verifica se o e-mail já está cadastrado
$stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmtCheck->bind_param("s", $email);
$stmtCheck->execute();
$stmtCheck->store_result();
if ($stmtCheck->num_rows > 0) {
    die("Este e-mail já está cadastrado.");
}
$stmtCheck->close();

// Criptografa a senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Insere o usuário
$stmtUser = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo_usuario) VALUES (?, ?, ?, 'comum')");
$stmtUser->bind_param("sss", $nome, $email, $senhaHash);
if (!$stmtUser->execute()) {
    die("Erro ao cadastrar usuário: " . $stmtUser->error);
}

$userId = $stmtUser->insert_id;
$stmtUser->close();

// Interesses válidos permitidos
$interessesValidos = ['RPG', 'FPS', 'MOBA', 'Aventura', 'Terror', 'Corrida', 'Sobrevivência'];

// Insere os interesses do usuário
$stmtInt = $conn->prepare("INSERT INTO interesses (usuario_id, interesse) VALUES (?, ?)");
foreach ($interesses as $interesse) {
    $interesse = trim($interesse);
    if (in_array($interesse, $interessesValidos)) {
        $stmtInt->bind_param("is", $userId, $interesse);
        $stmtInt->execute();
    }
}
$stmtInt->close();

// Define variável de sessão para sucesso
$_SESSION['cadastro_sucesso'] = true;

// Redireciona para login
header("Location: entrar.php");
exit();
?>