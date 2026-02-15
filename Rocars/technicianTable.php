<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
$branchId = $_SESSION['branch_id'];
if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $searchTerm = "%$search%";

    // 1. Get Data
    $query = "SELECT m.*, b.branch_name 
              FROM mechanics m
              LEFT JOIN branches b ON m.branch_id = b.branch_id
              WHERE (m.mechanic_name LIKE ? OR m.contact_number LIKE ? OR b.branch_name LIKE ? OR m.email LIKE ?) 
              AND m.archived_at IS NULL AND m.branch_id = ?
              ORDER BY m.mechanic_id DESC
              LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssiii", $searchTerm, $searchTerm,$searchTerm,$searchTerm, $branchId, $limit, $offset);
    $stmt->execute();
    $mechanics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$countQuery = "
    SELECT COUNT(*) AS total
    FROM mechanics m
    LEFT JOIN branches b ON m.branch_id = b.branch_id
    WHERE (m.mechanic_name LIKE ? 
           OR m.contact_number LIKE ? 
           OR b.branch_name LIKE ? OR m.email LIKE ?)
      AND m.archived_at IS NULL
      AND m.branch_id = ?
";
    $cStmt = $conn->prepare($countQuery);
    $cStmt->bind_param(
    "ssssi",
    $searchTerm,
    $searchTerm,
    $searchTerm,
    $searchTerm,
    $branchId
);
    $cStmt->execute();
    $totalRows = $cStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $limit);

    // 3. Build Table HTML
    $tableHTML = '';
    if (count($mechanics) > 0) {
        foreach ($mechanics as $m) {
            $created = isset($m['created_at']) ? date('M d, Y', strtotime($m['created_at'])) : 'N/A';
            
            $tableHTML .= '<tr>
                <td>' . htmlspecialchars($m['mechanic_name']) . '</td>
                <td>' . htmlspecialchars($m['contact_number'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($m['branch_name'] ?? 'N/A') . '</td>
                 <td>' . htmlspecialchars($m['email'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($created) . '</td>
                <td>
                  <button onclick="EditMechanic(' . (int)$m['mechanic_id'] . ')" class="btn-restore">  <i class="fa-solid fa-pen-to-square"></i>Edit</button>
                    <button onclick="archiveMechanic(' . (int)$m['mechanic_id'] . ')" class="btn-archive">
                        <i class="fa fa-archive"></i> Archive
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

    $paginationHTML = '';
    if ($totalPages > 1) {
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            $paginationHTML .= '<button class="page-btn ' . $active . '" data-page="' . $i . '">' . $i . '</button>';
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'table' => $tableHTML,
        'pagination' => $paginationHTML
    ]);
    exit;
}