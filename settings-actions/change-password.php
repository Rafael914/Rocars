<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "<p style='color:red;'>All fields are required.</p>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<p style='color:red;'>New passwords do not match.</p>";
    } else {

        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $message = "<p style='color:red;'>User not found.</p>";
        } else {
            $stmt->bind_result($hashed_password);
            $stmt->fetch();

            if (!password_verify($current_password, $hashed_password)) {
                $message = "<p style='color:red;'>Current password is incorrect.</p>";
            } else {
                $new_hashed = password_hash($new_password, PASSWORD_DEFAULT); // Update password

                $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update->bind_param("si", $new_hashed, $user_id);

                if ($update->execute()) {
                    $message = "<p style='color:green;'>Password updated successfully.</p>";
                } else {
                    $message = "<p style='color:red;'>Failed to update password.</p>";
                }

                $update->close();
            }
        }

        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
</head>
<body>

<div class="container">
    <h2>Change Password for <?php echo htmlspecialchars($username); ?></h2>
    <?php echo $message; ?>
    <form method="POST">
        <label>Current Password:</label>
        <input type="password" name="current_password" required><br>

        <label>New Password:</label>
        <input type="password" name="new_password" required><br>

        <label>Confirm New Password:</label>
        <input type="password" name="confirm_password" required><br>

        <button type="submit">Update Password</button>
    </form>
</div>

</body>
</html>
