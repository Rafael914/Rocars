<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$branch_id = $_SESSION['branch_id'] ?? null;

// Filters
$search = trim($_GET['search'] ?? '');
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');

// Base query (NO pagination)
$query = "
    SELECT 
        s.date,
        m.mechanic_name,
        s.si_number,
        s.customer_name,
        s.front_incentive,
        s.skill_incentive,
        (s.front_incentive + s.skill_incentive) AS total_incentive
    FROM sales s
    LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
";

$where = ["s.branch_id = ?"];
$params = [$branch_id];
$types = "i";

if (!empty($search)) {
    $where[] = "(m.mechanic_name LIKE ? OR s.si_number LIKE ? OR s.customer_name LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
    $types .= "sss";
}

if (!empty($from) && !empty($to)) {
    $where[] = "s.date BETWEEN ? AND ?";
    array_push($params, $from, $to);
    $types .= "ss";
}

$query .= " WHERE " . implode(" AND ", $where);
$query .= " ORDER BY s.date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

/* ======================
   CREATE EXCEL
====================== */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Sales Incentives');

/* Headers */
$headers = [
    'A1' => 'Date',
    'B1' => 'Mechanic',
    'C1' => 'SI #',
    'D1' => 'Customer',
    'E1' => 'Front Incentive',
    'F1' => 'Skill Incentive',
    'G1' => 'Total Incentive'
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue("A$rowNum", $row['date']);
    $sheet->setCellValue("B$rowNum", $row['mechanic_name']);
    $sheet->setCellValue("C$rowNum", $row['si_number']);
    $sheet->setCellValue("D$rowNum", $row['customer_name']);
    $sheet->setCellValue("E$rowNum", $row['front_incentive']);
    $sheet->setCellValue("F$rowNum", $row['skill_incentive']);
    $sheet->setCellValue("G$rowNum", $row['total_incentive']);
    $rowNum++;
}

/* Auto-size columns */
foreach (range('A','G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* Download */
$filename = "Sales_Incentives_" . date('Y-m-d') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
