<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$branch_id = $_SESSION['branch_id'] ?? null;
$role      = $_SESSION['role'] ?? 'guest';

// Filters
$search = trim($_GET['search'] ?? '');
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

// -------------------------------
// WHERE CONDITIONS
// -------------------------------
$where = ["s.branch_id = ?"];
$params = [$branch_id];
$types  = "i";

// Search (NO si_number LIKE here)
if (!empty($search)) {
    $where[] = "(m.mechanic_name LIKE ? OR s.customer_name LIKE ? OR s.si_number LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
    $types .= "sss";
}

// Date filter
if (!empty($from) && !empty($to)) {
    $where[] = "DATE(s.date) BETWEEN ? AND ?";
    array_push($params, $from, $to);
    $types .= "ss";
}

$whereSQL = " WHERE " . implode(" AND ", $where);

// -------------------------------
// COUNT QUERY (GROUPED)
// -------------------------------
$countQuery = "
    SELECT COUNT(DISTINCT s.si_number) AS total
    FROM sales s
    LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
    $whereSQL
";

$stmt = $conn->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($total_rows / $limit);

// -------------------------------
// DATA QUERY (GROUP BY SI NUMBER)
// -------------------------------
$dataQuery = "
    SELECT
        s.si_number,
        DATE(MAX(s.date)) AS date,
        m.mechanic_name,
        s.customer_name,
        SUM(s.front_incentive) AS front_incentive,
        SUM(s.skill_incentive) AS skill_incentive,
        SUM(s.front_incentive + s.skill_incentive) AS total_incentive
    FROM sales s
    LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
    $whereSQL
    GROUP BY s.si_number, m.mechanic_name, s.customer_name
    ORDER BY date DESC
    LIMIT ?, ?
";

$dataParams = array_merge($params, [$offset, $limit]);
$dataTypes  = $types . "ii";

$result = false;
$error  = null;

$stmt = $conn->prepare($dataQuery);
$stmt->bind_param($dataTypes, ...$dataParams);

if ($stmt->execute()) {
    $result = $stmt->get_result();
} else {
    $error = $stmt->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Incentives</title>
    <link rel="stylesheet" href="assets/css/mechanics.css">
</head>
<body>

<div class="container">
    <div class="incentives-header">
        <h1>Sales Incentive per day</h1>

        <?php if ($result && $result->num_rows > 0): ?>
            <button
                type="button"
                class="btn-export"
                onclick="window.location.href='actions/export_sales_incentives.php?<?= http_build_query($_GET) ?>'">
                Export
            </button>
        <?php endif; ?>
    </div>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>

<?php elseif ($result && $result->num_rows > 0): ?>

    <table class="incentive-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Mechanic</th>
                <th>SI #</th>
                <th>Customer</th>
                <th>Front Incentive</th>
                <th>Skill Incentive</th>
                <th>Total Incentive</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['mechanic_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['si_number']) ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td>$<?= number_format((float)$row['front_incentive'], 2) ?></td>
                <td>$<?= number_format((float)$row['skill_incentive'], 2) ?></td>
                <td class="total">$<?= number_format((float)$row['total_incentive'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <button class="page-btn" data-page="<?= $page-1 ?>">‹ Prev</button>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <button class="page-btn <?= $i == $page ? 'active' : '' ?>" data-page="<?= $i ?>">
                <?= $i ?>
            </button>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <button class="page-btn" data-page="<?= $page+1 ?>">Next ›</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

<?php else: ?>
    <table class="incentive-table">
        <tr>
            <td colspan="7" style="text-align:center; padding:40px;">
                <img src="images/noIncentiveData.png"
                     alt="No data"
                     style="max-width:20%; height:auto;">
            </td>
        </tr>
    </table>
<?php endif; ?>

</div>
</body>
</html>
