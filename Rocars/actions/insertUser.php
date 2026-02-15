<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get POST data
$username   = $_POST['username'] ?? '';
$password   = $_POST['password'] ?? '';
$role       = $_POST['role'] ?? '';
$contact    = $_POST['contact_number'] ?? null;
$branch_id  = $_POST['branch_id'] ?? null;
$email      = $_POST['email'] ?? null;
$fullname   = $_POST['fullname'] ?? null;

// Validate required fields
if (empty($username) || empty($password) || empty($role)) {
    $response['message'] = 'Required fields are missing';
    echo json_encode($response);
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert query
$sql = "INSERT INTO users 
        (username, password, role, contact_number, branch_id, email, fullname)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $response['message'] = 'Prepare failed';
    echo json_encode($response);
    exit;
}

$stmt->bind_param(
    "ssssiss",
    $username,
    $hashedPassword,
    $role,
    $contact,
    $branch_id,
    $email,
    $fullname
);

// Execute
if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'User added successfully';

    // echo json_encode(['success' => true, 'message' => 'User created successfully']); other approach
} else {
    $response['message'] = 'Insert failed';
}


echo json_encode($response);

$stmt->close();
$conn->close();
exit;