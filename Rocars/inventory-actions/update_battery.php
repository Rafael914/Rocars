<?php
// 1. Error Handling Configuration
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
// Ensure this path is writable by the web server (e.g., www-data)
ini_set('error_log', '../logs/php_errors.log'); 

ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 2. Load dependencies
    // If these files don't exist, the 'catch' block will handle the error
    if (!file_exists('../includes/config.php')) {
        throw new Exception("Configuration file missing.");
    }
    require_once '../includes/config.php';
    require_once '../includes/auditLog.php';

    /**
     * ✅ AJAX-safe authentication check
     */
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access'
        ]);
        exit;
    }

    // ==============================
    // SANITIZE & DEFAULT VALUES
    // ==============================
    $si_number       = $_POST['si_number'] ?? 'N/A';
    $mech_id         = isset($_POST['mechanic_id']) && $_POST['mechanic_id'] !== '' ? (int)$_POST['mechanic_id'] : null;
    $category        = $_POST['cat_name'] ?? '';
    $quantity        = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $item_name       = $_POST['item_name'] ?? 'Service';
    $customer_name   = $_POST['customer_name'] ?? '';
    $vehicle         = $_POST['vehicle'] ?? '';
    $plate_no        = $_POST['plate_number'] ?? '';
    $odometer        = $_POST['odometer'] ?? '';
    $cp_number       = $_POST['cp_number'] ?? '';
    $total_amount    = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0.00;
    $total_cost      = isset($_POST['total_cost']) ? (float)$_POST['total_cost'] : 0.00;
    $gross_profit    = $total_amount - $total_cost;
    $remarks         = $_POST['remarks'] ?? '';
    $discrepancy     = $_POST['discrepancy'] ?? '';
    $front_incentive = isset($_POST['front_incentive']) ? (float)$_POST['front_incentive'] : 0.00;
    $skill_incentive = isset($_POST['skill_incentive']) ? (float)$_POST['skill_incentive'] : 0.00;
    $branch_id       = $_SESSION['branch_id'] ?? 0;
    $payment_method  = $_POST['payment_method'] ?? 'Cash';
    $description     = $_POST['description'] ?? '';

    // ==============================
    // INSERT QUERY
    // ==============================
    $sql = "
        INSERT INTO sales (
            si_number, date, mechanic_id, category, quantity, item_name,
            customer_name, vehicle, plate_no, odometer, cp_number,
            total_amount, total_cost, gross_profit,
            remarks, discrepancy, front_incentive, skill_incentive,
            branch_id, payment_method, description
        )
        VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sisississsdddssddiss",
        $si_number, $mech_id, $category, $quantity, $item_name,
        $customer_name, $vehicle, $plate_no, $odometer, $cp_number,
        $total_amount, $total_cost, $gross_profit,
        $remarks, $discrepancy, $front_incentive, $skill_incentive,
        $branch_id, $payment_method, $description
    );

    $stmt->execute();
    $stmt->close();


    if (ob_get_length()) ob_clean();

    echo json_encode([
        'success' => true,
        'message' => 'Service Entry Successfully Added'
    ]);

} catch (Throwable $e) {

    error_log("Process Error: " . $e->getMessage());


    if (ob_get_length()) ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred. Please check the logs.',
        'error'   => $e->getMessage()
    ]);
}
ob_end_flush();