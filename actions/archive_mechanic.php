<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

header('Content-Type: application/json');

if (!isset($_POST['mechanic_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

$mechanic_id = (int) $_POST['mechanic_id'];
$actor_id    = $_SESSION['user_id'] ?? 0;

/**
 * 1️⃣ Get mechanic name BEFORE archiving
 */
$nameStmt = $conn->prepare(
    "SELECT mechanic_name, archived_at FROM mechanics WHERE mechanic_id = ?"
);
$nameStmt->bind_param("i", $mechanic_id);
$nameStmt->execute();
$nameResult = $nameStmt->get_result();
$mechanic   = $nameResult->fetch_assoc();
$nameStmt->close();

if (!$mechanic) {
    echo json_encode([
        'success' => false,
        'message' => 'Mechanic not found'
    ]);
    exit;
}

// Optional: check if already archived
if ($mechanic['archived_at'] !== null) {
    echo json_encode([
        'success' => false,
        'message' => "Mechanic {$mechanic['mechanic_name']} is already archived"
    ]);
    exit;
}

$mechanic_name = $mechanic['mechanic_name'];

/**
 * 2️⃣ Archive mechanic
 */
$stmt = $conn->prepare("
    UPDATE mechanics
    SET archived_at = NOW()
    WHERE mechanic_id = ?
");
$stmt->bind_param("i", $mechanic_id);

if ($stmt->execute()) {

    // ✅ AUDIT LOG WITH NAME
    auditLog(
        $conn,
        $actor_id,
        'ARCHIVE',
        'mechanics',
        $mechanic_id,
        "Technician archived: {$mechanic_name}"
    );

    echo json_encode([
        'success' => true,
        'message' => "Mechanic {$mechanic_name} archived successfully"
    ]);

} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to archive mechanic'
    ]);
}

$stmt->close();
exit;
