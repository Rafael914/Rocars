<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$branchId = $_SESSION['branch_id'] ?? null;
$search = $_GET['search'] ?? '';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';

$params = [];
$types = "";

// Base query
$where = "WHERE m.branch_id = ?";
$params[] = $branchId;
$types .= "i";

// Date filter
if ($from && $to) {
    $where .= " AND s.date BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
    $types .= "ss";
} else {
    // Default to current month
    $where .= " AND MONTH(s.date) = ? AND YEAR(s.date) = ?";
    $params[] = date('m');
    $params[] = date('Y');
    $types .= "ss";
}

// Search filter
if ($search) {
    $where .= " AND (s.si_number LIKE ? OR s.customer_name LIKE ? OR s.item_name LIKE ?)";
    $s = "%$search%";
    $params[] = $s;
    $params[] = $s;
    $params[] = $s;
    $types .= "sss";
}

// Sum query with join to mechanics
$sql = "SELECT SUM(IFNULL(s.front_incentive,0)+IFNULL(s.skill_incentive,0)) AS total
        FROM sales s
        LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
        $where";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total = (float)($row['total'] ?? 0);

echo json_encode(['total' => $total]);
