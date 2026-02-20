<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inventoryId = (int)($_POST['inventory_id'] ?? 0);
    $newQty = (int)($_POST['new_qty'] ?? 0);
    $branchId = $_SESSION['branch_id'] ?? 0;

    if ($inventoryId > 0 && $branchId > 0) {
        $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE inventory_id = ? AND branch_id = ?");
        $stmt->bind_param("iii", $newQty, $inventoryId, $branchId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID or Session']);
    }
}