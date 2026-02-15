<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Validate user_id
if (empty($_POST['user_id'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'User ID is required'
    ]);
    exit;
}

$user_id  = (int)$_POST['user_id'];
$actor_id = $_SESSION['user_id'] ?? 0;

// 1️⃣ Get fullname BEFORE restoring
$nameStmt = $conn->prepare(
    "SELECT fullname, archived_at FROM users WHERE user_id = ?"
);
$nameStmt->bind_param("i", $user_id);
$nameStmt->execute();
$nameResult = $nameStmt->get_result();
$user = $nameResult->fetch_assoc();
$nameStmt->close();

if (!$user) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User not found'
    ]);
    exit;
}

// Optional: check if already active
if ($user['archived_at'] === null) {
    echo json_encode([
        'status' => 'error',
        'message' => "User {$user['fullname']} is already active"
    ]);
    exit;
}

// 2️⃣ Restore user
$stmt = $conn->prepare(
    "UPDATE users SET archived_at = NULL WHERE user_id = ?"
);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {

    // ✅ Audit log AFTER success
    auditLog(
        $conn,
        $actor_id,
        'RESTORE',
        'users',
        $user_id,
        "User account restored: {$user['fullname']}"
    );

    echo json_encode([
        'status' => 'success',
        'message' => "User {$user['fullname']} restored successfully"
    ]);

} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to restore user'
    ]);
}

$stmt->close();
exit;
