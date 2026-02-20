<?php
ob_start();
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
date_default_timezone_set('Asia/Manila');
ob_clean();

// 1. Get Branch from Session (Automatic Security)
$branch_id = $_SESSION['branch_id'] ?? null;

// 2. Capture Inputs
$amount         = (float)($_POST['amount'] ?? 0);
$details        = $_POST['details'] ?? '';
$ca             = (float)($_POST['ca'] ?? 0);
$category       = $_POST['category'] ?? '';
$classification = $_POST['classification'] ?? '';
$remarks        = $_POST['remarks'] ?? '';
$code           = $_POST['code'] ?? '';


$full_datetime = date("Y-m-d H:i:s"); 
$month_name    = date("F"); 

if (!$branch_id || $amount <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid Branch or Amount"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO expenses
        (branch_id, month_name, `date`, amount, details, ca, category, classification, remarks, code)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "issdsdssss",
        $branch_id,
        $month_name,
        $full_datetime, // This now contains the automatic 24h time
        $amount,
        $details,
        $ca,
        $category,
        $classification,
        $remarks,
        $code
    );

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "expense_id" => $stmt->insert_id]);
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

$conn->close();