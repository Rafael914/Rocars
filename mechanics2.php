<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$branch_id = $_SESSION['branch_id'] ?? null;

$search = trim($_GET['search'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;


$baseQuery = "SELECT 
                s.mechanic_id,
                m.mechanic_name,
                YEAR(s.date) AS year,
                MONTHNAME(s.date) AS month,
                SUM(s.skill_incentive) AS skillIncentive,
                SUM(s.front_incentive) AS frontIncentive,
                SUM(s.front_incentive + s.skill_incentive) AS totalIncentive
                FROM sales s
                LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id";
                
$where_clauses = ["s.branch_id = ?"];
$params = [$branch_id];
$types = "i";

if(!empty($search)){
    $where_clauses[] = "(m.mechanic_name LIKE ? OR s.front_incentive LIKE ? OR s.skill_incentive LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if(!empty($from) && !empty($to)){
    $where_clauses[] = "s.date BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
    $types .= "ss";
}

if(!empty($where_clauses)){
    $baseQuery .= " WHERE " . implode(" AND ", $where_clauses);
   
}

$baseQuery .= " GROUP BY s.mechanic_id, YEAR(s.date), MONTH(s.date)";
$baseQuery .= " ORDER BY s.mechanic_id, YEAR(s.date), MONTH(s.date)";

$countquery = "SELECT COUNT(*) as total FROM ($baseQuery) as bsq";
$stmt = $conn->prepare($countquery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($total_rows / $limit);

$baseQuery .= " LIMIT $offset, $limit";


    $result = false;
    $error = null;

if ($stmt = $conn->prepare($baseQuery)) {
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
    } else {
        $error = "Execution failed: " . $stmt->error;
    }
    $stmt->close();
} else {
    $error = "Preparation failed: " . $conn->error;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="assets/css/mechanics.css">
</head>
<body>

    

<div class="container">


    <?php if($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    
    <?php elseif($result && $result->num_rows > 0): ?>
            <div class="incentives-header">
    <h1>Total Incentives Per Month</h1>
        <?php if ($result && $result->num_rows > 0): ?>
        <button 
    type="button" 
    class="btn-export"
    onclick="window.location.href='actions/export_incentives.php?<?= http_build_query($_GET) ?>'">
    Export
</button>
        <?php endif; ?>
    </div>
        <table class="incentive-table">
            <thead>
                <tr>
                    <th>technician</th>
                    <th>Month</th>
                    <th>front incentive(month)</th>
                    <th>skill incentive(month)</th>
                    <th>total incentive(month)</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['mechanic_name'])?></td>
                    <td><?= htmlspecialchars($row['month'])?></td>
                    <td><?= number_format($row['frontIncentive'], 2)?></td>
                    <td><?= number_format($row['skillIncentive'], 2)?></td>
                    <td><?= number_format($row['totalIncentive'], 2)?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <button class="page-btn" data-page="<?= $page-1 ?>">‹ Prev</button>
                <?php endif; ?>

                <?php for ($i=1; $i<=$totalPages; $i++): ?>
                    <button class="page-btn <?= $i==$page?'active':'' ?>" data-page="<?= $i ?>"><?= $i ?></button>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <button class="page-btn" data-page="<?= $page+1 ?>">Next ›</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php else: ?>
    <table class="incentive-table">
        <tbody>
            <tr>
                <td colspan="7" style="text-align:center; padding:40px;">
                    <img 
                        src="images/noIncentiveData.png" 
                        alt="No products found"
                        style="max-width:20%; height:auto; max-height:300px; display:block; margin:0 auto;"
                    >
                </td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>
</div>
</div>
</div>
<p><?php  ?></p>
<script>
document.querySelectorAll('.page-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const page = btn.dataset.page;
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        window.location.href = url;
    });
});
</script>
        
</body>
</html>