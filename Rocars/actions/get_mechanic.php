<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$id = (int)$_GET['id'];
$result = $conn->query("SELECT * FROM mechanics WHERE mechanic_id = $id");
$data = $result->fetch_assoc();

if ($data) {
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false]);
}