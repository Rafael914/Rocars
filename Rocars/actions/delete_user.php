<?php
require_once '../includes/config.php'; 
require_once '../includes/auth.php'; 
header('Content-Type: application/json');

if (!isset($_POST['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

$user_id = (int)$_POST['user_id'];

// Delete user permanently
$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete user'
    ]);
}
