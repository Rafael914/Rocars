<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$search = $_GET['query'] ?? '';
$search = $conn->real_escape_string($search);

$sql = "SELECT DISTINCT customer_name, vehicle, plate_no, odometer, cp_number
        FROM sales
        WHERE plate_no LIKE '%$search%'
        LIMIT 10";

$result = $conn->query($sql);

$plates = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $plates[] = $row;
    }
}

echo json_encode($plates);
