<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');


$category_name = trim($_POST['category_name'] ?? '');


if (!$category_name) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit;
}


$stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
$stmt->bind_param("s", $category_name);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Category added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Insert failed: '.$stmt->error]);
}

$stmt->close();
exit;
?>
