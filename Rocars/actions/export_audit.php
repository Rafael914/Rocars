<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require '../vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    if (!isset($_SESSION['branch_id'])) {
        throw new Exception("Session expired or Branch ID not found.");
    }

    $branchId = $_SESSION['branch_id'];
    $search = $_GET['search'] ?? '';

    $where = "WHERE u.branch_id = ?";
    $params = [$branchId];
    $types = "i";

    if (!empty($search)) {
        $where .= " AND (a.description LIKE ? OR u.username LIKE ? OR a.table_name LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like);
        $types .= "sss";
    }

    $query = "
        SELECT a.audit_id, u.username, a.action, a.table_name, a.record_id, a.description, a.created_at
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.user_id
        $where
        ORDER BY a.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // --- Create Spreadsheet ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Audit Logs');

    // Headers
    $headers = ['ID', 'User', 'Action', 'Table', 'Record ID', 'Description', 'Date'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Data
    $rowNum = 2;
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue("A$rowNum", $row['audit_id']);
        $sheet->setCellValue("B$rowNum", $row['username']);
        $sheet->setCellValue("C$rowNum", $row['action']);
        $sheet->setCellValue("D$rowNum", $row['table_name']);
        $sheet->setCellValue("E$rowNum", $row['record_id']);
        $sheet->setCellValue("F$rowNum", $row['description']);
        $sheet->setCellValue("G$rowNum", $row['created_at']);
        $rowNum++;
    }

    // Auto size columns
    foreach(range('A','G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Output to browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Audit_Log.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
