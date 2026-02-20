<?php
ob_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

try {
    $mechanic_id = filter_input(INPUT_POST, 'mechanic_id', FILTER_VALIDATE_INT);
    $actor_id    = $_SESSION['user_id'] ?? 0;

    if (!$mechanic_id) {
        throw new Exception('Invalid Technician ID');
    }

    // 1️⃣ Get mechanic name BEFORE restoring
    $nameStmt = $conn->prepare(
        "SELECT mechanic_name, archived_at FROM mechanics WHERE mechanic_id = ?"
    );
    $nameStmt->bind_param("i", $mechanic_id);
    $nameStmt->execute();
    $result = $nameStmt->get_result();
    $row    = $result->fetch_assoc();
    $nameStmt->close();

    if (!$row) {
        throw new Exception('Mechanic not found');
    }

    // Optional: check if mechanic is already active
    if ($row['archived_at'] === null) {
        throw new Exception("Technician {$row['mechanic_name']} is already active");
    }

    $mechanic_name = $row['mechanic_name'];

    // 2️⃣ Restore mechanic
    $stmt = $conn->prepare(
        "UPDATE mechanics SET archived_at = NULL WHERE mechanic_id = ?"
    );
    $stmt->bind_param("i", $mechanic_id);

    if ($stmt->execute()) {

        // ✅ Audit log AFTER success
        auditLog(
            $conn,
            $actor_id,
            'RESTORE',
            'mechanics',
            $mechanic_id,
            "Technician restored from archive: {$mechanic_name}"
        );

        $response = [
            'success' => true,
            'message' => "Technician {$mechanic_name} restored successfully!"
        ];

    } else {
        throw new Exception($stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

ob_clean();
echo json_encode($response);
exit;
