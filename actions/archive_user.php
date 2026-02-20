<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$user_id  = (int)$_POST['user_id'];
$actor_id = $_SESSION['user_id'] ?? 0;

// 1️⃣ Get fullname BEFORE archiving
$nameStmt = $conn->prepare(
    "SELECT fullname, archived_at FROM users WHERE user_id = ?"
);
$nameStmt->bind_param("i", $user_id);
$nameStmt->execute();
$result = $nameStmt->get_result();
$user   = $result->fetch_assoc();
$nameStmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Check if already archived
if ($user['archived_at'] !== null) {
    echo json_encode(['success' => false, 'message' => 'User is already archived']);
    exit;
}

// 2️⃣ Archive user
$stmt = $conn->prepare("UPDATE users SET archived_at = NOW() WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // ✅ Audit log
    auditLog(
        $conn,
        $actor_id,
        'ARCHIVE',
        'users',
        $user_id,
        "User archived: {$user['fullname']}"
    );

    echo json_encode([
        'success' => true,
        'message' => "User {$user['fullname']} archived successfully"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ]);
}

$stmt->close();
exit;
