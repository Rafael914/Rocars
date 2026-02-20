<?php
require '../includes/config.php';
require '../includes/auth.php';

$response = ['success' => false];

$user_id = $_GET['user_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT 
        user_id,
        username,
        fullname,
        email,
        contact_number,
        branch_id,
        role
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $response['success'] = true;
    $response['user'] = $user;
} else {
    $response['message'] = 'User not found.';
}

echo json_encode($response);
