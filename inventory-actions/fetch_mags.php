<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
$mags_id = $_GET['mags_id'] ?? null;

if (!$mags_id) {
    echo json_encode(['error' => 'Missing mags_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM mags_details WHERE mags_id = ?");
$stmt->bind_param("i", $mags_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No mags record found']);
}
?>
