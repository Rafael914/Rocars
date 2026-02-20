<?php
// Prevent any accidental whitespace or errors from echoing before we are ready
ob_start();

require_once '../includes/config.php';
require_once '../includes/auth.php';

// --- TIMEZONE FIX ---
// 1. Set PHP internal timezone
date_default_timezone_set('Asia/Manila');

// 2. Set MySQL connection timezone (crucial for DATE functions)
$conn->query("SET time_zone = '+08:00'");

// Clear any buffers (like PHP notices) and set header
ob_clean();
header('Content-Type: application/json');

try {
    $expense_id = filter_var($_GET['expense_id'] ?? 0, FILTER_VALIDATE_INT);
    $branchId   = $_SESSION['branch_id'] ?? 0;

    if (!$expense_id) {
        echo json_encode(null);
        exit;
    }


    $stmt = $conn->prepare("SELECT *, DATE_FORMAT(date, '%Y-%m-%d %H:%i:%s') as formatted_date FROM expenses WHERE expense_id = ? AND branch_id = ?");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $expense_id, $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();


    echo json_encode($data ?: null);

} catch (Exception $e) {
    // If an error happens, we still return valid JSON so JS doesn't crash
    http_response_code(500);
    echo json_encode([
        "error" => "Database error occurred",
        "message" => $e->getMessage()
    ]);
}

exit;