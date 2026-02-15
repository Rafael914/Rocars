<?php
ob_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

// 3. Set JSON header
header('Content-Type: application/json');

try {
    require_once '../includes/config.php';

    // Validate Input
    $mechanic_id = filter_input(INPUT_POST, 'mechanic_id', FILTER_VALIDATE_INT);

    if (!$mechanic_id) {
        throw new Exception('Invalid or missing Technician ID');
    }

    // Database Logic
    $stmt = $conn->prepare("DELETE FROM mechanics WHERE mechanic_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $mechanic_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    if ($stmt->affected_rows > 0) {
        $response = ['success' => true, 'message' => 'Technician deleted permanently'];
    } else {
        $response = ['success' => false, 'message' => 'Technician not found or already deleted'];
    }

    $stmt->close();

} catch (Exception $e) {
    // Catch any error and put it into the JSON message
    $response = [
        'success' => false, 
        'message' => 'System error: ' . $e->getMessage()
    ];
}

// 4. Wipe out any accidental HTML/Warnings/Spaces captured in the buffer
ob_clean();

// 5. Send only the clean JSON
echo json_encode($response);
exit;