<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
$engineoil_id = $_GET['oil_id'] ?? null;

if (!$engineoil_id) {
    echo json_encode(['error' => 'Missing engineoil_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM engineoil_details WHERE oil_id = ?");
$stmt->bind_param("i", $engineoil_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No engineoil record found']);
}
?>