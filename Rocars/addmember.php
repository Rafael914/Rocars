<?php
    require_once "includes/config.php";


    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $fullname = sanitizeInput($_POST['fullname']);
        $username = sanitizeInput($_POST['username']);
        $contact_number = sanitizeInput($_POST['contact_number']);
        $role = sanitizeInput($_POST['role']);
        $email = sanitizeInput($_POST['email']);
        $branch = sanitizeInput($_POST['branch']);
        $password = sanitizeInput($_POST['password']);
        $hashedpassword = password_hash($password, PASSWORD_DEFAULT);
        
        $checksql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $checkstmt = $conn->prepare($checksql);
        $checkstmt->bind_param("ss", $username, $email);
        $checkstmt->execute();
        $result = $checkstmt->get_result();

        if($result -> num_rows > 0){
            setFlash("error", "Username or email already exists.");
        }else{
            $stmt = $conn->prepare("INSERT INTO users (fullname, username, password, role, contact_number, Email, branch_id)
                                    VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssi",$fullname, $username, $hashedpassword, $role, $contact_number, $email, $branch);
            if($stmt->execute()){
                $_SESSION['result'] = "Member Successfully Added";
            }else{
                $_SESSION['error'] = "failed to add member: " . $conn->error;
            }

        }

        header("Location: " . $_SERVER["PHP_SELF"]);
        exit();

    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method = "POST">
        <label for="fullnane">fullname</label>
        <input type="text" name="fullname"><br>

        <label for="username">Username</label>
        <input type="text" name = "username"><br>

        <label for="contact_number">Contact_number</label>
        <input type="text" name= "contact_number"><br>

        <label for="email">Email</label>
        <input type="email" name="email"><br>

    <label for="role">role</label>
    
    <select name="role" id="role" required>
    <option value="" disabled <?= empty($_POST['role']) ? 'selected' : '' ?>>Select role</option>
    <option value="cashier" <?= (isset($_POST['role']) && $_POST['role'] == 'cashier') ? 'selected' : '' ?>>Cashier</option>
    <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
    <option value="inventory staff" <?= (isset($_POST['role']) && $_POST['role'] == 'inventory staff') ? 'selected' : '' ?>>Inventory Staff</option>
    <option value="Master Admin" <?= (isset($_POST['role']) && $_POST['role'] == 'Master Admin') ? 'selected' : '' ?>>Master Admin</option>
    </select>   <br>

        <label for="branch_id">Branch</label>
    <select name="branch" id="branch_id" required>
        <option value="" disabled selected>Select branch</option>
        <?php
        $branchQuery = $conn->query("SELECT branch_id, branch_name FROM branches");
        while ($branch = $branchQuery->fetch_assoc()):
        ?>
            <option value="<?= $branch['branch_id']; ?>">
                <?= htmlspecialchars($branch['branch_name']); ?>
            </option>
        <?php endwhile; ?>

        
    </select><br>

        <label for="password">Password</label>
        <input type="password" name= "password"><br>

       <button type="submit">Submit</button>

       <?php if(isset($_SESSION['result'])): ?>
            <div class="result-msg">
                <?php echo $_SESSION['result'];
                     unset($_SESSION['result']);
                ?>
            </div>

        <?php elseif(isset($_SESSION['error'])): ?>
            <div class="result-msg">
                <?php echo $_SESSION['error'];
                    unset($_SESSION['error']) ?>
            </div>

       <?php endif;?>

    </form>
</body>
</html>