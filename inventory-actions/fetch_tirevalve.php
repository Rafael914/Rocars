<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';

$tirevalve_id = $_GET['tirevalve_id'] ?? null;

if (!$tirevalve_id) {
    echo json_encode(['error' => 'Missing tirevalve_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tirevalve_details WHERE tirevalve_id = ?");
$stmt->bind_param("i", $tirevalve_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No tirevalve record found']);
}
?>