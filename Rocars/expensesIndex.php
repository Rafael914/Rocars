<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';


global $conn;


$dateRes = $conn->query("SELECT MIN(date) AS min_date, MAX(date) AS max_date FROM expenses");
$dateRow = $dateRes->fetch_assoc();
$minDate = $dateRow['min_date'] ?? '';
$maxDate = $dateRow['max_date'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Expenses Tracker</title>

<link rel="stylesheet" href="assets/css/sidebar.css">
<link rel="stylesheet" href="assets/css/topbar.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/expensesIndex.css";
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <div class="topbar">
        <i class="fa-solid fa-bars toggle" id="toggleBtn" aria-label="Toggle sidebar"></i>
        <h1>Expenses Tracker</h1>
        <div class="logo">
            <img src="images/rocarsn.png" alt="Logo" class="logo-img">
        </div>
    </div>


    <div class="filter-container">
        <div class="filter-row">

            <div class="filter-group">
                <label for="search">Search Records</label>
                <input type="text" id="search" placeholder="Search expense..." autocomplete="off">
            </div>

            <div class="filter-group">
                <label for="from_date">From Date</label>
                <input type="date" id="from_date" min="<?= $minDate ?>" max="<?= $maxDate ?>">
            </div>

            <div class="filter-group">
                <label for="to_date">To Date</label>
                <input type="date" id="to_date" min="<?= $minDate ?>" max="<?= $maxDate ?>">
            </div>

            <div class="button-group">
                <button class="btn btn-gray" onclick="setToday()">Today</button>
                <button class="btn btn-gray" onclick="setThisWeek()">This Week</button>
                <button class="btn btn-gray" onclick="setThisMonth()">This Month</button>
                <button class="btn btn-gray" onclick="setLastMonth()">Last Month</button>
            
             
                <button class="btn btn-secondary btn-clear" onclick="clearFilters()">Clear Filters</button>
                   <button class="btn btn-primary btn-excel" onclick="exportExcel()">Export to Excel</button>
                   <button onclick="openExpenseModal()" class="btn addExpense">Add Expense</button>
            </div>
        </div>
    </div>
    
    <div class="summary-box">
        <h3>TOTAL EXPENSES</h3>
        <p id="sumTotal">₱0.00</p>
    </div>
    <div id="expenseTable">Loading expenses...</div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEdit()">&times;</span>
            <h3>Edit Expense</h3>

            <form id="editForm">
                <input type="hidden" id="edit_id" name="expense_id">

                <label>Date</label>
                <input type="date" id="edit_date" name="date"><br>

                <label>Amount</label>
                <input type="number" id="edit_amount" name="amount" step="0.01"><br>

                <label>Details</label>
                <input type="text" id="edit_details" name="details"><br>

                <label>CA</label>
                <input type="number" id="edit_ca" name="ca" step="0.01"><br>

                <label>Receipt Status</label>
                <input type="text" id="edit_category" name="category"><br>

                <label>Classification</label>
                <input type="text" id="edit_classification" name="classification"><br>

                <label>Remarks</label>
                <input type="text" id="edit_remarks" name="remarks"><br>

                <label>Code</label>
                <input type="text" id="edit_code" name="code"><br>

                <button type="submit" id="saveEditBtn">Save Changes</button>
            </form>
        </div>
    </div>


<div id="messageBox" 
     class="hidden" 
     style="position: fixed; 
            bottom: 30px; 
            right: 30px; 
            z-index: 9999; 
            min-width: 300px; 
            max-width: 450px; 
            padding: 16px 20px; 
            border-radius: 12px; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3); 
            font-family: 'Poppins', sans-serif; 
            font-size: 14px; 
            display: none; 
            align-items: center; 
            gap: 12px; 
            background-color: #1e1e1e; 
            color: #ffffff;
            border: 1px solid #333;
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
</div>



<div id="expenseModal" class="modal-overlay">
  <div class="modal-box">
    <span class="close-btn" onclick="closeExpenseModal()">&times;</span>

    <h2>Add Expense</h2>

    <form id="expenseForm">
      <input type="hidden" name="branch_id" value="1">

      <label>Amount</label>
      <input type="number" step="0.01" name="amount" required>

      <label>Details</label>
      <textarea name="details"></textarea>

      <label>CA</label>
      <input type="number" step="0.01" name="ca">

      <label>Receipt Status</label>
      <input type="text" name="category">

      <label>Classification</label>
      <input type="text" name="classification">

      <label>Remarks</label>
      <textarea name="remarks"></textarea>

      <label>Code</label>
      <input type="text" name="code" style="margin-bottom:4px;">

      <button type="submit">Save Expense</button>
    </form>
  </div>
</div>

    </div>

    



<script>
let timer;

const messageBox = document.getElementById('messageBox'); 


function showMessage(msg, type = 'success') {
    const baseClass = "p-3 mb-4 rounded-lg font-medium ";
    
    if (!messageBox) {
        console.error("messageBox element not found.");
        return; 
    }

    if (type === 'success') {
        messageBox.className = baseClass + "bg-green-100 text-green-700 border border-green-400";
    } else if (type === 'error') {
        messageBox.className = baseClass + "bg-red-100 text-red-700 border border-red-400";
    } else {
        messageBox.className = baseClass + "bg-blue-100 text-blue-700 border border-blue-400";
    }

    messageBox.innerText = msg;
    messageBox.classList.remove('hidden'); 
    messageBox.style.display = 'block'; 

    setTimeout(() => {
        messageBox.style.display = 'none'; 
    }, 5000);
}

function formatDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth()+1).padStart(2,'0');
    const d = String(date.getDate()).padStart(2,'0');
    return `${y}-${m}-${d}`;
}


function loadExpenses(page=1) {
    const search = document.getElementById('search').value;
    const from = document.getElementById('from_date').value;
    const to = document.getElementById('to_date').value;

    document.getElementById("expenseTable").innerHTML = '<div class="p-4 text-center text-gray-500">Loading...</div>';

    const params = new URLSearchParams({ page, search, from_date: from, to_date: to, t: Date.now() }); 
    fetch("expenses.php?" + params)
        .then(res => res.text())
        .then(html => {
            document.getElementById("expenseTable").innerHTML = html;

            
            // Attach event listeners again
            document.querySelectorAll(".delete-btn").forEach(btn => btn.onclick = () => deleteExpense(btn.dataset.id));
            document.querySelectorAll(".editBtn").forEach(btn => btn.onclick = () => editExpense(btn.dataset.id));
            document.querySelectorAll(".page-btn").forEach(btn => btn.onclick = (e) => loadExpenses(e.target.dataset.page));
            

            loadSummary(search, from, to); 
        })
        .catch(error => console.error('Load Expenses Error:', error));
}






function loadSummary(search="", from="", to="") {
    const params = new URLSearchParams({ search, from_date: from, to_date: to });
    fetch("actions/summary.php?" + params)
        .then(res => res.json())
        .then(data => {

            const sumTotal = document.getElementById("sumTotal");
            
            if (sumTotal) {

                const totalAmount = Number(data.total) || 0;
                sumTotal.innerText = "₱" + totalAmount.toLocaleString(undefined, {minimumFractionDigits:2});
            }
            

            const sumCount = document.getElementById("sumCount");
            const sumAvg = document.getElementById("sumAvg");
            
            if (sumCount) sumCount.innerText = data.count;
            if (sumAvg) sumAvg.innerText = "₱" + Number(data.avg).toLocaleString(undefined, {minimumFractionDigits:2});
        })
        .catch(error => {
            console.error('Load Summary Error:', error);

            document.getElementById("sumTotal").innerText = "₱0.00";
        });
}


let currentEditRow = null;

function editExpense(expense_id) {
    const row = document.querySelector(`tr[data-id='${expense_id}']`);
    currentEditRow = row; 

    fetch("actions/editExpenses.php?expense_id=" + expense_id)
        .then(res => res.json())
        .then(data => {
            document.getElementById("edit_id").value = data.expense_id; 
            document.getElementById("edit_date").value = data.date;
            document.getElementById("edit_amount").value = data.amount;
            document.getElementById("edit_details").value = data.details;
            document.getElementById("edit_ca").value = data.ca;
            document.getElementById("edit_category").value = data.category;
            document.getElementById("edit_classification").value = data.classification;
            document.getElementById("edit_remarks").value = data.remarks;
            document.getElementById("edit_code").value = data.code;
            
            document.getElementById("editModal").style.display = "flex";
        })
        .catch(error => console.error('Edit Fetch Error:', error));
}
function closeEdit(){
    document.getElementById("editModal").style.display = "none";
}


function deleteExpense(expense_id) {
    
    if(window.confirm("Are you sure you want to delete this expense? This action cannot be undone.")) {
        fetch("./actions/deleteExpenses.php", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'expense_id=' + expense_id 
        })
        .then(res => res.json())
        .then(data => {
            if(data.status==="success") {
                showMessage("Expense deleted successfully.", 'success');
              
                loadExpenses(1); 
            } else {
                showMessage("Deletion Error: " + data.msg, 'error');
            }
        })
        .catch(error => {
            showMessage("A network error occurred during deletion.", 'error');
            console.error('Delete Fetch error:', error);
        });
    }
}


// Replacement for your saveEdit() function to debug the JSON error
function saveEdit() {
    const formData = new FormData(document.getElementById("editForm"));

    fetch("actions/updateExpense.php", {
        method: "POST",
        body: formData
    })
    .then(async res => {
        const text = await res.text(); // Get raw text first
        try {
            return JSON.parse(text); // Try to parse it
        } catch (err) {
            console.error("RAW SERVER RESPONSE:", text); // This shows you the ACTUAL PHP error
            throw new Error("Server sent invalid JSON. See console.");
        }
    })
    .then(data => {
        if (data.status === "success") {
            closeEdit();
            showMessage("Expense updated successfully.", 'success');
            loadExpenses(1); 
        } else {
            showMessage("Update failed: " + data.msg, 'error');
        }
    })
    .catch(error => {
        showMessage(error.message, 'error');
    });
}

document.getElementById('editForm').addEventListener('submit',function(e){
    e.preventDefault();
    saveEdit();
});

function setToday() {
    const today = new Date();
    document.getElementById('from_date').value = formatDate(today);
    document.getElementById('to_date').value = formatDate(today);
    loadExpenses(1);
}
function setThisWeek() {
    const today = new Date();
    const day = today.getDay();
    const monday = new Date(today);
    monday.setDate(today.getDate() - day + (day===0?-6:1));
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);
    document.getElementById('from_date').value = formatDate(monday);
    document.getElementById('to_date').value = formatDate(sunday);
    loadExpenses(1);
}
function setThisMonth() {
    const today = new Date();
    const first = new Date(today.getFullYear(), today.getMonth(), 1);
    const last = new Date(today.getFullYear(), today.getMonth()+1, 0);
    document.getElementById('from_date').value = formatDate(first);
    document.getElementById('to_date').value = formatDate(last);
    loadExpenses(1);
}
function setLastMonth() {
    const today = new Date();
    const first = new Date(today.getFullYear(), today.getMonth()-1, 1);
    const last = new Date(today.getFullYear(), today.getMonth(), 0);
    document.getElementById('from_date').value = formatDate(first);
    document.getElementById('to_date').value = formatDate(last);
    loadExpenses(1);
}

// Event Listeners for filters
document.getElementById('search').addEventListener('input', ()=>{
    clearTimeout(timer);
    timer = setTimeout(()=>loadExpenses(1), 500); // 500ms debounce
});

document.getElementById('from_date').addEventListener('change', ()=>loadExpenses(1));
document.getElementById('to_date').addEventListener('change', ()=>loadExpenses(1));

function clearFilters() {
    document.getElementById('search').value="";
    document.getElementById('from_date').value="";
    document.getElementById('to_date').value="";
    loadExpenses(1);
}


const modal = document.getElementById("expenseModal");
const form  = document.getElementById("expenseForm");

function openExpenseModal() {
  modal.style.display = "flex";
}

function closeExpenseModal() {
  modal.style.display = "none";
  form.reset();
}


form.addEventListener("submit", function (e) {
  e.preventDefault(); 

  const formData = new FormData(form);

  fetch("actions/addExpenses.php", {
    method: "POST",
    body: formData
  })
  .then(res => {
    // Check if the response is OK (status 200-299)
    if (!res.ok) {
      throw new Error(`HTTP error! Status: ${res.status} - ${res.statusText}`);
    }
    return res.text();
  })
  .then(text => {
    let data;
    try {
      data = JSON.parse(text);
    } catch (jsonError) {
      // If parsing fails, log the raw response and throw a custom error
      console.error("Failed to parse JSON. Raw response:", text);
      throw new Error("Server returned invalid response (not JSON). Check console for details.");
    }
    
    if (data.success) {
      alert("Expense saved!");
      loadExpenses(1); 
      form.reset();
    } else {
      alert("Error: " + (data.error || "Unknown error"));
    }
  })
  .catch(err => {
    // Log the error for debugging
    console.error("Add Expense Error:", err);
    // Show a user-friendly alert
    alert("Something went wrong: " + err.message);
  });
});

// click outside modal closes it
window.addEventListener("click", e => {


  if(e.target === editModal) closeEdit();
});

function exportExcel() {
    const search = document.getElementById('search').value;
    const from   = document.getElementById('from_date').value;
    const to     = document.getElementById('to_date').value;

    const params = new URLSearchParams({
        search: search,
        from_date: from,
        to_date: to
    });


    window.location.href = "actions/exportExpensesXLSX.php?" + params.toString();
}



loadExpenses(); // Initial load
</script>
<script src="assets/js/getProducts.js"></script>
<script src="assets/js/sidebar-toggle.js"></script>
</body>
</html>