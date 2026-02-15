<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
$role = $_SESSION['role'] ?? 'guest';

global $conn;

$minDate = '';
$maxDate = '';

try {
    if ($conn) {
        $dateRes = $conn->query("SELECT MIN(date) AS min_date, MAX(date) AS max_date FROM sales");
        if ($dateRes) {
            $dateRow = $dateRes->fetch_assoc();
            $minDate = $dateRow['min_date'] ?? '';
            $maxDate = $dateRow['max_date'] ?? '';
        } else {
            $fallbackDate = date('Y-m-d');
            $minDate = $fallbackDate;
            $maxDate = $fallbackDate;
        }
    } else {
        $fallbackDate = date('Y-m-d');
        $minDate = $fallbackDate;
        $maxDate = $fallbackDate;
    }
} catch (Throwable $e) {
    error_log("Fatal error getting min/max dates: " . $e->getMessage());
    $fallbackDate = date('Y-m-d');
    $minDate = $fallbackDate;
    $maxDate = $fallbackDate;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mechanic Incentives Tracker</title>
<link rel="stylesheet" href="assets/css/sidebar.css">
<link rel="stylesheet" href="assets/css/topbar.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/mechanicsIndex.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <div class="topbar">
        <i class="fa-solid fa-bars toggle" id="toggleBtn" aria-label="Toggle sidebar"></i>
        <h1>Technician Incentives Tracker</h1>
        <div class="logo">
            <img src="images/rocarsn.png" alt="Logo" class="logo-img"> 
        </div>
    </div>

    <div class="filter-container">
        <div class="filter-row">
            <div class="filter-group">
                <label for="search">Search Records (Mechanic, SI, Customer)</label>
                <input type="text" id="search" placeholder="Enter keywords..." autocomplete="off">
            </div>

            <div class="filter-group">
                <label for="from">From Date</label>
                <input type="date" id="from" min="<?= $minDate ?>" max="<?= $maxDate ?>">
            </div>

            <div class="filter-group">
                <label for="to">To Date</label>
                <input type="date" id="to" min="<?= $minDate ?>" max="<?= $maxDate ?>">
            </div>

            <div class="button-group">
                <button class="btn btn-secondary" onclick="setToday()">Today</button>
                <button class="btn btn-secondary" onclick="setThisWeek()">This Week</button>
                <button class="btn btn-secondary" onclick="setThisMonth()">This Month</button>
                <button class="btn btn-secondary" onclick="setLastMonth()">Last Month</button>
                <button class="btn btn-primary btn-clear" onclick="clearFilters()">Clear Filters</button>
            </div>
        </div>
    </div>

    <div class="summary-box">
        <h3>TOTAL INCENTIVES (FILTERED)</h3>
        <p id="totalIncentives">₱0.00</p>
    </div>

    <div id="table-container">
        <div>Loading data...</div>
    </div>
    <div class="filter-container">
    <div class="filter-row">
             <div class="filter-group">
                <label for="search">Search Records (Mechanic, SI, Customer)</label>
                <input type="text" id="search2" placeholder="Enter keywords..." autocomplete="off">
            </div>
    </div>
    </div>
    
    <div id="table2-container">
        <div class="p-4 text-center text-gray-500">Loading data...</div>
    </div>
</div>

<script>
let timer;

function reloadAll(page = 1) {
    loadData(page);
    loadData2(page);
    updateSummaryTotal();
}

function formatDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

/**
 * Updated loadData to handle page numbers
 */
function loadData(page = 1) {
    const search = document.getElementById("search").value;
    const from = document.getElementById("from").value;
    const to = document.getElementById("to").value;

    document.getElementById("table-container").innerHTML = '<div class="p-4 text-center text-gray-500">Loading Table...</div>';

    fetch(`mechanics.php?search=${encodeURIComponent(search)}&from=${from}&to=${to}&page=${page}`)
        .then(res => res.text())
        .then(data => {
            document.getElementById("table-container").innerHTML = data;
            attachPaginationListeners();
        })
        .catch(error => console.error('Load Table Error:', error));
}

/**
 * Add this function to your script
 */
function updateSummaryTotal() {
    const search = document.getElementById("search").value;
    const from = document.getElementById("from").value;
    const to = document.getElementById("to").value;

    fetch(`actions/get_mechanic_total.php?search=${encodeURIComponent(search)}&from=${from}&to=${to}`)
        .then(res => res.json())
        .then(data => {
            // Format number to Philippine Peso
            const formatter = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
            });
            document.getElementById("totalIncentives").innerText = formatter.format(data.total);
        })
        .catch(err => console.error("Error fetching total:", err));
}

/**
 * Captures clicks on pagination buttons to prevent page reload
 */
function attachPaginationListeners() {
    const container = document.getElementById("table-container");
    const paginationButtons = container.querySelectorAll('.page-btn');
    
    paginationButtons.forEach(btn => {
        btn.onclick = function(e) {
            e.preventDefault();
            const pageNum = this.getAttribute('data-page');
            loadData(pageNum);  // Loads new data without scrolling
        };
    });
}

async function loadData2(page = 1) {
    const searchs = document.getElementById("search2").value;
    const from = document.getElementById("from").value;
    const to = document.getElementById("to").value;

    const table2 = document.getElementById("table2-container");
    table2.innerHTML = '<div class="p-4 text-center text-gray-500">Loading Table...</div>';

    try {
        const res = await fetch(
            `mechanics2.php?search=${encodeURIComponent(searchs)}&from=${from}&to=${to}&page=${page}`
        );
        const html = await res.text();   
        table2.innerHTML = html;
        attachPaginationListeners2();  
    } catch (err) {
        console.error("Load table 2 failed", err);
    }
}

function attachPaginationListeners2() {
    const container = document.getElementById("table2-container");
    const paginationButtons = container.querySelectorAll('.page-btn');
    
    paginationButtons.forEach(btn => {
        btn.onclick = function(e) {
            e.preventDefault();
            const pageNum = this.getAttribute('data-page');
            loadData2(pageNum);  // Loads new data without scrolling
        };
    });
}

function setToday() {
    const today = new Date();
    document.getElementById("from").value = formatDate(today);
    document.getElementById("to").value = formatDate(today);
    reloadAll(1);
}

function setThisWeek() {
    const d = new Date();
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1); 
    const monday = new Date(d.setDate(diff));
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);
    document.getElementById("from").value = formatDate(monday);
    document.getElementById("to").value = formatDate(sunday);
    reloadAll(1);
}

function setThisMonth() {
    const d = new Date();
    const first = new Date(d.getFullYear(), d.getMonth(), 1);
    const last = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    document.getElementById("from").value = formatDate(first);
    document.getElementById("to").value = formatDate(last);
    reloadAll(1);
}

function setLastMonth() {
    const d = new Date();
    const first = new Date(d.getFullYear(), d.getMonth() - 1, 1);
    const last = new Date(d.getFullYear(), d.getMonth(), 0);
    document.getElementById("from").value = formatDate(first);
    document.getElementById("to").value = formatDate(last);
    reloadAll(1);
}

function clearFilters() {
    document.getElementById('search').value = "";
    document.getElementById('from').value = "";
    document.getElementById('to').value = "";
    reloadAll(1);
}

document.getElementById("search").addEventListener("input", function() {
    clearTimeout(timer);
    timer = setTimeout(() => reloadAll(1), 200);
});

document.getElementById("search2").addEventListener("input", function () {
    clearTimeout(timer);
    timer = setTimeout(() => loadData2(1), 200);
});

// REPLACE WITH THESE:
document.getElementById("from").addEventListener("change", () => reloadAll(1));
document.getElementById("to").addEventListener("change", () => reloadAll(1));

window.onload = () => reloadAll(1);
</script>
<script src="assets/js/sidebar-toggle.js"></script>
</body>
</html>