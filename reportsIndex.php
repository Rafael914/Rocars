<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$branch_id = $_SESSION['branch_id'];

// 1. Capture Filters
$fromDate = $_GET['from_date'] ?? '';
$toDate   = $_GET['to_date'] ?? '';
$selectedCategory = $_GET['category'] ?? '';

// Extracts year from 'From' date; defaults to current year if empty.
$year = (!empty($fromDate)) ? date('Y', strtotime($fromDate)) : date('Y');

// Fetch categories for dropdown
$categories = [];
$catStmt = $conn->prepare("SELECT category_name FROM categories ORDER BY category_name ASC");
$catStmt->execute();
$catRes = $catStmt->get_result();
while ($c = $catRes->fetch_assoc()) {
    $categories[] = $c['category_name'];
}

// 2. Optimized Summary Function
function getMonthlySummary($conn, $branch_id, $year, $category, $fromDate, $toDate) {
    $filtersSql = '';
    $params = [$branch_id, $year, $branch_id, $year, $branch_id, $year];
    $types = "iiiiii"; 

    if ($category) {
        $filtersSql .= " AND s.category = ? ";
        $types .= 's';
        $params[] = $category;
    }
    if ($fromDate) { $filtersSql .= " AND s.date >= ? "; $types .= 's'; $params[] = $fromDate; }
    if ($toDate) { $filtersSql .= " AND s.date <= ? "; $types .= 's'; $params[] = $toDate; }

    $sql = "
        SELECT 
            MONTH(s.date) AS month_num,
            IFNULL(SUM(s.total_amount), 0) AS total_amount,
            IFNULL(SUM(s.gross_profit), 0) AS gross_profit,
            (SELECT IFNULL(SUM(amount), 0) FROM expenses WHERE branch_id = ? AND YEAR(date) = ? AND MONTH(date) = MONTH(s.date)) AS total_expenses,
            (IFNULL(SUM(s.gross_profit), 0) - (SELECT IFNULL(SUM(amount), 0) FROM expenses WHERE branch_id = ? AND YEAR(date) = ? AND MONTH(date) = MONTH(s.date))) AS net_profit
        FROM sales s
        WHERE s.branch_id = ? AND YEAR(s.date) = ? $filtersSql
        GROUP BY MONTH(s.date) ORDER BY MONTH(s.date)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// --- NEW PRODUCT ANALYTICS FUNCTION ---
function getTopProductsData($conn, $branch_id, $year, $category) {
    $filtersSql = $category ? " AND category = ?" : "";
    $sql = "
        SELECT item_name, MONTH(date) as month_num, SUM(total_amount) as monthly_sales
        FROM sales
        WHERE branch_id = ? AND YEAR(date) = ? $filtersSql
        AND item_name IN (
            SELECT item_name FROM (
                SELECT item_name FROM sales 
                WHERE branch_id = ? AND YEAR(date) = ?
                GROUP BY item_name ORDER BY SUM(total_amount) DESC LIMIT 6
            ) as tops
        )
        GROUP BY item_name, MONTH(date)
    ";
    $stmt = $conn->prepare($sql);
    if($category) {
        $stmt->bind_param("iisii", $branch_id, $year, $category, $branch_id, $year);
    } else {
        $stmt->bind_param("iiii", $branch_id, $year, $branch_id, $year);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$monthlyData = getMonthlySummary($conn, $branch_id, $year, $selectedCategory, $fromDate, $toDate);
$productRawData = getTopProductsData($conn, $branch_id, $year, $selectedCategory);

// 3. Data Preparation for Chart
$monthNamesShort = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
$labels = $salesData = $profitData = $expensesData = $netData = [];

foreach ($monthNamesShort as $num => $name) {
    $labels[] = $name;
    $found = array_filter($monthlyData, fn($m) => $m['month_num'] == $num);
    $m = $found ? array_values($found)[0] : ['total_amount'=>0,'gross_profit'=>0,'total_expenses'=>0,'net_profit'=>0];

    $salesData[]    = (float)$m['total_amount'];
    $profitData[]   = (float)$m['gross_profit'];
    $expensesData[] = (float)$m['total_expenses'];
    $netData[]      = (float)$m['net_profit'];
}

// --- PREPARE PRODUCT DATASETS FOR CHART ---
$itemNames = array_unique(array_column($productRawData, 'item_name'));
$productDatasets = [];
$colors = ['#3498db', '#e74c3c', '#2ecc71', '#f1c40f', '#9b59b6', '#1abc9c'];
$cIdx = 0;

foreach ($itemNames as $name) {
    $pts = [];
    for ($m = 1; $m <= 12; $m++) {
        $entry = array_filter($productRawData, fn($r) => $r['item_name'] == $name && $r['month_num'] == $m);
        $pts[] = $entry ? (float)array_values($entry)[0]['monthly_sales'] : 0;
    }
    $productDatasets[] = [
        'label' => $name,
        'data' => $pts,
        'borderColor' => $colors[$cIdx % 6],
        'backgroundColor' => $colors[$cIdx % 6],
        'tension' => 0.4,
        'fill' => false,
        'pointHoverRadius' => 8
    ];
    $cIdx++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Analytics</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/reportsIndex.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .net-profit-positive { color: #2ecc71; font-weight: bold; }
        .net-profit-negative { color: #e74c3c; font-weight: bold; }
        .chart-wrapper { height: 400px; margin-top: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <div class="topbar">
        <i class="fa-solid fa-bars toggle" id="toggleBtn"></i>
        <h1>Financial Performance (<?= $year ?>)</h1>
        <div class="logo"><img src="images/rocarsn.png" class="logo-img"></div>
    </div>

    <div class="filters">
        <div>
            <label>Category</label>
            <select id="categoryFilter">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>From Date</label>
            <input type="date" id="fromDate" value="<?= htmlspecialchars($fromDate) ?>">
        </div>
        <div>
            <label>To Date</label>
            <input type="date" id="toDate" value="<?= htmlspecialchars($toDate) ?>">
        </div>
        <button type="button" class="btn-clear" onclick="clearAllFilters()">
            <i class="fa-solid fa-rotate-left"></i> Clear
        </button>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Gross Sales</th>
                    <th>Gross Profit</th>
                    <th>Expenses</th>
                    <th>Net Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $fullMonths = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                foreach ($fullMonths as $num => $name):
                    $found = array_filter($monthlyData, fn($m) => $m['month_num'] == $num);
                    $m = $found ? array_values($found)[0] : ['total_amount'=>0,'gross_profit'=>0,'total_expenses'=>0,'net_profit'=>0];
                    $netClass = $m['net_profit'] >= 0 ? 'net-profit-positive' : 'net-profit-negative';
                ?>
                <tr>
                    <td><?= $name ?></td>
                    <td>₱<?= number_format($m['total_amount'], 2) ?></td>
                    <td>₱<?= number_format($m['gross_profit'], 2) ?></td>
                    <td style="color: #e74c3c;">₱<?= number_format($m['total_expenses'], 2) ?></td>
                    <td class="<?= $netClass ?>">₱<?= number_format($m['net_profit'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="chart-wrapper">
        <h3 style="margin-bottom: 15px;">Revenue, Expenses & Profit</h3>
        <canvas id="financialChart"></canvas>
    </div>

    <div class="chart-wrapper">
        <h3 style="margin-bottom: 15px;">Top 6 Product Performance</h3>
        <canvas id="productChart"></canvas>
    </div>
</div>

<script src="assets/js/sidebar-toggle.js"></script>
<script>
// Filter Logic
const categoryFilter = document.getElementById('categoryFilter');
const fromDate = document.getElementById('fromDate');
const toDate = document.getElementById('toDate');

function applyFilters() {
    const params = new URLSearchParams();
    if (categoryFilter.value) params.set('category', categoryFilter.value);
    if (fromDate.value) params.set('from_date', fromDate.value);
    if (toDate.value) params.set('to_date', toDate.value);
    window.location.href = window.location.pathname + '?' + params.toString();
}

function clearAllFilters() {
    window.location.href = window.location.pathname;
}

[categoryFilter, fromDate, toDate].forEach(el => {
    el.addEventListener('change', applyFilters);
});

// Common Chart Options for Hover Values and Peso Formatting
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { position: 'top' },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.parsed.y !== null) {
                        label += new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(context.parsed.y);
                    }
                    return label;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: function(value) { return '₱' + value.toLocaleString(); }
            }
        }
    }
};

// 1. Financial Chart
const ctxFin = document.getElementById('financialChart').getContext('2d');
new Chart(ctxFin, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            { label: 'Gross Sales', data: <?= json_encode($salesData) ?>, borderColor: '#3498db', tension: 0.3, fill: false },
            { label: 'Expenses', data: <?= json_encode($expensesData) ?>, borderColor: '#e74c3c', tension: 0.3, fill: false },
            { label: 'Gross Profit', data: <?= json_encode($profitData) ?>, borderColor: '#2ecc71', tension: 0.3, fill: false },
            { label: 'Net Profit', data: <?= json_encode($netData) ?>, borderColor: '#f1c40f', tension: 0.3, fill: false }
        ]
    },
    options: commonOptions
});

// 2. Product Analytics Chart
const ctxProd = document.getElementById('productChart').getContext('2d');
new Chart(ctxProd, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: <?= json_encode($productDatasets) ?>
    },
    options: commonOptions
});
</script>
</body>
</html>