<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$search = $_GET['search'] ?? '';
$from   = $_GET['from_date'] ?? '';
$to     = $_GET['to_date'] ?? '';

$sql = "SELECT date, amount, details, ca, category, classification, remarks, code
        FROM expenses
        WHERE 1";

$params = [];
$types  = "";

/* filters */
if ($search !== '') {
    $sql .= " AND (details LIKE ? OR category LIKE ? OR classification LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if ($from !== '') {
    $sql .= " AND date >= ?";
    $params[] = $from;
    $types .= "s";
}

if ($to !== '') {
    $sql .= " AND date <= ?";
    $params[] = $to;
    $types .= "s";
}

$sql .= " ORDER BY date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();


$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Expenses');

/* Header Row */
$headers = [
    'A1' => 'Date',
    'B1' => 'Amount',
    'C1' => 'Details',
    'D1' => 'CA',
    'E1' => 'Category',
    'F1' => 'Classification',
    'G1' => 'Remarks',
    'H1' => 'Code'
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
    $sheet->getStyle($cell)->getFont()->setBold(true);
}

/* Data Rows */
$row = 2;
while ($r = $result->fetch_assoc()) {
    $sheet->setCellValue("A$row", $r['date']);
    $sheet->setCellValue("B$row", $r['amount']);
    $sheet->setCellValue("C$row", $r['details']);
    $sheet->setCellValue("D$row", $r['ca']);
    $sheet->setCellValue("E$row", $r['category']);
    $sheet->setCellValue("F$row", $r['classification']);
    $sheet->setCellValue("G$row", $r['remarks']);
    $sheet->setCellValue("H$row", $r['code']);
    $row++;
}


foreach (range('A','H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}


$filename = "expenses_" . date("Y-m-d_H-i-s") . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
