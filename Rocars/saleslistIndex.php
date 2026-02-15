<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Get mechanic list
$mechRes = $conn->query("SELECT mechanic_id, mechanic_name FROM mechanics"); 

// Get min/max sale dates to restrict the date picker
$dateRes = $conn->query("SELECT MIN(date) AS min_date, MAX(date) AS max_date FROM sales");
$dateRow = $dateRes->fetch_assoc();
$minDate = $dateRow['min_date'] ?? '';
$maxDate = $dateRow['max_date'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History | Rocars</title>
    
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/salesllistIndex.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
/* ================================
   MODAL OVERLAY
================================ */
.modal {
    display: none; /* hidden by default */
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55); /* dark overlay */
    backdrop-filter: blur(4px);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    font-family: 'Poppins', sans-serif;
}


.modal-content {
    width: 400px;
    max-width: 95%;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
    animation: modalFade 0.25s ease;
    position: relative;
    padding: 0;
}
.modal-header {
    background-color: #000; 
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px; /* Padding stays inside the black bar */
    width: 100%; 
    margin: 0; /* Ensure no margins */
    box-sizing: border-box; 
}

#editForm {
    padding: 20px;
}
.modal-header h3 {
    margin: 0;
    font-size: 18px;
}
.modal-header .close {
    font-size: 22px;
    cursor: pointer;
    color: #fff;
    transition: 0.2s;
}
.modal-header .close:hover {
    color: #ff4d4d;
}



#editForm label {
    display: block;

    font-size: 14px;
    font-weight: 500;
    color: #333;
}

#editForm input,
#editForm select,
#editForm textarea {
    width: 100%;

    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
    outline: none;
    transition: 0.2s;
}

#editForm input:focus,
#editForm select:focus,
#editForm textarea:focus {
    border-color: #000;
    box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
}

#editForm textarea {
    min-height: 60px;
    resize: vertical;
}

/* ================================
   BUTTONS
================================ */
#editForm button[type="submit"] {
    margin-top: 15px;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: 0.25s;
    width: 100%;
}

#editForm button[type="submit"]:hover {
    background: #1f1f1f;
    transform: translateY(-1px);
}

/* ================================
   FLEX ROWS FOR MULTIPLE INPUTS
================================ */
.input-row {
    display: flex;
    gap: 15px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.input-row .input-group {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* ================================
   MODAL ANIMATION
================================ */
@keyframes modalFade {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

/* ================================
   MOBILE RESPONSIVE
================================ */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
    }
}


    </style>
</head>
<body>

  <?php include 'includes/sidebar.php'; ?>

  <div class="main-content" id="mainContent">
    <div class="topbar">
      <i class="fa-solid fa-bars toggle" id="toggleBtn" aria-label="Toggle sidebar"></i>
      <h1>Sales History</h1> 
      <div class="logo">
        <img src="images/rocarsn.png" alt="Rocars" class="logo-img">
      </div>
    </div>

    <div id="messageBox" style="display:none; padding:10px; border-radius:5px;"></div>

    <div class="filter-container">
        <div class="filter-row">
            <div class="filter-group">
                <label for="search">Search Records</label>
                <input type="text" id="search" placeholder="Enter search terms..." autocomplete="off">
            </div>
            
            <div class="filter-group">
                <label for="from_date">From Date</label>
                <input type="date" id="from_date" min="<?= $minDate ?>" max="<?= $maxDate ?>">
            </div>
            
            <div class="filter-group">
                <label for="to_date">To Date</label>
                <input type="date" id="to_date" min="<?= $minDate ?>" max="<?= $maxDate ?>">
            </div>
            
            <div class="filter-group">
                <label for="mechanic_id">Mechanic</label>
                <select id="mechanic_id">
                    <option value="">All Mechanics</option>
                    <?php while($m = $mechRes->fetch_assoc()): ?>
                        <option value="<?= $m['mechanic_id'] ?>"><?= htmlspecialchars($m['mechanic_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        
            <div class="button-group">
                <button class="btn btn-primary btn-excel" onclick="exportExcel()">Export to Excel</button>
                <button class="btn btn-secondary btn-clear" onclick="clearFilters()">Clear Filters</button>
            </div>
        </div> 
    </div>

    <div id="salesTable" class="tableSales">Loading sales data...</div>

<div id="editModal" class="modal">
  <div class="modal-content">
    <!-- Black Top Strip -->
    <div class="modal-header">
      <h3>Edit Sale Record</h3>
      <span class="close">&times;</span>
    </div>

    <form id="editForm">
      <input type="hidden" name="sales_id" id="edit_sales_id">

      <!-- Mechanic -->
      <label for="edit_mechanic_id">Mechanic</label>
      <select name="mechanic_id" id="edit_mechanic_id" required>
        <?php 
        $mechRes->data_seek(0); 
        while($m = $mechRes->fetch_assoc()): 
        ?>
            <option value="<?= $m['mechanic_id'] ?>"><?= htmlspecialchars($m['mechanic_name']) ?></option>
        <?php endwhile; ?>
      </select>

      <!-- SI Number + Date -->
      <div class="input-row">
        <div class="input-group">
          <label>SI Number</label>
          <input type="text" name="si_number" id="edit_si_number" required>
        </div>
        <div class="input-group">
          <label>Date</label>
          <input type="date" name="date" id="edit_date" required>
        </div>
      </div>

      <!-- Customer Name + Phone Number -->
      <div class="input-row">
        <div class="input-group">
          <label>Customer Name</label>
          <input type="text" name="customer_name" id="edit_customer_name" required>
        </div>
        <div class="input-group">
          <label>Phone Number</label>
          <input type="text" name="phoneNumber" id="edit_cp_number">
        </div>
      </div>

      <!-- Vehicle + Plate No -->
      <div class="input-row">
        <div class="input-group">
          <label>Vehicle</label>
          <input type="text" name="vehicle" id="edit_vehicle">
        </div>
        <div class="input-group">
          <label>Plate No</label>
          <input type="text" name="plateNumber" id="edit_plate_no">
        </div>
      </div>

      <!-- Odometer -->
      <label>Odometer</label>
      <input type="text" name="odometer" id="edit_odometer">

      <!-- Total Amount + Total Cost + Gross Profit -->
      <div class="input-row">
        <div class="input-group">
          <label>Total Amount</label>
          <input type="number" step="0.01" name="total_amount" id="edit_total_amount">
        </div>
        <div class="input-group">
          <label>Total Cost</label>
          <input type="number" step="0.01" name="totalCost" id="edit_total_cost">
        </div>
        <div class="input-group">
          <label>Gross Profit</label>
          <input type="number" step="0.01" name="grossProfit" id="edit_gross_profit">
        </div>
      </div>

      <!-- Remarks -->
      <label>Remarks</label>
      <textarea name="remarks" id="edit_remarks"></textarea>

      <!-- Discrepancy -->
      <label>Discrepancy</label>
      <input type="text" name="discrepancy" id="edit_discrepancy">

      <!-- Front + Skill Incentive -->
      <div class="input-row">
        <div class="input-group">
          <label>Front Incentive</label>
          <input type="number" step="0.01" name="front_incentive" id="edit_front_incentive">
        </div>
        <div class="input-group">
          <label>Skill Incentive</label>
          <input type="number" step="0.01" name="skill_incentive" id="edit_skill_incentive">
        </div>
      </div>

      <!-- Payment Method -->
      <label>Payment Method</label>
      <input type="text" name="payment_method" id="edit_payment_method">

      <!-- Submit -->
      <button type="submit" class="btn btn-primary" style="margin-top:10px;">Save Changes</button>
    </form>
  </div>
</div>

    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <span class="close closeReceipt">&times;</span>
            <div id="receiptBody"></div>
        </div>
    </div>

  </div> <script>
let timer; 

// --- Core Function: Load Table ---
function loadSales(page = 1) {
    const search = document.getElementById('search').value;
    const from = document.getElementById('from_date').value;
    const to = document.getElementById('to_date').value;
    const mech = document.getElementById('mechanic_id').value;

    const params = new URLSearchParams({
        page: page,
        search: search,
        from_date: from,
        to_date: to,
        mechanic_id: mech
    });

    fetch('saleslist.php?' + params)
        .then(res => res.text())
        .then(html => {
            document.getElementById('salesTable').innerHTML = html;
        })
        .catch(err => console.error("Error loading sales:", err));
}

// --- Event Listeners for Filters ---
document.getElementById('search').addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => loadSales(1), 50);
});

document.getElementById('from_date').addEventListener('change', () => loadSales(1));
document.getElementById('to_date').addEventListener('change', () => loadSales(1));
document.getElementById('mechanic_id').addEventListener('change', () => loadSales(1));

document.addEventListener('click', function (e) {

    // Pagination
    if (e.target.classList.contains('page-btn')) {
        loadSales(e.target.getAttribute('data-page'));
        return;
    }

    // OPEN RECEIPT IN MODAL
    const receiptBtn = e.target.closest('.receiptBtn');
    if (receiptBtn) {
        const id = receiptBtn.dataset.siNumber;

        fetch(`includes/generatesalesReceipt.php?si_number=${id}`)
            .then(res => res.text())
            .then(html => {
                document.getElementById('receiptBody').innerHTML = html;
                document.getElementById('receiptModal').style.display = 'flex';
            });

        return;
    }

    // CLOSE MODALS
    if (e.target.classList.contains('close') || e.target.classList.contains('modal')) {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('receiptModal').style.display = 'none';
    }
});

function printReceipt() {
    const receiptContent = document.getElementById('receiptBody').innerHTML;

    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';

    document.body.appendChild(iframe);

    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(`
        <html>
        <head>
            <title>Print Receipt</title>
            <style>
    @page {
        size: Letter portrait;
        margin: 0.5in;
    }

    body {
        margin: 0;
        padding: 0;
    }
}

                .receipt {
                    width: 80mm;
                    margin: 0 auto;
                }

                button { display: none; }
            </style>
        </head>
        <body>
            ${receiptContent}
        </body>
        </html>
    `);
    doc.close();

    iframe.contentWindow.focus();
    iframe.contentWindow.print();

    setTimeout(() => document.body.removeChild(iframe), 500);
}


// --- 1. Populate Modal with Data ---
async function editSales(si_number) {
    try {
        const res = await fetch(`actions/fetchSale.php?si_number=${si_number}`);
        
        // This is the array of items returned by PHP
        const salesData = await res.json(); 

        // Check if we actually got an array with content
        if(salesData && salesData.length > 0) {
            
            // 1. Use the first item in the array to fill the header/general info
            const firstItem = salesData[0];
            
            document.getElementById("edit_sales_id").value = firstItem.sales_id;
            document.getElementById("edit_mechanic_id").value = firstItem.mechanic_id;
            document.getElementById("edit_si_number").value = firstItem.si_number;
            document.getElementById("edit_date").value = firstItem.date;
            document.getElementById("edit_customer_name").value = firstItem.customer_name;
            document.getElementById("edit_vehicle").value = firstItem.vehicle;
            document.getElementById("edit_plate_no").value = firstItem.plate_no;
            document.getElementById("edit_odometer").value = firstItem.odometer;
            document.getElementById("edit_cp_number").value = firstItem.cp_number;
            document.getElementById("edit_total_amount").value = firstItem.total_amount;
            document.getElementById("edit_total_cost").value = firstItem.total_cost;
            document.getElementById("edit_gross_profit").value = firstItem.gross_profit;
            document.getElementById("edit_remarks").value = firstItem.remarks;
            document.getElementById("edit_discrepancy").value = firstItem.discrepancy;
            document.getElementById("edit_front_incentive").value = firstItem.front_incentive;
            document.getElementById("edit_skill_incentive").value = firstItem.skill_incentive;
            document.getElementById("edit_payment_method").value = firstItem.payment_method;

          
            
            document.getElementById("editModal").style.display = "flex";
        } else {
            showMessage("No record found for this SI number", "error");
        }
    } catch (error) {
        console.error('Fetch Error:', error);
        showMessage("Could not fetch data", "error");
    }
}



function showMessage(message, type='info') {
    const box = document.getElementById('messageBox');
    box.textContent = message;
    box.style.display = 'block';
    
    const styles = {
        success: { bg: '#d1fae5', text: '#065f46', border: '1px solid #10b981' },
        error: { bg: '#fee2e2', text: '#991b1b', border: '1px solid #f87171' },
        info: { bg: '#e0f2fe', text: '#0369a1', border: '1px solid #38bdf8' }
    };

    const style = styles[type] || styles.info;
    Object.assign(box.style, {
        background: style.bg,
        color: style.text,
        border: style.border
    });

    setTimeout(() => { box.style.display = 'none'; }, 3000);
}

function clearFilters() {
    document.getElementById('search').value = '';
    document.getElementById('from_date').value = '';
    document.getElementById('to_date').value = '';
    document.getElementById('mechanic_id').value = '';
    loadSales(1);
}

function exportExcel() {
    const params = new URLSearchParams({
        search: document.getElementById('search').value,
        from_date: document.getElementById('from_date').value,
        to_date: document.getElementById('to_date').value,
        mechanic_id: document.getElementById('mechanic_id').value
    });
    window.location.href = 'export_sales.php?' + params;
}

loadSales();
</script>

<script src="assets/js/sidebar-toggle.js"></script>
</body>
</html>