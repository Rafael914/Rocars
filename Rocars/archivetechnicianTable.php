<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$branchId = $_SESSION['branch_id'];

if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    header('Content-Type: application/json'); // Best practice for JSON responses

    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $searchTerm = "%$search%";

    $query = "SELECT m.*, b.branch_name 
              FROM mechanics m
              LEFT JOIN branches b ON m.branch_id = b.branch_id
              WHERE (m.mechanic_name LIKE ? OR m.contact_number LIKE ? OR b.branch_name LIKE ? OR m.email LIKE ?) 
              AND m.archived_at IS NOT NULL AND m.branch_id = ?
              ORDER BY m.mechanic_id DESC
              LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);

    $stmt->bind_param("ssssiii", $searchTerm, $searchTerm, $searchTerm,$searchTerm,$branchId, $limit, $offset);
    $stmt->execute();
    $mechanics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


    $countQuery = "SELECT COUNT(*) as total 
                   FROM mechanics m
                   LEFT JOIN branches b ON m.branch_id = b.branch_id
                   WHERE (m.mechanic_name LIKE ? OR m.contact_number LIKE ? OR b.branch_name LIKE ? OR m.email LIKE ?) 
                   AND m.archived_at IS NULL AND m.branch_id = ?
                   AND m.archived_at IS NOT NULL";
                   
    $cStmt = $conn->prepare($countQuery);
    $cStmt->bind_param("ssssi", $searchTerm, $searchTerm, $searchTerm,$searchTerm,$branchId);
    $cStmt->execute();
    $totalRows = $cStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $limit);

    // 3. Table HTML
    $tableHTML = '';
    if (count($mechanics) > 0) {
        foreach ($mechanics as $m) {
            $tableHTML .= '<tr>
                <td>' . htmlspecialchars($m['mechanic_name']) . '</td>
                <td>' . htmlspecialchars($m['contact_number'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($m['branch_name'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($m['email'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($m['archived_at'] ?? 'N/A') . '</td>
                <td>
                    <button onclick="restoreTechnician(' . (int)$m['mechanic_id'] . ')" class="btn-restore">
                        <i class="fa-solid fa-rotate-left"></i> Restore
                    </button>
                    <button onclick="deleteTechnician(' . (int)$m['mechanic_id'] . ')" class="btn-archive"><i class="fa fa-archive"></i>Delete
                    </button>
                </td>
            </tr>';
        }
    } else {
             $tableHTML .= '<tr> <td colspan="6" style="text-align:center; padding:40px;">
                    <img 
                        src="images/noTechnician.png" 
                        alt="No Technician found"
                        style="max-width:20%; height:auto; max-height:300px; display:block; margin:0 auto;"
                    >
                </td></tr>';
    }

    // 4. Pagination HTML
    $paginationHTML = '';
    if ($totalPages > 1) {
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            $paginationHTML .= '<button class="page-btn ' . $active . '" data-page="' . $i . '">' . $i . '</button>';
        }
    }

    echo json_encode([
        'table' => $tableHTML,
        'pagination' => $paginationHTML
    ]);
    exit;
}