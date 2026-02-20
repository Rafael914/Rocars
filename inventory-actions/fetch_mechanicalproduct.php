<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
$mechanical_id = $_GET['mechanical_id'] ?? null;

if (!$mechanical_id) {
    echo json_encode(['error' => 'Missing mechanical_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM mechanical_details WHERE mechanical_id = ?");
$stmt->bind_param("i", $mechanical_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No mags record found']);
}
?>
