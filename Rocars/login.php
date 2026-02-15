<?php
require_once "includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid request");
    }

    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password']; 

    $stmt = $conn->prepare("
        SELECT u.user_id, u.username, u.role, u.password, u.branch_id, b.branch_name, u.archived_at
        FROM users u
        JOIN branches b ON u.branch_id = b.branch_id
        WHERE u.username = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Check if user is archived
        if (!is_null($row['archived_at']) && $row['archived_at'] != 0) {
            $_SESSION['invalid'] = "This account is archived and cannot login.";
            header("Location: " . $_SERVER["PHP_SELF"]);
            exit();
        }

        // Check password
        if (password_verify($password, $row['password'])) {

            session_regenerate_id(true);

            $_SESSION['branch_name'] = $row['branch_name'];
            $_SESSION['branch_id']   = $row['branch_id'];
            $_SESSION['user_id']     = $row['user_id'];
            $_SESSION['username']    = $row['username'];
            $_SESSION['role']        = $row['role'];

            unset($_SESSION['invalid']);

            header("Location: index.php");
            exit();
        }
    }

    // Invalid login
    $_SESSION['invalid'] = "Invalid username or password";
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rocars Login</title>

    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="login-container">

    <div class="logo">
        <img src="images/rocarsn.png" alt="Rocars Logo">
    </div>

    <div class="login-box">
        <h2>LOGIN</h2>

        <?php if (!empty($_SESSION['invalid'])): ?>
            <p style="color:red; text-align:center;">
                <?= htmlspecialchars($_SESSION['invalid']) ?>
            </p>
            <?php unset($_SESSION['invalid']); ?>
        <?php endif; ?>

        <form id="loginForm" method="POST" autocomplete="off">

            <!-- CSRF TOKEN -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text" placeholder="Username" name="username" required>
            </div>

            <div class="input-group password-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" placeholder="Password" name="password" id="password" required>
                <i class="fa-solid fa-eye" id="togglePassword"></i>
            </div>

        </form>

        <button class="login-btn" type="submit" form="loginForm">LOGIN</button>
    </div>
</div>

<script>
const togglePassword = document.getElementById('togglePassword');
const password = document.getElementById('password');

togglePassword.addEventListener('click', function () {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.classList.toggle('fa-eye');
    this.classList.toggle('fa-eye-slash');
});
</script>

</body>
</html>
