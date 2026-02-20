<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
$lugnut_id = $_GET['lugnut_id'] ?? null;

if (!$lugnut_id) {
    echo json_encode(['error' => 'Missing lugnut_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM lugnuts_details WHERE lugnut_id = ?");
$stmt->bind_param("i", $lugnut_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No lugnut record found']);
}
?>