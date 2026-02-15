<?php
require '../includes/config.php'; 
require '../includes/auth.php'; 


$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $branch_id = $_POST['branch_id'] ?? '';
    $mechanic_name = $_POST['mechanic_name'] ?? '';
    $contact_number = $_POST['contact_number'] ?? null;
    $email = $_POST['email'] ?? null;

    if (empty($branch_id) || empty($mechanic_name)) {
        $response['message'] = 'Required fields missing.';
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO mechanics 
        (branch_id, mechanic_name, contact_number, email, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param(
        "isss",
        $branch_id,
        $mechanic_name,
        $contact_number,
        $email
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Technician added successfully.';
    } else {
        $response['message'] = 'Database error.';
    }

    $stmt->close();
}

echo json_encode($response);
