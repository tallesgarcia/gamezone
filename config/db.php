<?php
// Configurações do banco de dados
$host = "localhost"; // usa IP direto para evitar problemas de resolução DNS
$usuario = "gamezone";
$senha = "gamezone&DB&IFSUL25";
$banco = "db_20252_gamezone";

// Cria a conexão com o banco de dados usando MySQLi
$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    // Em produção, evite exibir mensagens de erro detalhadas
    die("Erro ao conectar ao banco de dados.");
}

// Define o charset para suportar acentos, emojis e caracteres especiais
$conn->set_charset("utf8mb4");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão (PDO): " . $e->getMessage());
}

?>
