<?php
require_once '../includes/config.php';
require_once '../includes/auth.php'; // Required to access $_SESSION['branch_id']

// Set header for JSON response
header('Content-Type: application/json');

// Get database connection object
global $conn;

// Helper function to pass parameters by reference (required for mysqli_stmt::bind_param using the splat operator)
function refValues($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

// Input sanitation
$search = trim($_GET['search'] ?? '');
$fromDate = trim($_GET['from'] ?? '');
$toDate = trim($_GET['to'] ?? '');

// Get mandatory filter from session
$branch_id = $_SESSION['branch_id'] ?? null; 

// Initialize query components
$whereClauses = [];
$params = [];
$types = '';

// --- Build WHERE clauses based on filters ---

// 1. MANDATORY: Filter by Branch ID
if (!empty($branch_id)) {
    $whereClauses[] = "s.branch_id = ?";
    $params[] = $branch_id;
    $types .= "i"; // Assuming branch_id is integer
} else {
    // If branch ID is missing, prevent data loading and return an error
    echo json_encode(['status' => 'error', 'total' => 0, 'message' => 'Branch ID not found in session. Authentication issue?']);
    exit;
}

// 2. Date Filter
if (!empty($fromDate) && !empty($toDate)) {
    $whereClauses[] = "s.date BETWEEN ? AND ?";
    $params[] = $fromDate;
    $params[] = $toDate;
    $types .= "ss";
} elseif (!empty($fromDate)) {
    $whereClauses[] = "s.date >= ?";
    $params[] = $fromDate;
    $types .= "s";
} elseif (!empty($toDate)) {
    // If only 'To' date is set, filter up to that date
    $whereClauses[] = "s.date <= ?";
    $params[] = $toDate;
    $types .= "s";
}


// 3. Search Filter (Targets mechanic_name, si_number, and customer_name)
if (!empty($search)) {
    $searchString = "%$search%";
    $whereClauses[] = "(\r\n"
        . "m.mechanic_name LIKE ?\r\n"      // Mechanic Name (from mechanics table)
        . "OR s.si_number LIKE ?\r\n"      // SI Number (from sales table)
        . "OR s.customer_name LIKE ?\r\n"  // Customer Name (from sales table)
        . ")\r\n";
    
    // Add parameters for each placeholder
    $params[] = $searchString;
    $params[] = $searchString;
    $params[] = $searchString;
    $types .= "sss";
}

// Construct the full WHERE clause
$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// --- Total Incentives Calculation Query ---
$query = "
    SELECT 
        -- Sum the two incentive columns as confirmed by your mechanics table script
        SUM(s.front_incentive + s.skill_incentive) AS total_incentives
    FROM sales s
    -- Join is necessary for the mechanic_name search filter
    LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
    {$whereSql}
";

$total = 0;
$status = 'success';
$msg = '';

try {
    if (!$conn) {
        throw new \Exception("Database connection not available.");
    }
    
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new \Exception('Prepare failed. Check database tables (sales, mechanics) and column names: ' . $conn->error);
    }

    // Bind parameters
    if (!empty($types)) {
        call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$types], $params)));
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();


    $total = $row['total_incentives'] ?? 0;

} catch (\Throwable $e) { 
    $status = 'error';
    // Provide a detailed error message listing expected tables/columns for debugging
    $msg = 'Failed to fetch summary. Database Error: ' . $e->getMessage() . 
           '. Expected tables/columns: (sales: branch_id, date, front_incentive, skill_incentive, mechanic_id, si_number, customer_name) AND (mechanics: mechanic_id, mechanic_name).';
    $total = 0;
}


// Return the total as JSON
echo json_encode(['status' => $status, 'total' => $total, 'message' => $msg]);

?>