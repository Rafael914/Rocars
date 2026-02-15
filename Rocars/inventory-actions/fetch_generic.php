<?php
header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';

$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    echo json_encode(['error' => 'Missing product_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo json_encode(['error' => 'Product not found']);
    exit;
}

echo json_encode($data);
