<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../includes/config.php';
require_once '../includes/auth.php'; 
require_once '../includes/auditLog.php';

$user_id  = $_SESSION['user_id'] ?? 0;
$branchId = $_SESSION['branch_id'] ?? 0;

if (!isset($_POST['expense_id'])) {
    ob_end_clean();
    echo json_encode(["status" => "error", "msg" => "No expense ID provided"]);
    exit;
}

$expense_id = (int)$_POST['expense_id'];

$queryOld = "SELECT amount, category, date FROM expenses WHERE expense_id = ? AND branch_id = ?";
$stmtOld = $conn->prepare($queryOld);
$stmtOld->bind_param("ii", $expense_id, $branchId);
$stmtOld->execute();
$resultOld = $stmtOld->get_result();
$oldData = $resultOld->fetch_assoc();
$stmtOld->close();

if (!$oldData) {
    ob_end_clean();
    echo json_encode(["status" => "error", "msg" => "Expense not found or unauthorized"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM expenses WHERE expense_id = ? AND branch_id = ?");
$stmt->bind_param("ii", $expense_id, $branchId);

if ($stmt->execute()) {
    $logMsg = "Deleted expense ID: $expense_id (Amount: {$oldData['amount']}, Category: {$oldData['category']}, Date: {$oldData['date']})";
    auditLog($conn, $user_id, 'DELETE', 'expenses', $expense_id, $logMsg);

    ob_end_clean();
    echo json_encode(["status" => "success"]);
} else {
    ob_end_clean();
    echo json_encode(["status" => "error", "msg" => $stmt->error]); 
}

$stmt->close();