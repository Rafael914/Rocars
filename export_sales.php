<?php
require_once 'includes/config.php';
require_once 'includes/auditLog.php';

// --- 1. Retrieve Filters ---
$search = $_GET['search'] ?? '';
$from   = $_GET['from_date'] ?? '';
$to     = $_GET['to_date'] ?? '';
$mech   = $_GET['mechanic_id'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;


$sql = "
    SELECT 
        s.sales_id, s.si_number, s.date, 
        m.mechanic_name, 
        s.customer_name, s.vehicle, s.plate_no, 
        s.odometer, s.cp_number, 
        s.total_amount, s.total_cost, s.gross_profit, 
        s.remarks, s.discrepancy, 
        s.front_incentive, s.skill_incentive
    FROM sales s
    LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
    WHERE 1=1
";

$params = [];
$types  = "";


if (!empty($search)) {
    $sql .= " AND (s.si_number LIKE ? OR s.customer_name LIKE ? OR s.vehicle LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}


if (!empty($from)) {
    $sql .= " AND s.date >= ?";
    $params[] = $from;
    $types .= "s";
}
if (!empty($to)) {
    $sql .= " AND s.date <= ?";
    $params[] = $to;
    $types .= "s";
}


if (!empty($mech)) {
    $sql .= " AND s.mechanic_id = ?";
    $params[] = $mech;
    $types .= "i";
}


$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=sales_export_" . date('Y-m-d') . ".csv");
header("Pragma: no-cache");
header("Expires: 0");



echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');


$headers = [
    'ID', 'SI Number', 'Date', 'Mechanic', 'Customer', 'Vehicle',
    'Plate No', 'Odometer', 'Phone', 'Total Amount', 'Total Cost',
    'Gross Profit', 'Remarks', 'Discrepancy', 'Front Incentive', 'Skill Incentive'
];
fputcsv($output, $headers);


while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['sales_id'],
        $row['si_number'],
        $row['date'],
        $row['mechanic_name'],
        $row['customer_name'],
        $row['vehicle'],
        $row['plate_no'],
        $row['odometer'],
        $row['cp_number'],
        $row['total_amount'],
        $row['total_cost'],
        $row['gross_profit'],
        $row['remarks'],
        $row['discrepancy'],
        $row['front_incentive'],
        $row['skill_incentive']
    ]);
}

fclose($output);

        auditLog(
            $conn,
            $user_id,
            'EXPORT',
            'users',
            $user_id,
            $username . ' was export the Sales Table'
        );

exit;
?>
