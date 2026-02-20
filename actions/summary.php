<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$branch_id = (int)$_SESSION['branch_id'];

$search = $_GET['search'] ?? "";
$from   = $_GET['from_date'] ?? "";
$to     = $_GET['to_date'] ?? "";
$year   = $_GET['year'] ?? date("Y"); // default = current year

$params = [];
$types  = "";
$where  = "WHERE branch_id = ? AND YEAR(date) = ?";

$params[] = $branch_id;
$params[] = $year;
$types   .= "ii";

if (!empty($search)) {
    $where .= " AND (details LIKE ? OR category LIKE ? OR remarks LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if (!empty($from)) {
    $where .= " AND date >= ?";
    $params[] = $from;
    $types .= "s";
}

if (!empty($to)) {
    $where .= " AND date <= ?";
    $params[] = $to;
    $types .= "s";
}

$sql = "
    SELECT 
        COUNT(*) AS count,
        IFNULL(SUM(amount), 0) AS total,
        IFNULL(AVG(amount), 0) AS avg
    FROM expenses
    $where
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "year"  => (int)$year,
    "total" => (float)$result['total'],
    "count" => (int)$result['count'],
    "avg"   => (float)$result['avg']
]);
