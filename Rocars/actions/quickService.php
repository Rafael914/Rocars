<?php
// actions/quickService.php
require_once '../includes/config.php'; 
require_once '../includes/auth.php';   

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Required fields validation
$required = ['mechanic_id', 'plate_number', 'customer_name', 'vehicle'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['status' => 'error', 'message' => "Field '$field' is required"]);
        exit;
    }
}

// 1. Sanitize and Prepare Inputs
$si_number       = trim($_POST['si_number'] ?? 'N/A');
$mechanic_id     = intval($_POST['mechanic_id']);
$category        = 'services'; // Hardcoded
$quantity        = 1;          // Hardcoded
$item_name       = 'Service';  // Hardcoded
$customer_name   = trim($_POST['customer_name']);
$vehicle         = trim($_POST['vehicle']);
$plate_no        = trim($_POST['plate_number']);
$odometer        = trim($_POST['odometer'] ?? '');
$cp_number       = trim($_POST['cp_number'] ?? '');
$total_amount    = floatval($_POST['total_amount'] ?? 0);
$total_cost      = 0; 
$gross_profit    = $total_amount - $total_cost;
$remarks         = trim($_POST['remarks'] ?? '');
$discrepancy     = trim($_POST['discrepancy'] ?? '');
$front_incentive = floatval($_POST['front_incentive'] ?? 0);
$skill_incentive = floatval($_POST['skill_incentive'] ?? 0);
$branch_id       = $_SESSION['branch_id'] ?? 1;
$payment_method  = trim($_POST['payment_method'] ?? 'Cash');
$description     = trim($_POST['description'] ?? '');

// 2. Prepare Statement
// Counted: 21 columns in list, 21 values (20 '?' and 1 'NOW()')
$sql = "INSERT INTO sales 
        (si_number, date, mechanic_id, category, quantity, item_name, customer_name, vehicle, plate_no, odometer, cp_number, total_amount, total_cost, gross_profit, remarks, discrepancy, front_incentive, skill_incentive, branch_id, payment_method, description) 
        VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

// 3. Bind Parameters
// The types string must be exactly 20 characters long to match the 20 '?' placeholders
// s = string, i = integer, d = double/float
$types = "sisississsdddssddiss";

$stmt->bind_param(
    $types,
    $si_number,      
    $mechanic_id,    
    $description,        // 3. s
    $quantity,        // 4. i
    $item_name,       // 5. s
    $customer_name,   // 6. s
    $vehicle,         // 7. s
    $plate_no,        // 8. s
    $odometer,        // 9. s
    $cp_number,       // 10. s
    $total_amount,    // 11. d
    $total_cost,      // 12. d
    $gross_profit,    // 13. d
    $remarks,         // 14. s
    $discrepancy,     // 15. s
    $front_incentive, // 16. d
    $skill_incentive, // 17. d
    $branch_id,       // 18. i
    $payment_method,  // 19. s
    $description      // 20. s
);

// 4. Execute and Response
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Service record saved successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>