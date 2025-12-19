<?php
require_once __DIR__ . '/../config/db.php';

$email = $_POST['email'] ?? '';
$produto = $_POST['produto'] ?? '';
$data_ini = $_POST['data_ini'] ?? '';
$data_fim = $_POST['data_fim'] ?? '';

$sql = "
SELECT 
    c.id,
    u.email,
    p.nome AS produto_nome,
    c.quantidade,
    c.valor_total,
    c.data_compra
FROM compras c
INNER JOIN usuarios u ON c.usuario_id = u.id
INNER JOIN produtos p ON c.produto_id = p.id
WHERE 1=1
";

// FILTRO POR EMAIL
if ($email !== "") {
    $email = $conn->real_escape_string($email);
    $sql .= " AND u.email LIKE '%$email%'";
}

// FILTRO POR PRODUTO
if ($produto !== "") {
    $produto = $conn->real_escape_string($produto);
    $sql .= " AND p.nome LIKE '%$produto%'";
}

// FILTRO POR INTERVALO DE DATAS
if ($data_ini !== "") {
    $sql .= " AND DATE(c.data_compra) >= '$data_ini'";
}

if ($data_fim !== "") {
    $sql .= " AND DATE(c.data_compra) <= '$data_fim'";
}

$sql .= " ORDER BY c.data_compra DESC";

$res = $conn->query($sql);

echo "
<table class='w-full border-collapse'>
<tr class='bg-zinc-800 text-left'>
    <th class='p-3'>ID</th>
    <th class='p-3'>Usuário</th>
    <th class='p-3'>Produto</th>
    <th class='p-3'>Qtd</th>
    <th class='p-3'>Valor</th>
    <th class='p-3'>Data</th>
</tr>
";

if ($res->num_rows === 0) {
    echo "
    <tr><td colspan='6' class='p-4 text-center text-gray-400'>
        Nenhuma compra encontrada.
    </td></tr>";
} else {
    while ($c = $res->fetch_assoc()) {
        echo "
        <tr class='border-b border-zinc-700'>
            <td class='p-3'>{$c['id']}</td>
            <td class='p-3'>".htmlspecialchars($c['email'])."</td>
            <td class='p-3'>".htmlspecialchars($c['produto_nome'])."</td>
            <td class='p-3'>{$c['quantidade']}</td>
            <td class='p-3 text-green-400'>R$ ".number_format($c['valor_total'],2,',','.')."</td>
            <td class='p-3'>".date('d/m/Y H:i', strtotime($c['data_compra']))."</td>
        </tr>
        ";
    }
}

echo "</table>";
