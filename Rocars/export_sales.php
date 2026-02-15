<?php
/**
 * sales_export.php
 * Exports filtered sales data to a UTF-8 encoded CSV file.
 * Requires: includes/config.php (mysqli connection: $conn)
 */

require_once 'includes/config.php';

// --- 1. Retrieve Filters ---
$search = $_GET['search'] ?? '';
$from   = $_GET['from_date'] ?? '';
$to     = $_GET['to_date'] ?? '';
$mech   = $_GET['mechanic_id'] ?? '';

// --- 2. Build Dynamic SQL Query ---
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

// Search filter
if (!empty($search)) {
    $sql .= " AND (s.si_number LIKE ? OR s.customer_name LIKE ? OR s.vehicle LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}

// Date filters
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

// Mechanic filter
if (!empty($mech)) {
    $sql .= " AND s.mechanic_id = ?";
    $params[] = $mech;
    $types .= "i";
}

// --- 3. Execute Query ---
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// --- 4. Prepare CSV Output ---
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=sales_export_" . date('Y-m-d') . ".csv");
header("Pragma: no-cache");
header("Expires: 0");


// Add UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// --- 5. Write Header Row ---
$headers = [
    'ID', 'SI Number', 'Date', 'Mechanic', 'Customer', 'Vehicle',
    'Plate No', 'Odometer', 'Phone', 'Total Amount', 'Total Cost',
    'Gross Profit', 'Remarks', 'Discrepancy', 'Front Incentive', 'Skill Incentive'
];
fputcsv($output, $headers);

// --- 6. Write Data Rows ---
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
exit;
?>
