<?php
ob_start(); 

require_once 'includes/config.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



$allowed_roles = ['master_admin', 'admin'];
$user_role = $_SESSION['role'] ?? 'guest';

if (!in_array($user_role, $allowed_roles)) {
    ob_end_clean();
    header("Location: inventorysearchmodal.php"); 
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT username, fullname, email, contact_number FROM users WHERE user_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$branch = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 0;

// Initialize variables
$todaysSales = 0; $totalRevenue = 0; $totalProducts = 0; $lowStock = 0;
$salesTrend = []; $topProducts = []; $categories = []; $recentSales = []; $criticalItems = [];
$paymentTrendData = ['CASH' => [], 'GCASH' => [], 'CARD' => []];
$payDates = [];

if ($branch > 0) {
    // KPI Fetching
    $todayQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(date) = CURDATE() AND branch_id = ?";
    $stmt = $conn->prepare($todayQuery); $stmt->bind_param("i", $branch); $stmt->execute();
    $todaysSales = (float)$stmt->get_result()->fetch_assoc()['total'];

    $revenueQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE branch_id = ?";
    $stmt = $conn->prepare($revenueQuery); $stmt->bind_param("i", $branch); $stmt->execute();
    $totalRevenue = (float)$stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM inventory WHERE branch_id = ?");
    $stmt->bind_param("i", $branch); $stmt->execute();
    $totalProducts = (int)$stmt->get_result()->fetch_assoc()['count'];

    $lowStockQuery = "SELECT COUNT(*) as count FROM inventory i JOIN products p ON i.product_id = p.product_id WHERE i.branch_id = ? AND i.quantity <= p.critical_stock_level";
    $stmt = $conn->prepare($lowStockQuery); $stmt->bind_param("i", $branch); $stmt->execute();
    $lowStock = (int)$stmt->get_result()->fetch_assoc()['count'];

    // Critical Stock
    $criticalListQuery = "SELECT p.product_name, p.critical_stock_level, i.quantity FROM inventory i JOIN products p ON i.product_id = p.product_id WHERE i.branch_id = ? AND i.quantity <= p.critical_stock_level ORDER BY i.quantity ASC LIMIT 10";
    $stmt = $conn->prepare($criticalListQuery); $stmt->bind_param("i", $branch); $stmt->execute();
    $criticalItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Sales Trend (7 Days)
    $trendQuery = "SELECT DATE(date) as sale_date, SUM(total_amount) as daily_revenue FROM sales WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND branch_id = ? GROUP BY DATE(date)";
    $stmt = $conn->prepare($trendQuery); $stmt->bind_param("i", $branch); $stmt->execute();
    $dbTrend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $revenueMap = [];
    foreach ($dbTrend as $row) { $revenueMap[$row['sale_date']] = (float)$row['daily_revenue']; }
    for ($i = 6; $i >= 0; $i--) {
        $dateKey = date('Y-m-d', strtotime("-$i days"));
        $salesTrend[] = ['date' => date('M d', strtotime($dateKey)), 'revenue' => $revenueMap[$dateKey] ?? 0];
    }

    // Top Products
    $stmt = $conn->prepare("SELECT item_name, SUM(total_amount) as revenue FROM sales WHERE branch_id = ? GROUP BY item_name ORDER BY revenue DESC LIMIT 5");
    $stmt->bind_param("i", $branch); $stmt->execute();
    $topProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($topProducts as &$p) {
        $p['item_name'] = htmlspecialchars($p['item_name'] ?: 'Unnamed Item');
        $p['revenue'] = (float)$p['revenue'];
    }
    unset($p);

    // Category Performance
    $catPerfQuery = "SELECT c.category_name as category_display, SUM(s.total_amount) as revenue FROM sales s JOIN categories c ON s.category = c.category_id WHERE s.branch_id = ? GROUP BY c.category_name ORDER BY revenue DESC";
    $stmt = $conn->prepare($catPerfQuery); $stmt->bind_param("i", $branch); $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Payment Trend
    $payTrendQuery = "SELECT DATE(date) as sale_date, UPPER(TRIM(payment_method)) as method, SUM(total_amount) as total 
                      FROM sales 
                      WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND branch_id = ? 
                      GROUP BY DATE(date), UPPER(TRIM(payment_method))";
    $stmt = $conn->prepare($payTrendQuery); $stmt->bind_param("i", $branch); $stmt->execute();
    $rawPayData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $payMethods = ['CASH', 'GCASH', 'CARD'];
    for ($i = 6; $i >= 0; $i--) {
        $dateKey = date('Y-m-d', strtotime("-$i days"));
        $payDates[] = date('M d', strtotime($dateKey));
        foreach ($payMethods as $method) {
            $amount = 0;
            foreach ($rawPayData as $row) {
                if ($row['sale_date'] === $dateKey && $row['method'] === $method) {
                    $amount = (float)$row['total'];
                    break;
                }
            }
            $paymentTrendData[$method][] = $amount;
        }
    }

    // Recent Sales
    $stmt = $conn->prepare("SELECT si_number, date, item_name, total_amount, payment_method, customer_name FROM sales WHERE branch_id = ? ORDER BY date DESC LIMIT 10");
    $stmt->bind_param("i", $branch); $stmt->execute(); 
    $recentSales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ROCARS Dashboard</title>
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/dashboard2.css">
<link rel="stylesheet" href="assets/css/sidebar.css">
<link rel="stylesheet" href="assets/css/topbar.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* Modal overlay */
.editCredentialModal {
    display: none; 
    position: fixed;  
    z-index: 9999; 
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
}

/* Modal content */
.editCredentialModalContent {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 30px 25px;
    width: 90%;
    max-width: 400px;
    border-radius: 12px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
    text-align: center;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

/* Black header strip */
.editCredentialModalHeader {
    background-color: #000;        /* black strip */
    color: #fff;                   /* white text */
    padding: 12px 20px;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    font-size: 1.2rem;
    font-weight: bold;
    text-align: center;
    position: relative;
    margin: -30px -25px 20px -25px; /* stretch full width of modal */
}

/* Close "×" button in header */
.editCredentialModalClose {
    position: absolute;
    top: 8px;
    right: 12px;
    font-size: 1.4rem;
    cursor: pointer;
}

/* Inputs */
.editCredentialModalContent input[type="text"],
.editCredentialModalContent input[type="email"],
.editCredentialModalContent input[type="password"] {
    width: 100%;
    padding: 10px 12px;
    margin: 8px 0 15px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 0.95rem;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.editCredentialModalContent input:focus {
    border-color: #2196F3;
    outline: none;
}

/* Buttons container */
.editCredentialModalButtons {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 10px;
}

/* Buttons */
.editCredentialModalButtons .save-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.95rem;
    background-color: #2196F3;
    color: #fff;
    transition: background-color 0.2s;
}

.editCredentialModalButtons .save-btn:hover {
    background-color: #1976D2;
}

.editCredentialModalButtons .cancel-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.95rem;
    background-color: #f44336;
    color: #fff;
    transition: background-color 0.2s;
}

.editCredentialModalButtons .cancel-btn:hover {
    background-color: #d32f2f;
}


.editCredentialModalContent input[type="text"],
.editCredentialModalContent input[type="email"],
.editCredentialModalContent input[type="password"] {
    width: 100%;
    padding: 10px 12px;
    margin: 8px 0 15px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 0.95rem;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.editCredentialModalContent input:focus {
    border-color: #2196F3;
    outline: none;
}

.toast {
    min-width: 250px;
    margin-top: 10px;
    background-color: #333;
    color: #fff;
    padding: 14px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    font-size: 0.95rem;
    opacity: 0;
    transform: translateX(100%);
    transition: opacity 0.5s ease, transform 0.5s ease;
}

/* Show toast */
.toast.show {
    opacity: 1;
    transform: translateX(0);
}


</style>
</head>
<body class="dashboard-page">
<?php include 'includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">

<div class="topbar" style="display:flex; align-items:center; justify-content:space-between; padding:10px;">
    <div style="display:flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-bars toggle" id="toggleBtn"></i>
        <h1 style="font-size:1.1rem; margin:0;">Dashboard Overview</h1>
    </div>

    <div class="user-info" style="display:flex; flex-direction:column; align-items:center; text-align:center;">
        
        <div>
             <i class="fa-solid fa-circle-user" style="font-size:28px; color:white; margin-right:2px;"></i>
        <span style="font-size:1.2rem; color:white;">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
        <a href="edit_credentials.php" style="font-size:0.8rem; color:#2196F3; text-decoration:none;">Edit Credentials</a>
    </div>
</div>

<!-- Edit Credentials Modal -->
<div id="editCredentialModal" class="editCredentialModal">
    <div class="editCredentialModalContent">
        <!-- Black header strip -->
        <div class="editCredentialModalHeader">
            <h3>Edit Credentials</h3>
            <span class="editCredentialModalClose" onclick="closeEditCredentialModal()">&times;</span>
        </div>

        <form id="editCredentialForm" method="POST" action="actions/edit_credentials.php" onsubmit="return validatePasswords()">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

            <label>Full Name</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>">

            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">

            <label>Contact Number</label>
            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>">

            <label>New Password</label>
            <input type="password" name="password" id="password" placeholder="Leave blank to keep current">

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password">

            <div class="editCredentialModalButtons">
                <button type="submit" name="update_credentials" class="save-btn">Save</button>
                <button type="button" class="cancel-btn" onclick="closeEditCredentialModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>
<div id="toastContainer" style="position: fixed; bottom: 20px; right: 20px; z-index: 10000;"></div>



    <div class="kpi-container">
      <div class="kpi"><h3>Today's Sales</h3><p>₱<?= number_format($todaysSales,2) ?></p></div>
      <div class="kpi" style="border-bottom-color:#10b981;"><h3>Total Revenue</h3><p>₱<?= number_format($totalRevenue,2) ?></p></div>
      <div class="kpi" style="border-bottom-color:#8b5cf6;"><h3>Total Products</h3><p><?= $totalProducts ?></p></div>
      <div class="kpi" style="border-bottom-color:#ef4444;"><h3>Low Stock</h3><p><?= $lowStock ?></p></div>
    </div>

    <!-- Critical Stock Table -->
   

    <div class="section-header"><i class="fa-solid fa-chart-line" style="color:#3b82f6;"></i><span>Business Performance Analytics</span></div>
    <div class="charts-grid">
      <div class="chart-box"><h3>General Sales Trend</h3><div class="chart-container"><canvas id="salesTrendChart"></canvas></div></div>
      <div class="chart-box"><h3>Top Products</h3><div class="chart-container"><canvas id="topProductsChart"></canvas></div></div>
      <div class="chart-box"><h3>Category Performance</h3><div class="chart-container"><canvas id="categoryChart"></canvas></div></div>
      <div class="chart-box"><h3>Payment Method Trends</h3><div class="chart-container"><canvas id="paymentLineChart"></canvas></div></div>
    </div>

    <div class="critical-container">
    <div class="critical-stock">
        <h3>Critical Stock Items</h3>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Critical Stock Level</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($criticalItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name'] ?: 'N/A') ?></td>
                        <td><?= htmlspecialchars($item['quantity'] ?: '0') ?></td>
                        <td>
                          <div  class="critical-row">critical</div>  <br>
                        <?= htmlspecialchars($item['critical_stock_level'] ?: '0') ?>
                        
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

   <?php if(empty($criticalItems)): ?>
  <tr>
    <td>
        <img src="images/noCritical.png" alt="" style="max-width:20%; height:auto; max-height:300px; display:block; margin:0 auto";>
    </td>
  </tr>
    <?php endif; ?>

    <div class="recent-sales">
        <h3>Recent Sales Transactions</h3>
        
        <div style="overflow-x:auto;">
            
            <table>
                <thead><tr><th>SI Number</th><th>Customer</th><th>Item</th><th>Date</th><th>Amount</th><th>Payment</th></tr></thead>
                <tbody>
                <?php foreach($recentSales as $sale): ?>
                <tr>
                    <td><strong>#<?= htmlspecialchars($sale['si_number']) ?></strong></td>
                    <td><?= htmlspecialchars($sale['customer_name'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($sale['item_name'] ?: 'N/A') ?></td>
                    <td><?= date('m/d/y H:i', strtotime($sale['date'])) ?></td>
                    <td>₱<?= number_format($sale['total_amount'],2) ?></td>
                    <td><span style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:0.68rem;"><?= ucfirst(strtolower(htmlspecialchars($sale['payment_method']))) ?></span></td>
                </tr>
                <?php endforeach; ?>

                </tbody>
            </table>
        </div>
    </div>
                <?php if(empty($recentSales)): ?>
            <tr colspan="6" style="text-align:center; padding:40px;">
                <td>
                    <img src="images/noProduct.png" alt="" style="max-width:20%; height:auto; max-height:300px; display:block; margin:0 auto";>
                </td>
            </tr>
            <?php endif; ?>
</div>



<script src="assets/js/sidebar-toggle.js"></script>
<script>
const salesTrendData = <?= json_encode($salesTrend) ?>;
const topProductsData = <?= json_encode($topProducts) ?>;
const categoryData = <?= json_encode($categories) ?>;
const pDates = <?= json_encode($payDates) ?>;
const pTrend = <?= json_encode($paymentTrendData) ?>;

Chart.defaults.devicePixelRatio = window.devicePixelRatio || 1;
Chart.defaults.font.size = 11;

const commonOptions = { 
    responsive: true, 
    maintainAspectRatio: false, 
    plugins: { legend: { position: 'bottom' } } 
};

// General Sales Trend
new Chart(document.getElementById('salesTrendChart'), {
  type: 'line',
  data: { labels: salesTrendData.map(d=>d.date), datasets:[{ label:'Revenue', data:salesTrendData.map(d=>d.revenue), borderColor:'#3b82f6', borderWidth:2, fill:true, backgroundColor:'rgba(59,130,246,0.1)', tension:0.4 }] },
  options: commonOptions
});

// Top Products
new Chart(document.getElementById('topProductsChart'), {
    type:'bar',
    data:{
        labels: topProductsData.map(p=>p.item_name),
        datasets:[{
            label:'Revenue',
            data: topProductsData.map(p=>Number(p.revenue.toFixed(2))),
            backgroundColor:'#4318FF',
            borderRadius:5
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: ctx => '₱' + ctx.parsed.y.toLocaleString() } } },
        scales:{ y:{ beginAtZero:true, ticks:{ callback:v=>'₱'+v.toLocaleString() } } }
    }
});

// Category Performance
new Chart(document.getElementById('categoryChart'), {
    type:'bar',
    data:{ labels: categoryData.map(c=>c.category_display), datasets:[{ label:'Revenue', data:categoryData.map(c=>c.revenue), backgroundColor:'#8b5cf6' }] },
    options:commonOptions
});

// Payment Method Trend
new Chart(document.getElementById('paymentLineChart'), {
    type:'line',
    data:{
        labels:pDates,
        datasets:[
            { label:'Cash', data:pTrend.CASH, borderColor:'#10b981', borderWidth:2.5, tension:0.3, pointRadius:3, fill:false },
            { label:'GCash', data:pTrend.GCASH, borderColor:'#3b82f6', borderWidth:2.5, tension:0.3, pointRadius:3, fill:false },
            { label:'Card', data:pTrend.CARD, borderColor:'#f97316', borderWidth:2.5, tension:0.3, pointRadius:3, fill:false }
        ]
    },
    options:{
        ...commonOptions,
        interaction:{ mode:'index', intersect:false },
        plugins:{ tooltip:{ callbacks:{ label: ctx=>'₱'+ctx.parsed.y.toLocaleString() } } },
        scales:{ y:{ beginAtZero:true, ticks:{ callback:v=>'₱'+v.toLocaleString() } } }
    }
});



function openEditCredentialModal() {
    document.getElementById('editCredentialModal').style.display = 'block';
}

function closeEditCredentialModal() {
    document.getElementById('editCredentialModal').style.display = 'none';
}

// Click outside modal to close
window.onclick = function(event) {
    if (event.target == document.getElementById('editCredentialModal')) {
        closeEditCredentialModal();
    }
}

// Open modal on link click
document.querySelector('.user-info a').addEventListener('click', function(e){
    e.preventDefault();
    openEditCredentialModal();
});

// Validate password & confirm password
function validatePasswords() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;

    if (password !== confirm) {
        alert("Passwords do not match!");
        return false; // prevent form submission
    }
    return true;
}
function showToast(message, duration = 3000) {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerText = message;
    toastContainer.appendChild(toast);

    // Trigger CSS animation
    setTimeout(() => toast.classList.add('show'), 100);

    // Remove after duration
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 500);
    }, duration);
}

<?php if(isset($_SESSION['toast_success'])): ?>
    showToast("<?php echo $_SESSION['toast_success']; ?>");
    <?php unset($_SESSION['toast_success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['toast_error'])): ?>
    showToast("<?php echo $_SESSION['toast_error']; ?>");
    <?php unset($_SESSION['toast_error']); ?>
<?php endif; ?>

</script>
</body>
</html>