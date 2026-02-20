<?php
// actions/fetchSale.php

// 1. Check your path! If fetchSale.php is inside "actions" folder, 
// and config is in "includes", you need to go up one level.
require_once '../includes/config.php'; 

$si_number = $_GET['si_number'] ?? '';

header('Content-Type: application/json');

if (empty($si_number)) {
    echo json_encode([]);
    exit;
}

// 2. Wrap in try-catch to see if SQL is failing
try {
    $stmt = $conn->prepare("SELECT * FROM sales WHERE si_number = ?");
    $stmt->bind_param("s", $si_number);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    echo json_encode($items);
} catch (Exception $e) {
    // This will send the error message back to JS so you can see it in Console
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
exit;