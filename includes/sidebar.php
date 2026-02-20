<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['role'] ?? 'guest'; 
$current_page = basename($_SERVER['PHP_SELF']);
$current_branch = $_SESSION['branch_id'] ?? 1;

// Fetch Branches for the Dropdown
$branch_query = "SELECT branch_id, branch_name FROM branches ORDER BY branch_name ASC";
$branch_result = $conn->query($branch_query);

// --- PERMISSIONS ---
$menu_permissions = [
    'index.php'            => ['master_admin', 'admin_staff'],
    'getProducts.php'          => ['master_admin', 'admin_staff', 'inventory_staff'],
    'saleslistIndex.php'       => ['master_admin', 'admin_staff', 'inventory_staff'],
    'inventorysearchmodal.php' => ['master_admin', 'admin_staff', 'inventory_staff'],
    'mechanicsIndex.php'       => ['master_admin', 'admin_staff'],
    'expensesIndex.php'        => ['master_admin', 'admin_staff'],
    'activitylogIndex.php'     => ['master_admin', 'admin_staff'],
    'reportsIndex.php'         => ['master_admin', 'admin_staff'],
    'userIndex.php'            => ['master_admin', 'admin_staff']
];

if (!function_exists('has_access')) {
    function has_access($page, $role, $permissions) {
        if ($role === 'master_admin') return true;
        if (!isset($permissions[$page])) return false;
        return in_array($role, $permissions[$page]);
    }
}
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="images/rocarsn.png" alt="Rocars Logo" id="logo">
    </div>

    <?php if ($user_role === 'master_admin'): ?>
    <div class="branch-selector" style="padding: 10px 20px;">
        <label style="color: #ccc; font-size: 12px; display: block; margin-bottom: 5px;">Current Branch:</label>
        <select id="sidebarBranchSelect" onchange="updateBranch(this.value)" style="width: 100%; padding: 8px; border-radius: 5px; background: #2c3e50; color: white; border: 1px solid #444;">
            <?php 
            $branch_result->data_seek(0); 
            while($branch = $branch_result->fetch_assoc()): 
            ?>
                <option value="<?= $branch['branch_id'] ?>" <?= ($current_branch == $branch['branch_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($branch['branch_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <?php endif; ?>

    <ul class="sidebar-menu">
 

        <?php if (has_access('index.php', $user_role, $menu_permissions)): ?>
        <li class="<?= ($current_page == 'index.php') ? 'active' : ''; ?>">
            <a href="index.php"><i class="fa-solid fa-border-all"></i><span>Dashboard</span></a>
        </li>
        <?php endif; ?>

        <?php if (has_access('getProducts.php', $user_role, $menu_permissions)): ?>
        <li class="<?= ($current_page == 'getProducts.php') ? 'active' : ''; ?>">
            <a href="getProducts.php"><i class="fa-solid fa-cart-shopping"></i><span>Point of Sale</span></a>
        </li>
        <?php endif; ?>

        <?php if (has_access('inventorysearchmodal.php', $user_role, $menu_permissions)): ?>
        <li class="<?= ($current_page == 'inventorysearchmodal.php') ? 'active' : ''; ?>">
            <a href="inventorysearchmodal.php"><i class="fa-solid fa-box"></i><span>Inventory</span></a>
        </li>
        <?php endif; ?>

        <?php if (has_access('saleslistIndex.php', $user_role, $menu_permissions)): ?>
        <li class="<?= ($current_page == 'saleslistIndex.php') ? 'active' : ''; ?>">
            <a href="saleslistIndex.php"><i class="fa-solid fa-clock-rotate-left"></i><span>Sales History</span></a>
        </li>
        <?php endif; ?>

        <?php if (has_access('mechanicsIndex.php', $user_role, $menu_permissions)): ?>
        <li class="<?= ($current_page == 'mechanicsIndex.php') ? 'active' : ''; ?>">
            <a href="mechanicsIndex.php"><i class="fa-solid fa-wrench"></i><span>Technician</span></a>
        </li>
        <?php endif; ?>

        <?php if (has_access('expensesIndex.php', $user_role, $menu_permissions)): ?>
        <li class="<?= ($current_page == 'expensesIndex.php') ? 'active' : ''; ?>">
            <a href="expensesIndex.php"><i class="fa-solid fa-coins"></i><span>Expenses</span></a>
        </li>
        <?php endif; ?>

        <?php if (has_access('activitylogIndex.php', $user_role, $menu_permissions)): ?>
        <li class="<?= ($current_page == 'activitylogIndex.php') ? 'active' : ''; ?>">
            <a href="activitylogIndex.php"><i class="fa-solid fa-chart-line"></i><span>Activity Log</span></a>
        </li>
        <?php endif; ?>

        <?php if (has_access('reportsIndex.php', $user_role, $menu_permissions)): ?>
        <li class="<?= ($current_page == 'reportsIndex.php') ? 'active' : ''; ?>">
            <a href="reportsIndex.php"><i class="fa-solid fa-chart-bar"></i><span>Reports</span></a>
        </li>
        <?php endif; ?>

        <?php if (has_access('userIndex.php', $user_role, $menu_permissions)): ?>
        <li class="<?= ($current_page == 'userIndex.php') ? 'active' : ''; ?>">
            <a href="userIndex.php"><i class="fa-solid fa-users"></i><span>Employee</span></a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </div>
</div>

<script>
function updateBranch(branchId) {
    fetch('update_session_branch.php?branch_id=' + branchId)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                window.location.reload();
            }
        });
}
</script>