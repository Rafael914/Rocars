<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
if (isset($_POST['update_credentials'])) {
    $userId = $_SESSION['user_id'];
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact_number']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if(!empty($password) && $password !== $confirm_password){
        $_SESSION['toast_error'] = "Passwords do not match!";
        header("Location: ../dashboard.php");
        exit;
    }

    $params = [];
    $types = '';
    $set = [];

    if(!empty($username)) { $set[] = "username=?"; $params[] = $username; $types .= "s"; }
    $set[] = "fullname=?"; $params[] = $fullname; $types .= "s";
    $set[] = "email=?"; $params[] = $email; $types .= "s";
    $set[] = "contact_number=?"; $params[] = $contact; $types .= "s";

    if(!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $set[] = "password=?";
        $params[] = $hashed;
        $types .= "s";
    }

    $params[] = $userId;
    $types .= "i";

    $sql = "UPDATE users SET ".implode(", ", $set)." WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if($stmt->execute()){
        $_SESSION['username'] = $username;
        $_SESSION['fullname'] = $fullname;
        $_SESSION['email'] = $email;
        $_SESSION['contact_number'] = $contact;

        $_SESSION['toast_success'] = "Credentials updated successfully!";
    } else {
        $_SESSION['toast_error'] = "Error updating credentials.";
    }

    $stmt->close();
    $conn->close();

    header("Location: ../index.php");
    exit;
}
?>
