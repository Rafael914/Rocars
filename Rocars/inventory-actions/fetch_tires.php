<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);


require_once '../includes/config.php';
require_once '../includes/auth.php';

$tire_id = $_GET['tire_id'] ?? null;

if (empty($tire_id) || !is_numeric($tire_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid tire_id']);
    exit;
}

$sql = "
    SELECT t.*, p.product_name, p.price, p.cost
    FROM tire_details t
    INNER JOIN products p ON t.product_id = p.product_id
    WHERE t.tire_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tire_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(array_merge(['success' => true], $row));
} else {
    echo json_encode(['success' => false, 'message' => 'No tire record found']);
}

?>