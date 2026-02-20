<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';  // To get $_SESSION['user_id']
require_once 'includes/auditLog.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['si_number']) && !empty($_GET['si_number'])) {
    $si_number = $_GET['si_number'];
    $actor_id  = $_SESSION['user_id'] ?? 0;

    // 1️⃣ Check if the sale exists and get details (optional, useful for audit)
    $checkStmt = $conn->prepare("SELECT si_number FROM sales WHERE si_number = ?");
    $checkStmt->bind_param("s", $si_number);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $sale = $result->fetch_assoc();
    $checkStmt->close();

    if (!$sale) {
        $_SESSION['error'] = "Invoice #$si_number not found.";
        header("Location: saleslistIndex.php");
        exit;
    }

    // 2️⃣ Delete the sale
    $sql = "DELETE FROM sales WHERE si_number = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $si_number);

        if ($stmt->execute()) {
            // ✅ Audit log
            auditLog(
                $conn,
                $actor_id,
                'DELETE',
                'sales',
                $si_number,
                "Sale deleted: Invoice #$si_number"
            );

            $_SESSION['message'] = "Invoice #$si_number and all associated items deleted successfully.";
            header("Location: saleslistIndex.php");
            exit;

        } else {
            $_SESSION['error'] = "Error deleting record: " . $stmt->error;
            header("Location: saleslistIndex.php");
            exit;
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare delete statement: " . $conn->error;
        header("Location: saleslistIndex.php");
        exit;
    }

} else {
    $_SESSION['error'] = "Invalid SI Number.";
    header("Location: saleslistIndex.php");
    exit;
}

$conn->close();
?>
