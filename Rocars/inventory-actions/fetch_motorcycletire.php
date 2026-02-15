<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
$motortire_id = $_GET['motortire_id'] ?? null;

if (!$motortire_id) {
    echo json_encode(['error' => 'Missing motortire_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM motorcycle_tires_details WHERE motortire_id = ?");
$stmt->bind_param("i", $motortire_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No motortire record found']);
}
?>
