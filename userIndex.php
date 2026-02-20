<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$branchResult = $conn->query("SELECT * FROM branches");
$branchResult2 = $conn->query("SELECT * FROM branches");
$branchResult3 = $conn->query("SELECT * FROM branches");
$branchResult4 = $conn->query("SELECT * FROM branches");


// Check if the query failed
if (!$branchResult) {
    // This will tell you exactly why the database is complaining
    die("Query failed: " . $conn->error);
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Directory | ROCARS</title>
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/userIndex.css">
</head>

<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <div class="topbar">
        <i class="fa-solid fa-bars toggle" id="toggleBtn" aria-label="Toggle sidebar"></i>
        <h1>Employee</h1>
        <div class="logo">
            <img src="images/rocarsn.png" alt="Rocars" class="logo-img">
        </div>
    </div>

    <div class="main-wrapper">
        <div class="top-row">
            <input type="text" id="autoSearch" class="search-box" placeholder="Search name or username" autocomplete="off">
            <button class="class" id="addBranch">Add Branch</button>
            <button class="class" id="createAccount">Create Account</button>
            <button class="class" id="addTechnicians">Add Technician</button>
        </div>

        <h2>Active Users</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Branch</th>
                        <th>Contact_Number</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created at</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody"></tbody>
            </table>
        </div>
        <div id="paginationControls" class="pagination"></div>

        <h2>Archived Users</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Branch</th>
                        <th>Contact_Number</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>archive at</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="archivedUserTableBody"></tbody>
            </table>
        </div>
        <div id="archivedPaginationControls" class="pagination"></div>

   
        <h2>Mechanics</h2>
        <input type="text" id="searchInput" class="searchbox" placeholder="Search mechanics..."  autocomplete="off"/>
        <div class="table-wrapper">
               
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact Number</th>
                        <th>Branch</th>
                        <th>email</th>
                       <th>created at</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="mechanicsTableBody">
                    
                </tbody>
            </table>
        </div>

        <div id="mechanicspaginationControls" class="pagination"></div>

        <div class="table-wrapper">
               
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact Number</th>
                        <th>Branch</th>
                        <th>Email</th>
                        <th>archived at</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="archivedmechanicsTableBody">
                    
                </tbody>
            </table>
        </div>

        <div id="archivemechanicspaginationControls" class="pagination"></div>

    </div>
</div>

<div class="addbranchModal" id="addbranchModal">
    <div class="addBranchContent">
        <div class="addBranchHeader">
            <h3>Add New Branch</h3>
        </div>
        <span class="close" id="closeBtn">&times;</span>
        <form id="branchForm" autocomplete="off">
            <label for="branch_name" class="labels">Branch Name</label>
            <input type="text" name="branch_name" id="branch_name" required>
            <button type="submit">Add Branch</button>
        </form>
    </div>
</div>

<div class="createAccountModal" id="createAccountModal">
    <div class="createContent">
        <div class="addcontentHeader">
            <h3>Create Account</h3>
            <span class="close" id="closeBtns">&times;</span>
        </div>

        <form id="accountForm" autocomplete="off">
            
            <div class="input-row">
                <div class="form-group">
                    <label class="labels">Full Name</label>
                    <input type="text" name="fullname" required>
                </div>
                <div class="form-group">
                    <label class="labels">Username</label>
                    <input type="text" name="username" required>
                </div>
            </div>

            <div class="input-row">
                <div class="form-group">
                    <label class="labels">Email</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label class="labels">Contact Number</label>
                    <input type="text" name="contact_number">
                </div>
            </div>

            <div class="input-row">
                <div class="form-group">
                    <label class="labels">Role</label>
                    <select name="role" required>
                        <option value="">-- Select Role --</option>
                        <option value="master_admin">Master Admin</option>
                        <option value="admin_staff">admin_staff</option>
                        <option value="inventory_staff">Inventory Staff</option>
                        <option value="cashier">Cashier</option>
                    </select>
                </div>
            <div class="form-group">
                <label class="labels">Branch</label>
                <select name="branch_id" required>
                    <option value="">-- Select Branch --</option>
                    <?php
                    // Only attempt to loop if $branchResult is a valid object
                    if ($branchResult && $branchResult->num_rows > 0):
                        $branchResult->data_seek(0);
                        while($row = $branchResult->fetch_assoc()):
                    ?>
                        <option value="<?= $row['branch_id'] ?>">
                            <?= htmlspecialchars($row['branch_name']) ?>
                        </option>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <option value="">No Branches Found</option>
                    <?php endif; ?>
                </select>
            </div>
            </div>

            <div class="input-row">
                <div class="form-group">
                    <label class="labels">Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label class="labels">Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
            </div>
            <button type="submit" class="submit-btn">Create Account</button>
        </form>
    </div>
</div>


<div class="addTechnicianModal" id="addTechnicianModal">
    <div class="addTechnicianContent">

        <div class="modalHeader">
            <span>Add New Technician</span>
            <span class="close" id="closeBtn">&times;</span>
        </div>

        <form id="technicianForm" autocomplete="off">
            <label class="labels">Branch</label>
                <select name="branch_id" required>
                    <option value="">-- Select Branch --</option>
                    <?php
                    // Only attempt to loop if $branchResult is a valid object
                    if ($branchResult2 && $branchResult2->num_rows > 0):
                        $branchResult2->data_seek(0);
                        while($row = $branchResult2->fetch_assoc()):
                    ?>
                        <option value="<?= $row['branch_id'] ?>">
                            <?= htmlspecialchars($row['branch_name']) ?>
                        </option>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <option value="">No Branches Found</option>
                    <?php endif; ?>
                </select>

            <label class="labels">Technician Name</label>
            <input type="text" name="mechanic_name" required>

            <label class="labels">Contact Number</label>
            <input type="text" name="contact_number">

            <label class="labels">Email</label>
            <input type="email" name="email">



            <button type="submit">Save Technician</button>
        </form>

    </div>
</div>



<div class="addTechnicianModal" id="editUserModal" style="display:none;">
    <div class="addTechnicianContent">
        <div class="modalHeader">
            <span>Edit User</span>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <form id="editUserForm">
            <input type="hidden" name="user_id" id="edit_user_id">
            <label>Full Name</label>
            <input type="text" name="fullname" id="edit_fullname">
            <label>Username</label>
            <input type="text" name="username" id="edit_username" required>
            <label>Email</label>
            <input type="email" name="email" id="edit_email">
            <label>Contact Number</label>
            <input type="text" name="contact_number" id="edit_contact">
            <label>Branch</label>
            <select name="branch_id" id="edit_branch" required>
                    <option value="">-- Select Branch --</option>
                    <?php
                    // Only attempt to loop if $branchResult is a valid object
                    if ($branchResult3 && $branchResult3->num_rows > 0):
                        $branchResult3->data_seek(0);
                        while($row = $branchResult3->fetch_assoc()):
                    ?>
                        <option value="<?= $row['branch_id'] ?>">
                            <?= htmlspecialchars($row['branch_name']) ?>
                        </option>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <option value="">No Branches Found</option>
                    <?php endif; ?>
                </select>
            <label>Role</label>

            <select name="role" id="edit_role" required>
                <option value="">-- Select Role --</option>
                        <option value="master_admin">Master Admin</option>
                        <option value="admin_staff">Admin_staff</option>
                        <option value="inventory_staff">Inventory Staff</option>
                        <option value="cashier">Cashier</option>
            </select>
            <button type="submit">Update User</button>
        </form>
    </div>
</div>

<div class="editMechanicModal" id="editMechanicModal" style="display:none;">
    <div class="modal-content">
        <h3>Edit Technician</h3>
        <form id="editMechanicForm">
            <input type="hidden" name="mechanic_id" id="edit_mechanic_id">
            
            <label>Name</label>
            <input type="text" name="mechanic_name" id="edit_mechanic_name" required>
             <label>Branch</label>
             <select name="branch_id" id="editMechanic_branch" required>
                    <option value="">-- Select Branch --</option>
                    <?php
                    if ($branchResult4 && $branchResult4->num_rows > 0):
                        $branchResult4->data_seek(0);
                        while($row = $branchResult4->fetch_assoc()):
                    ?>
                        <option value="<?= $row['branch_id'] ?>">
                            <?= htmlspecialchars($row['branch_name']) ?>
                        </option>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <option value="">No Branches Found</option>
                    <?php endif; ?>
                </select>

            <label>Contact Number</label>
            <input type="text" name="contact_number" id="edit_contact_number">
            
            <label>Email</label>
            <input type="email" name="email" id="editMechanic_email">

            <button type="submit">Update Technician</button>
            <button type="button" onclick="closeEditModal()">Cancel</button>
        </form>
    </div>
</div>


<div id="toast"></div>


<script src="assets/js/userIndex.js"></script>
<script src="assets/js/sidebar-toggle.js"></script>
</body>
</html>