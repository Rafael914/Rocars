<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
$battery_id = $_GET['battery_id'] ?? null;

if (!$battery_id) {
    echo json_encode(['error' => 'Missing battery_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM battery_details WHERE battery_id = ?");
$stmt->bind_param("i", $battery_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No battery record found']);
}
?>