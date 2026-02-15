<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$branch_id = $_SESSION['branch_id'] ?? null;

$search = trim($_GET['search'] ?? '');
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');

$baseQuery = "SELECT 
                m.mechanic_name,
                YEAR(s.date) AS year,
                MONTHNAME(s.date) AS month,
                SUM(s.front_incentive) AS frontIncentive,
                SUM(s.skill_incentive) AS skillIncentive,
                SUM(s.front_incentive + s.skill_incentive) AS totalIncentive
            FROM sales s
            LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id";

$where = ["s.branch_id = ?"];
$params = [$branch_id];
$types = "i";

if (!empty($search)) {
    $where[] = "(m.mechanic_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $types .= "s";
}

if (!empty($from) && !empty($to)) {
    $where[] = "s.date BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
    $types .= "ss";
}

$baseQuery .= " WHERE " . implode(" AND ", $where);
$baseQuery .= " GROUP BY s.mechanic_id, YEAR(s.date), MONTH(s.date)";
$baseQuery .= " ORDER BY s.mechanic_id, YEAR(s.date), MONTH(s.date)";

$stmt = $conn->prepare($baseQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

/* ======================
   CREATE EXCEL FILE
====================== */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Incentives');

/* Header */
$headers = [
    'A1' => 'Technician',
    'B1' => 'Month',
    'C1' => 'Front Incentive',
    'D1' => 'Skill Incentive',
    'E1' => 'Total Incentive'
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue("A$rowNum", $row['mechanic_name']);
    $sheet->setCellValue("B$rowNum", $row['month']);
    $sheet->setCellValue("C$rowNum", $row['frontIncentive']);
    $sheet->setCellValue("D$rowNum", $row['skillIncentive']);
    $sheet->setCellValue("E$rowNum", $row['totalIncentive']);
    $rowNum++;
}

/* Auto size columns */
foreach (range('A','E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* Download */
$filename = "Incentives_" . date('Y-m-d') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
