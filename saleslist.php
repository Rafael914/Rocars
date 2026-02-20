<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Helper for call_user_func_array
function refValues($arr) { 
    $refs = []; 
    foreach($arr as $key => $value) $refs[$key] = &$arr[$key]; 
    return $refs; 
}

// PAGINATION
$recordPerPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $recordPerPage;

// FILTERS
$search = $_GET['search'] ?? "";
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$mechanicId = $_GET['mechanic_id'] ?? '';
$branchId = $_SESSION['branch_id'] ?? '';

// Build WHERE clauses
$whereClauses = [];
$params = [];
$types = '';

if ($search != '') {
    $whereClauses[] = "(s.si_number LIKE ? OR s.customer_name LIKE ? OR s.vehicle LIKE ? OR s.plate_no LIKE ? OR s.odometer LIKE ? OR s.cp_number LIKE ? OR m.mechanic_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, array_fill(0, 7, $searchTerm));
    $types .= str_repeat("s", 7);
}

if (!empty($fromDate)) {
    $whereClauses[] = "s.date >= ?";
    $params[] = $fromDate;
    $types .= "s";
}

if (!empty($toDate)) {
    $whereClauses[] = "s.date <= ?";
    $params[] = $toDate;
    $types .= "s";
}

if (!empty($mechanicId)) {
    $whereClauses[] = "s.mechanic_id = ?";
    $params[] = $mechanicId;
    $types .= "i";
}

if (!empty($branchId)) {
    $whereClauses[] = "s.branch_id = ?";
    $params[] = $branchId;
    $types .= "i";
}

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// 1️⃣ TOTAL ROWS
$stmtTotal = $conn->prepare("
    SELECT COUNT(DISTINCT s.si_number)
    FROM sales s
    LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
    $whereSQL
");

if (!empty($params)) {
    call_user_func_array([$stmtTotal, 'bind_param'], refValues(array_merge([$types], $params)));
}

$stmtTotal->execute();
$stmtTotal->bind_result($totalRows);
$stmtTotal->fetch();
$stmtTotal->close();

$totalPages = ceil($totalRows / $recordPerPage);

// 2️⃣ MAIN QUERY - GROUP PRODUCTS PER SI_NUMBER
$sql = "
SELECT 
    s.si_number,
    s.date,
    s.mechanic_id,
    m.mechanic_name,
    s.customer_name,
    s.vehicle,
    s.plate_no,
    s.odometer,
    s.cp_number,
    GROUP_CONCAT(s.item_name SEPARATOR ', ') AS products,
    SUM(s.total_amount) AS total_amount,
    SUM(s.total_cost) AS total_cost,
    SUM(s.total_amount - s.total_cost) AS gross_profit,
    MAX(s.remarks) AS remarks,
    MAX(s.discrepancy) AS discrepancy,
    MAX(s.description) AS description,
    MAX(s.front_incentive) AS front_incentive,
    MAX(s.skill_incentive) AS skill_incentive,
    MAX(s.sales_id) AS sales_id
FROM sales s
LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
$whereSQL
GROUP BY s.si_number
ORDER BY s.date DESC
LIMIT $offset, $recordPerPage
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$types], $params)));
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales List</title>
<link rel="stylesheet" href="assets/css/saleslist.css">
</head>
<body>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>SI Number</th>
            <th>Date</th>
            <th>Mechanic</th>
            <th>Customer</th>
            <th>Vehicle</th>
            <th>Plate No</th>
            <th>Odometer</th>
            <th>Phone Number</th>
            <th>Products</th>
            <th>Total Amount</th>
            <th>Total Cost</th>
            <th>Gross Profit</th>
            <th>Remarks</th>
            <th>Discrepancy</th>
            <th>Description</th>
            <th>Front Incentive</th>
            <th>Skill Incentive</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['si_number']) ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['mechanic_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= htmlspecialchars($row['vehicle']) ?></td>
                    <td><?= htmlspecialchars($row['plate_no']) ?></td>
                    <td><?= htmlspecialchars($row['odometer']) ?></td>
                    <td><?= htmlspecialchars($row['cp_number']) ?></td>
                    <td><?= htmlspecialchars($row['products']) ?></td>
                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                    <td>₱<?= number_format($row['total_cost'], 2) ?></td>
                    <td>₱<?= number_format($row['gross_profit'], 2) ?></td>
                    <td><?= htmlspecialchars($row['remarks'] ?? "") ?></td>
                    <td><?= htmlspecialchars($row['discrepancy'] ?? "") ?></td>
                    <td><?= htmlspecialchars($row['description'] ?? "") ?></td>
                    <td>₱<?= number_format($row['front_incentive'], 2) ?></td>
                    <td>₱<?= number_format($row['skill_incentive'], 2) ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-receipt receiptBtn" data-si-number="<?= htmlspecialchars($row['si_number']) ?>">Receipt</button>
                    <a class="btn btn-delete" 
                    href="delete_sales.php?si_number=<?= urlencode($row['si_number']) ?>" 
                    onclick="return confirm('Are you sure you want to delete all items for SI #<?= $row['si_number'] ?>?');">
                    Delete
                    </a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
            <td colspan="18" style="text-align:center; padding:40px;">
                <img 
                    src="images/noRecords.png" 
                    alt="No Records found"
                    style="max-width:40%; height:auto; max-height:300px; display:block; margin:0 auto;"
                >
            </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <button class="page-btn" data-page="<?= $page - 1 ?>">‹ Prev</button>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <button class="page-btn <?= $i == $page ? 'active' : '' ?>" data-page="<?= $i ?>">
            <?= $i ?>
        </button>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
        <button class="page-btn" data-page="<?= $page + 1 ?>">Next ›</button>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
document.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('btn-delete')){
        const salesId = e.target.dataset.salesId;
        if(confirm(`Are you sure you want to delete sale #${salesId}?`)){
            // Redirect to PHP delete page
            window.location.href = `delete_sales.php?sales_id=${salesId}`;
        }
    }
});
</script>

</body>
</html>
