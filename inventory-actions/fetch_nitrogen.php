<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
$nitrogen_id = $_GET['nitrogen_id'] ?? null;

if (!$nitrogen_id) {
    echo json_encode(['error' => 'Missing nitrogen_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM nitrogen_details WHERE nitrogen_id = ?");
$stmt->bind_param("i", $nitrogen_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No nitrogen record found']);
}
?>
