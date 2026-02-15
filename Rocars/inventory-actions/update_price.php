<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$product_id     = intval($_POST['product_id']);
$original_price = floatval($_POST['original_price']);
$percent        = floatval($_POST['percent']);

$new_price = $original_price + ($original_price * $percent / 100);
$new_price = round($new_price); 

$stmt = $conn->prepare("
    UPDATE products
    SET price = ?
    WHERE product_id = ?
");

$stmt->bind_param("di", $new_price, $product_id);

echo $stmt->execute() ? 'success' : 'error';
