<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../includes/config.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validate input
$inventory_id = isset($data['inventory_id']) ? (int)$data['inventory_id'] : 0;
$quantity     = isset($data['quantity']) ? (int)$data['quantity'] : 0;

if ($inventory_id > 0) {
    $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE inventory_id = ?");
    $stmt->bind_param("ii", $quantity, $inventory_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $stmt->error
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid inventory_id'
    ]);
}
