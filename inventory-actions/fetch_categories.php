<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$categories = [];
while($row = $result->fetch_assoc()){
    $categories[] = $row;
}

echo json_encode($categories);
exit;
?>
