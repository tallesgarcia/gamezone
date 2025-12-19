<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
session_start();

// Segurança
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    die("Acesso negado.");
}

// -------------------------
// FILTROS
// -------------------------
$where = " WHERE 1=1 ";
$params = [];
$types  = "";

// Filtro usuário
if (!empty($_GET['usuario'])) {
    $where .= " AND u.nome LIKE ? ";
    $params[] = "%" . $_GET['usuario'] . "%";
    $types   .= "s";
}

// Filtro produto
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

$sql = "SELECT c.id, u.nome AS usuario, p.nome AS produto, c.quantidade,
               c.valor_total, c.status, c.data_compra
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
// GERAR XLSX
// -------------------------
$xlsx = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $xlsx->getActiveSheet();

$sheet->fromArray(
    ['ID', 'Usuário', 'Produto', 'Quantidade', 'Valor Total', 'Status', 'Data'],
    NULL,
    'A1'
);

$rowIndex = 2;

while ($row = $result->fetch_assoc()) {
    $sheet->fromArray([
        $row["id"],
        $row["usuario"],
        $row["produto"],
        $row["quantidade"],
        $row["valor_total"],
        $row["status"],
        $row["data_compra"]
    ], NULL, "A$rowIndex");

    $rowIndex++;
}

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=compras.xlsx");

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xlsx);
$writer->save("php://output");
exit;
