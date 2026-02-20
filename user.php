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

    $query = "SELECT u.*, b.branch_name 
              FROM users u 
              LEFT JOIN branches b ON u.branch_id = b.branch_id 
              WHERE (u.username LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR b.branch_name LIKE ?) 
              AND u.archived_at IS NULL AND u.branch_id = ?
              ORDER BY u.created_at DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssiii", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $branchId, $limit, $offset);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. Correct Count Query (Added JOIN so it knows what branch_name is)
    $countQuery = "SELECT COUNT(*) as total 
                   FROM users u
                   LEFT JOIN branches b ON u.branch_id = b.branch_id
                   WHERE (u.username LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR b.branch_name LIKE ?) 
                   AND u.archived_at IS NULL AND u.branch_id = ?";
    
    $cStmt = $conn->prepare($countQuery);
    $cStmt->bind_param("ssssi", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $branchId);
    $cStmt->execute();
    $totalUsers = $cStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalUsers / $limit);

    // 3. Output Table Rows
    if (count($users) > 0) {
        foreach ($users as $user) {
            echo "<tr>
                    <td><strong>".htmlspecialchars($user['fullname'] ?: 'N/A')."</strong></td>
                    <td>".htmlspecialchars($user['username'])."</td>
                    <td>".htmlspecialchars($user['branch_name'] ?: 'N/A')."</td>
                    <td>".htmlspecialchars($user['contact_number'] ?: 'N/A')."</td>
                    <td>".htmlspecialchars($user['email'] ?: 'N/A')."</td>
                    <td>".htmlspecialchars($user['role'])."</td>
                    <td>".date('M d Y', strtotime($user['created_at']))."</td>
                    <td>
                     <button onclick='EditUser(".$user['user_id'].")' class='btn-restore'>  <i class='fa-solid fa-pen-to-square'></i>Edit</button>
                        <button onclick='archiveUser(".$user['user_id'].")' class='btn-archive'>
                            <i class='fa fa-archive'></i> Archive
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

    // 4. Output Pagination
    if ($totalPages > 1) {
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo "<a href='#' class='page-link $active' data-page='$i'>$i</a>";
        }
    }
    exit;
}