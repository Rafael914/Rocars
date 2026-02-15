<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
$accessories_id = $_GET['accessories_id'] ?? null;

if (!$accessories_id) {
    echo json_encode(['error' => 'Missing accessories_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM accessories_details WHERE accessories_id = ?");
$stmt->bind_param("i", $accessories_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No accessories record found']);
}
?>
