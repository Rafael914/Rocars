<?php
/**
 * expenses.php - Renders the table body and pagination
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';

$branchId = $_SESSION['branch_id'] ?? null;
if ($branchId === null) {
    echo "<table><tr><td colspan='10' style='color: red; text-align: center;'>Security Error: User branch ID not found.</td></tr></table>";
    exit;
}

// Helper for prepared statements
function refValues($arr) { 
    if (empty($arr)) return [];
    $refs = []; 
    foreach($arr as $key => $value) $refs[$key] = &$arr[$key]; 
    return $refs; 
}

$limit = 30;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

$whereClauses = [];
$params = [];
$types = "";

// 1. ALWAYS filter by branch
$whereClauses[] = "e.branch_id = ?";
$params[] = $branchId;
$types .= "i";


if (!empty($fromDate)) {
    $whereClauses[] = "e.date >= ?";
    $params[] = $fromDate . " 00:00:00"; 
    $types .= "s";
}

if (!empty($toDate)) {
    $whereClauses[] = "e.date <= ?";
    $params[] = $toDate . " 23:59:59";
    $types .= "s";
}

// 3. Search logic
if ($search !== '') {
    $whereClauses[] = "(e.amount LIKE ? OR e.details LIKE ? OR e.ca LIKE ? 
    OR e.category LIKE ? OR e.classification LIKE ? OR e.remarks LIKE ? OR e.code LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like]);
    $types .= "sssssss";
}

$whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// --- PAGINATION COUNT ---
$countQuery = "SELECT COUNT(*) FROM expenses e $whereSQL";
$stmtTotal = $conn->prepare($countQuery);
if (!empty($types)) {
    call_user_func_array([$stmtTotal, 'bind_param'], refValues(array_merge([$types], $params)));
}
$stmtTotal->execute();
$stmtTotal->bind_result($totalRows);
$stmtTotal->fetch();
$stmtTotal->close();

$totalPages = ceil($totalRows / $limit);

// --- MAIN DATA QUERY ---
// Sorting by e.date DESC now uses both Date AND Time accuracy
$sql = "SELECT e.*, b.branch_name
        FROM expenses e
        LEFT JOIN branches b ON e.branch_id = b.branch_id
        $whereSQL
        ORDER BY e.date DESC
        LIMIT ?, ?";

$params2 = array_merge($params, [$offset, $limit]);
$types2 = $types . "ii";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo "<table><tr><td colspan='10' style='color: red; text-align: center;'>SQL Error: " . $conn->error . "</td></tr></table>";
    exit;
}

call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$types2], $params2)));
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/expenses.css">
    <style>
        .dt-container { display: flex; flex-direction: column; }
        .dt-date { font-weight: 600; color: #1a202c; }
        .dt-time { font-size: 0.75rem; color: #718096; margin-top: 2px; }
        .amount-cell { font-weight: 700; color: #2d3748; }
        .badge-code { background: #edf2f7; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Amount</th>
                    <th>Details</th>
                    <th>CA</th>
                    <th>Receipt Status</th>
                    <th>Classification</th>
                    <th>Remarks</th>
                    <th>Code</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding:50px;">
                        <img src="images/noExpenses.png" alt="No Data" style="max-width:180px; opacity:0.7;">
                        <p style="color:#a0aec0; margin-top:10px;">No records found matching your filters.</p>
                    </td>
                </tr>
                <?php endif; ?>

                <?php while ($row = $result->fetch_assoc()): 
                    $ts = strtotime($row['date']);
                    $fDate = date('M d, Y', $ts);
                    $fTime = date('h:i A', $ts);
                ?>
                <tr data-id="<?= $row['expense_id'] ?>">
                    <td>
                        <div class="dt-container">
                            <span class="dt-date"><?= $fDate ?></span>
                            <span class="dt-time"><i class="fa-regular fa-clock"></i> <?= $fTime ?></span>
                        </div>
                    </td>
                    <td class="amount-cell">₱<?= number_format($row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['details']) ?></td>
                    <td>₱<?= number_format($row['ca'], 2) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><?= htmlspecialchars($row['classification']) ?></td>
                    <td><?= htmlspecialchars($row['remarks']) ?></td>
                    <td><span class="badge-code"><?= htmlspecialchars($row['code']) ?></span></td>
                    <td style="white-space: nowrap;">
                        <button class="editBtn" data-id="<?= $row['expense_id'] ?>" title="Edit">✏️</button>
                        <button class="delete-btn" data-id="<?= $row['expense_id'] ?>" title="Delete">🗑️</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination" style="margin-top: 20px; display: flex; gap: 5px; justify-content: center;">
        <?php if ($page > 1): ?>
            <button class="page-btn" data-page="<?= $page - 1 ?>">Prev</button>
        <?php endif; ?>

        <?php 
        // Showing a limited range of pages if totalPages is large
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        
        for ($i = $start; $i <= $end; $i++): ?>
            <button class="page-btn <?= $i == $page ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></button>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <button class="page-btn" data-page="<?= $page + 1 ?>">Next</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php $stmt->close(); ?>
</body>
</html>