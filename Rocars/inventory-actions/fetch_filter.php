<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
$filter_id = $_GET['filter_id'] ?? null;

if (!$filter_id) {
    echo json_encode(['error' => 'Missing filter_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM filter_details WHERE filter_id = ?");
$stmt->bind_param("i", $filter_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No filter record found']);
}
?>
