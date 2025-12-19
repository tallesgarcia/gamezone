<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
session_start();

use Dompdf\Dompdf;
use Dompdf\Options;

// -------------------------
// Segurança
// -------------------------
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    die("Acesso negado.");
}

// -------------------------
// FILTROS
// -------------------------
$where = " WHERE 1=1 ";
$params = [];
$types  = "";

// Filtro usuário (nome)
if (!empty($_GET['usuario'])) {
    $where .= " AND u.nome LIKE ? ";
    $params[] = "%" . $_GET['usuario'] . "%";
    $types   .= "s";
}

// Filtro produto (nome)
if (!empty($_GET['produto'])) {
    $where .= " AND p.nome LIKE ? ";
    $params[] = "%" . $_GET['produto'] . "%";
    $types   .= "s";
}

// Filtro status
if (!empty($_GET['status'])) {
    $where .= " AND c.status = ? ";
    $params[] = $_GET['status'];
    $types   .= "s";
}

// -------------------------
// CONSULTA
// -------------------------
$sql = "SELECT 
            c.id,
            u.nome AS usuario,
            p.nome AS produto,
            c.quantidade,
            c.valor_total,
            c.status,
            c.data_compra
        FROM compras c
        LEFT JOIN usuarios u ON u.id = c.usuario_id
        LEFT JOIN produtos p ON p.id = c.produto_id
        $where
        ORDER BY c.data_compra DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erro ao preparar SQL: " . $conn->error);
}

if ($types) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// -------------------------
// GERAR HTML DO PDF
// -------------------------
$html = "
<h2 style='text-align:center; font-family: Arial;'>Relatório de Compras</h2>

<table width='100%' border='1' cellspacing='0' cellpadding='6' 
       style='border-collapse: collapse; font-family: Arial; font-size: 12px;'>
<thead>
<tr style='background:#f0f0f0; font-weight:bold;'>
    <th>ID</th>
    <th>Usuário</th>
    <th>Produto</th>
    <th>Qtde</th>
    <th>Valor Total</th>
    <th>Status</th>
    <th>Data</th>
</tr>
</thead>
<tbody>
";

while ($row = $result->fetch_assoc()) {

    $html .= "<tr>
        <td>{$row['id']}</td>
        <td>{$row['usuario']}</td>
        <td>{$row['produto']}</td>
        <td>{$row['quantidade']}</td>
        <td>R$ " . number_format($row['valor_total'], 2, ',', '.') . "</td>
        <td>{$row['status']}</td>
        <td>{$row['data_compra']}</td>
    </tr>";
}

$html .= "</tbody></table>";

// -------------------------
// GERAR PDF COM DOMPDF
// -------------------------
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true); // permite carregar imagens externas

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, "UTF-8");
$dompdf->setPaper("A4", "landscape"); // A4 paisagem
$dompdf->render();

// Forçar download
$dompdf->stream("compras.pdf", ["Attachment" => true]);
exit;
