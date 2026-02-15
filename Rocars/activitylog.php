<?php
// Prevent accidental text output from breaking JSON
ob_start(); 
header('Content-Type: application/json');

// Error Reporting (Logs to server, doesn't echo to screen)
error_reporting(E_ALL);
ini_set('display_errors', 0); 

require_once 'includes/config.php';
require_once 'includes/auth.php';

try {

    if (!isset($_SESSION['branch_id'])) {
        throw new Exception("Session expired or Branch ID not found.");
    }

    $branchId = $_SESSION['branch_id'];
    $search = $_GET['search'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $recordsPerPage = 10;
    $offset = ($page - 1) * $recordsPerPage;


    $where = "WHERE u.branch_id = ?"; 
    $params = [$branchId];
    $types = "i"; 

    if (!empty($search)) {
        $where .= " AND (a.description LIKE ? OR u.username LIKE ? OR a.table_name LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like);
        $types .= "sss";
    }

    // --- 3. Get Total Count ---
    $totalQuery = "SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.user_id $where";
    $stmt = $conn->prepare($totalQuery);
    
    if (!$stmt) {
        throw new Exception("Database Preparation Error: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($totalRecords);
    $stmt->fetch();
    $stmt->close();

    // --- 4. Fetch Data ---
    $dataQuery = "
        SELECT a.audit_id, u.username, a.action, a.table_name, a.record_id, a.description, a.created_at
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.user_id
        $where
        ORDER BY a.created_at DESC
        LIMIT ?, ?
    ";

    $stmt = $conn->prepare($dataQuery);
    if (!$stmt) {
        throw new Exception("Data Query Error: " . $conn->error);
    }

    $finalTypes = $types . "ii";
    $finalParams = array_merge($params, [$offset, $recordsPerPage]);

    $stmt->bind_param($finalTypes, ...$finalParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();

    // Clear buffer and send clean JSON
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'page' => $page,
        'total' => $totalRecords,
        'per_page' => $recordsPerPage,
        'data' => $data
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}