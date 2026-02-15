<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$branchId = $_SESSION['branch_id'];

if (isset($_GET['action']) && $_GET['action'] == 'fetch') {
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $searchTerm = "%$search%";
    
    // JOIN to get branch_name
$query = "SELECT u.*, b.branch_name 
          FROM users u 
          LEFT JOIN branches b ON u.branch_id = b.branch_id 
          WHERE (u.username LIKE ? OR u.fullname LIKE ?) 
          AND u.archived_at IS NOT NULL AND u.branch_id = ?
          ORDER BY u.created_at DESC 
          LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssiii", $searchTerm, $searchTerm, $branchId, $limit, $offset);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM users u LEFT JOIN branches b ON u.branch_id = b.branch_id  WHERE (username LIKE ? OR fullname LIKE ?) AND u.archived_at IS NOT NULL AND u.branch_id = ?";
    $cStmt = $conn->prepare($countQuery);
    $cStmt->bind_param("ssi", $searchTerm, $searchTerm, $branchId);
    $cStmt->execute();
    $totalUsers = $cStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalUsers / $limit);

    // 1. Output Table Rows
    if (count($users) > 0) {
        foreach ($users as $user) {
            $userData = htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8');
            echo "<tr>
                    <td><strong>".htmlspecialchars($user['fullname'] ?: 'N/A')."</strong></td>
                    <td>".htmlspecialchars($user['username'])."</td>
                    <td>".htmlspecialchars($user['branch_name'] ?: 'N/A')."</td>
                    <td>".htmlspecialchars($user['contact_number'] ?: 'N/A')."</td>
                    <td>".htmlspecialchars($user['email'] ?: 'N/A')."</td>
                    <td>".htmlspecialchars($user['role'])."</td>
                         <td>".date('M d Y', strtotime($user['archived_at']))."</td>
                    <td>
                        <button onclick='restoreUser(".$user['user_id'].")' class='btn-restore'>  <i class='fa-solid fa-rotate-left'></i>Restore</button>
                        <button onclick='deleteUser(".$user['user_id'].")'  class='btn-archive'><i class='fa fa-archive'></i>
                   Delete
                </button>
                    </td>
                  </tr>";
        }
    } else {
        echo 
        "<tr> <td colspan='8' style='text-align:center; padding:40px;'>
                    <img 
                        src='images/noUser.png' 
                        alt='No User found'
                        style='max-width:20%; height:auto; max-height:300px; display:block; margin:0 auto;'
                    >
                </td></tr>";
    }

    echo "||SEP||"; 

    // 2. Output Pagination (ONLY if more than 1 page exists)
    if ($totalPages > 1) {
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo "<a href='javascript:void(0)' onclick='loadUsers($i)' class='$active'>$i</a>";
        }
    }
    exit;
}