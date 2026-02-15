<?php
require_once '../includes/config.php'; 
require_once '../includes/auditLog.php';

// Ensure session is started if not already in config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? 0;

// 1. Validate Input
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "error: missing id";
    exit;
}

$category_id = intval($_GET['id']);

// 2. Fetch Category Name BEFORE deletion for the Audit Log
$categoryName = "Unknown";
$getNameStmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
$getNameStmt->bind_param("i", $category_id);
$getNameStmt->execute();
$getNameStmt->bind_result($categoryName);
$getNameStmt->fetch();
$getNameStmt->close();

// 3. Perform Deletion
$stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
$stmt->bind_param("i", $category_id);

if ($stmt->execute()) {
    // 4. Log the action
    $logMessage = "Deleted Category: " . $categoryName;
    auditLog($conn, $userId, 'DELETE', 'categories', $category_id, $logMessage);
    
    echo "success";
} else {
    echo "error: " . $conn->error;
}

$stmt->close();
$conn->close();
?>