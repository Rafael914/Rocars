<?php
// Start session if not already started for auditLog
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php'; 
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

// Prevent error pollution in JSON response
ini_set('display_errors', 0); 
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    try {
        /* 1. GET FORM DATA */
        $sales_id       = (int)($_POST['sales_id'] ?? 0);
        $si_number      = trim($_POST['si_number'] ?? '');
        $date           = $_POST['date'] ?? '';
        $mechanic_id    = (int)($_POST['mechanic_id'] ?? 0);
        $customer_name  = trim($_POST['customer_name'] ?? '');
        $vehicle        = trim($_POST['vehicle'] ?? '');
        $plate_no       = trim($_POST['plateNumber'] ?? '');
        $odometer       = trim($_POST['odometer'] ?? '');
        $cp_number      = trim($_POST['phoneNumber'] ?? '');
        $total_amount    = (float)($_POST['total_amount'] ?? 0);
        $total_cost      = (float)($_POST['totalCost'] ?? 0);
        $gross_profit    = (float)($_POST['grossProfit'] ?? 0);
        $remarks         = ($_POST['remarks'] ?? '');
        $discrepancy     = trim($_POST['discrepancy'] ?? '');
        $front_incentive = (float)($_POST['front_incentive'] ?? 0);
        $skill_incentive = (float)($_POST['skill_incentive'] ?? 0);

        // Fetch Old Data for Audit Log (fetch by sales_id to know what was clicked)
        $oldStmt = $conn->prepare("SELECT * FROM sales WHERE sales_id = ?");
        $oldStmt->bind_param("i", $sales_id);
        $oldStmt->execute();
        $oldData = $oldStmt->get_result()->fetch_assoc();
        $oldStmt->close();

        if (!$oldData) {
            throw new Exception('Sales record not found');
        }

        /* 2. UPDATE ALL ROWS WITH THE SAME SI_NUMBER */
        // We update by SI_NUMBER instead of SALES_ID so the whole group changes
        $stmt = $conn->prepare("
            UPDATE sales SET
                `date`=?, mechanic_id=?, customer_name=?, vehicle=?, plate_no=?, 
                odometer=?, cp_number=?, remarks=?, discrepancy=?
            WHERE si_number=?
        ");

        $stmt->bind_param(
            "sissssssss",
            $date, $mechanic_id, $customer_name, $vehicle, $plate_no, 
            $odometer, $cp_number, $remarks, $discrepancy, 
            $si_number
        );

        if ($stmt->execute()) {
            /* 3. LOG THE CHANGES */
            $newData = [
                'date'            => $date,
                'mechanic_id'     => $mechanic_id,
                'customer_name'   => $customer_name,
                'vehicle'         => $vehicle,
                'plate_no'        => $plate_no,
                'odometer'        => $odometer,
                'cp_number'       => $cp_number,
                'remarks'         => $remarks,
                'discrepancy'     => $discrepancy
            ];

            $changes = [];
            foreach ($newData as $key => $value) {
                if (isset($oldData[$key]) && (string)$oldData[$key] !== (string)$value) {
                    $changes[] = "$key: {$oldData[$key]} → $value";
                }
            }

            $description = empty($changes) ? 'No fields changed' : "Bulk Update (SI: $si_number): " . implode(', ', $changes);
            $userId = $_SESSION['user_id'] ?? 0;

            auditLog($conn, $userId, 'UPDATE', 'sales', $sales_id, $description);

            echo json_encode([
                'status'  => 'success',
                'message' => 'All items for SI #' . $si_number . ' updated successfully'
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>