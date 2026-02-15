<?php
// ==========================
// update_user.php
// ==========================

// 1️⃣ Start session at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2️⃣ Log errors instead of displaying
error_reporting(E_ALL);
ini_set('display_errors', 0);          // Do not output errors to browser
ini_set('log_errors', 1);              // Log errors
ini_set('error_log', __DIR__ . '/php-error.log'); // Error log path

// 3️⃣ Include config and auth
require '../includes/config.php';
require '../includes/auth.php'; 

// 4️⃣ Set JSON header
header('Content-Type: application/json');

// 5️⃣ Initialize response
$response = ['success' => false, 'message' => ''];

try {
    // 6️⃣ Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // 7️⃣ Validate user_id
    if (empty($_POST['user_id'])) {
        throw new Exception('User ID is missing.');
    }

    // 8️⃣ Get POST data safely
    $user_id   = (int)$_POST['user_id'];
    $username  = trim($_POST['username'] ?? '');
    $fullname  = trim($_POST['fullname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $contact   = trim($_POST['contact_number'] ?? '');
    $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : NULL;
    $role      = trim($_POST['role'] ?? '');

    // Optional: Validate required fields
    if (!$username || !$fullname || !$email || !$role) {
        throw new Exception('Please fill in all required fields.');
    }

    // 9️⃣ Prepare SQL statement
    $stmt = $conn->prepare("
        UPDATE users SET
            username = ?,
            fullname = ?,
            email = ?,
            contact_number = ?,
            branch_id = ?,
            role = ?
        WHERE user_id = ?
    ");

    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    // 10️⃣ Bind parameters
    $stmt->bind_param(
        "ssssisi",
        $username,
        $fullname,
        $email,
        $contact,
        $branch_id,
        $role,
        $user_id
    );

    // 11️⃣ Execute query
    if (!$stmt->execute()) {
        throw new Exception('Database execute failed: ' . $stmt->error);
    }

    $stmt->close();

    // 12️⃣ Set success response
    $response['success'] = true;
    $response['message'] = 'User updated successfully.';

    // 13️⃣ Optional: Audit log
    if (function_exists('auditLog')) {
        $current_user_id = $_SESSION['user_id'] ?? 0;
        $action = 'UPDATE';
        $table_name = 'users';
        $record_id = $user_id;
        $description = "Updated user: $username ($fullname)";
        auditLog($conn, $current_user_id, $action, $table_name, $record_id, $description);
    }

} catch (Exception $e) {
    // 14️⃣ Catch errors and return JSON
    $response['message'] = $e->getMessage();
}

// 15️⃣ Return JSON and exit
echo json_encode($response);
exit;
