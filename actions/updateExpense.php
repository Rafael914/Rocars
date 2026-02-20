<?php
ob_start();
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

ob_clean();

$user_id   = $_SESSION['user_id'] ?? 0;
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid request method']);
    exit;
}

$expense_id = (int)($_POST['expense_id'] ?? 0);
if (!$expense_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Expense ID missing from request']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE expense_id = ? AND branch_id = ?");
    $stmt->bind_param("ii", $expense_id, $branch_id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$old) {
        echo json_encode(['status' => 'error', 'msg' => 'Expense not found']);
        exit;
    }

    $input_date = $_POST['date'] ?? '';
    // Fix: Keep the original time or use current time instead of defaulting to 00:00:00
    $time_part = date('H:i:s'); 
    $full_date = !empty($input_date) ? date('Y-m-d H:i:s', strtotime("$input_date $time_part")) : $old['date'];
    
    $month_name     = date('F', strtotime($full_date));
    $amount         = (float)($_POST['amount'] ?? 0);
    $details        = $_POST['details'] ?? '';
    $ca             = (float)($_POST['ca'] ?? 0);
    $category       = $_POST['category'] ?? '';
    $classification = $_POST['classification'] ?? '';
    $remarks        = $_POST['remarks'] ?? '';
    $code           = $_POST['code'] ?? '';

    $stmt = $conn->prepare("
        UPDATE expenses 
        SET month_name = ?, date = ?, amount = ?, details = ?, ca = ?, 
            category = ?, classification = ?, remarks = ?, code = ? 
        WHERE expense_id = ? AND branch_id = ?
    ");

    $stmt->bind_param("ssdssssssii", 
        $month_name, $full_date, $amount, $details, $ca, 
        $category, $classification, $remarks, $code, $expense_id, $branch_id
    );

    if ($stmt->execute()) {
        $changes = [];
        if (date('Y-m-d', strtotime($old['date'])) !== date('Y-m-d', strtotime($full_date))) $changes[] = "Date Changed";
        if ((float)$old['amount'] != $amount) $changes[] = "Amount updated";

        if (!empty($changes)) {
            $logMessage = "Updated expense ID $expense_id: " . implode(', ', $changes);
            auditLog($conn, $user_id, 'UPDATE', 'expenses', $expense_id, $logMessage);
        }
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => $conn->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}