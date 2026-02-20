<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['mechanic_id'];
    $name = $_POST['mechanic_name'];
    $contact = $_POST['contact_number'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE mechanics SET mechanic_name = ?, contact_number = ?, email = ? WHERE mechanic_id = ?");
    $stmt->bind_param("sssi", $name, $contact, $email, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Technician updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
    }
    $stmt->close();
}