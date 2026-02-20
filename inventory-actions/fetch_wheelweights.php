<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';

$wheel_id = $_GET['wheel_id'] ?? null;

if (!$wheel_id) {
    echo json_encode(['error' => 'Missing wheel_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM wheelweights_details WHERE wheel_id = ?");
$stmt->bind_param("i", $wheel_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No wheelweights record found']);
}
?>