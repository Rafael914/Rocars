<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - Expenses Tracker</title>
    
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>

        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            background-color: #f4f7f6;
            font-family: 'Poppins', sans-serif;

        }

        .actions{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:2%;
            margin-bottom:2%;
        }
        
        .main-content {
            flex-grow: 1;
           
            transition: all 0.3s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content.active {
            margin-left: 80px;
        }

        /* --- TOPBAR FIXES --- */
        /* Overriding topbar.css to ensure it fits inside main-content */
        .topbar {
            width: 100% !important; 
            left: 0 !important;
            position: relative !important; /* Not fixed anymore so it stays in flow */
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 25px;
            background-color: #1a1a1a;
            color: #ffffff;
            z-index: 10;
        }

        .topbar h1 {
            font-size: 20px;
            margin: 0;
            flex-grow: 1;
            margin-left: 20px;
        }

        .toggle {
            cursor: pointer;
            font-size: 20px;
        }
#exportBtn {
    display: inline-block;       
    text-align: center;        
    padding: 10px 20px;       
    color: #fff;                
    background-color: #2b2b2b;  
    border-radius: 5px;        
    text-decoration: none;       
    cursor: pointer;     
            
}

#exportBtn:hover {
    background-color: #444;      /* hover effect */
}

        /* --- PAGE CONTENT --- */
        .content-body {
            padding: 30px;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        /* --- SEARCH BOX --- */
        #search { 
            padding: 15px; 
            width: 100%; 
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            color: #333; /* Dark text for input */
            justify-content:center;
            align-items:center;
            display:flex;
        }

        /* --- TABLE STYLING (The Text Color Fix) --- */
        .table-container {
            background: #000000ff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table { 
            border-collapse: collapse; 
            width: 100%; 
            background-color: #fff;
        }
        
        table th, table td { 
            border-bottom: 1px solid #eee; 
            padding: 15px; 
            text-align: left; 
            color: #2d3436 !important; 
            font-size: 14px;
        }

        table th { 
            background-color: #2b2b2b; 
            font-weight: 600;
            color: #ffffffff !important;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        table tr:hover {
            background-color: #f1f2f6;
        }

        /* --- PAGINATION --- */
        .pagination { 
            align-items:center;
            justify-content:center;
            margin-top: 20px; 
            display: flex;
            gap: 5px;
        }
        .pagination button { 
            padding: 8px 16px; 
            border: 1px solid #dfe6e9;
            background: white;
            cursor: pointer;
            border-radius: 4px;
            transition: 0.2s;
        }
        .pagination button:hover { background-color: #74b9ff; color: white; }
        .pagination button:disabled { 
            background-color: #ecf0f1; 
            color: #bdc3c7; 
            cursor: not-allowed; 
        }

        

    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">

    <div class="topbar">
        <i class="fa-solid fa-bars toggle" id="toggleBtn" aria-label="Toggle sidebar"></i>
        <h1>Activity Log</h1>
        <div class="logo">
            <img src="images/rocarsn.png" alt="Logo" class="logo-img" style="height: 40px;">
        </div>
    </div>

    <div class="content-body">
    
    <div class="actions">
     <input type="text"  id="search" placeholder="Search by username, table, description..." autocomplete="off">
     <a id="exportBtn" href="#">Export</a>
    </div>

        <div class="table-container">
            <table id="auditTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>Description</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="7">Loading logs...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="pagination"></div>
    </div>

</div>

<script>
let currentPage = 1;

function fetchAuditLogs(page = 1) {
    const search = document.getElementById('search').value;

    fetch(`activityLog.php?search=${encodeURIComponent(search)}&page=${page}`)
        .then(res => res.json())
        .then(res => {
            const tbody = document.querySelector('#auditTable tbody');
            tbody.innerHTML = '';



            if (!res.data || res.data.length === 0) {
                tbody.innerHTML = `<tr> <td colspan="16" style="text-align:center; padding:40px;">
                    <img 
                        src="images/noRecords.png" 
                        alt="No Records found"
                        style="max-width:40%; height:auto; max-height:300px; display:block; margin:0 auto;"
                    >
                </td></tr>`;
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            res.data.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.audit_id}</td>
                    <td><strong>${row.username || 'N/A'}</strong></td>
                    <td>${row.action}</td>
                    <td>${row.table_name}</td>
                    <td>${row.record_id}</td>
                    <td>${row.description}</td>
                    <td>${row.created_at}</td>
                `;
                tbody.appendChild(tr);
            });

 const totalPages = Math.ceil(res.total / res.per_page);
const paginationDiv = document.getElementById('pagination');
paginationDiv.innerHTML = '';

// PREV BUTTON
const prevBtn = document.createElement('button');
prevBtn.textContent = '« Prev';
prevBtn.disabled = res.page === 1;
prevBtn.addEventListener('click', () => {
    if (res.page > 1) {
        currentPage = res.page - 1;
        fetchAuditLogs(currentPage);
    }
});
paginationDiv.appendChild(prevBtn);


for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement('button');
    btn.textContent = i;
    if (i === res.page) {
        btn.disabled = true;
        btn.style.fontWeight = 'bold';
    }
    btn.addEventListener('click', () => {
        currentPage = i;
        fetchAuditLogs(i);
    });
    paginationDiv.appendChild(btn);
}


const nextBtn = document.createElement('button');
nextBtn.textContent = 'Next »';
nextBtn.disabled = res.page === totalPages;
nextBtn.addEventListener('click', () => {
    if (res.page < totalPages) {
        currentPage = res.page + 1;
        fetchAuditLogs(currentPage);
    }
});
paginationDiv.appendChild(nextBtn);
        })
        .catch(err => {
            console.error("Error fetching logs:", err);
            document.querySelector('#auditTable tbody').innerHTML = '<tr><td colspan="7">Error loading data.</td></tr>';
        });
}

// Search event
document.getElementById('search').addEventListener('input', () => {
    currentPage = 1;
    fetchAuditLogs();
});


const toggleBtn = document.getElementById('toggleBtn');
const mainContent = document.getElementById('mainContent');

toggleBtn.addEventListener('click', () => {
    mainContent.classList.toggle('active');

});


document.getElementById('exportBtn').addEventListener('click', function(e){
    e.preventDefault();
    const search = document.getElementById('search').value;
    window.location.href = `actions/export_audit.php?search=${encodeURIComponent(search)}`;
        setTimeout(function(){
        window.location.reload();
    }, 3000);
});



fetchAuditLogs();
</script>

<script src="assets/js/sidebar-toggle.js"></script>

</body>
</html>