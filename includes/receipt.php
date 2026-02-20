<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ob_start();

require_once 'config.php';
require_once 'auth.php';

/**
 * Send JSON response and exit
 */
function sendResponse(bool $success, array $data = [], string $error = null) {
    if (ob_get_level() > 0) ob_end_clean();
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

/**
 * Error handlers
 */
set_error_handler(function($severity, $message, $file, $line) {
    sendResponse(false, [], "PHP Error: $message in $file on line $line");
});
set_exception_handler(function($e) {
    sendResponse(false, [], "Exception: " . $e->getMessage());
});

try {

    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
        strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false
    ) {
        sendResponse(false, [], 'Invalid request');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        sendResponse(false, [], 'Invalid JSON payload');
    }

    // Required
    $si_number   = trim($data['si_number'] ?? '');
    $mechanic_id = (int)($data['mechanic_id'] ?? 0);

    if ($si_number === '' || $mechanic_id === 0) {
        sendResponse(false, [], 'SI Number and Mechanic are required');
    }

    // Optional / Common
    $customer_name = trim($data['customer'] ?? '');
    $vehicle       = trim($data['vehicle'] ?? '');
    $plate_no      = trim($data['plate'] ?? '');
    $odometer      = trim($data['odometer'] ?? '');
    $cp_number     = trim($data['phone'] ?? '');
    $remarks       = trim($data['remarks'] ?? '');
    $discrepancy   = trim($data['discrepancy'] ?? '');
    $front_inc     = (float)($data['frontline'] ?? 0);
    $skill_inc     = (float)($data['skilled'] ?? 0);
    $branch_id     = (int)($data['branch_id'] ?? $_SESSION['branch_id'] ?? 0);
    $payment_method = $data['paymentMethod'] ?? 'Cash';
    $product_description = trim($data['productDescription'] ?? '');

    // Flags
    $hasCart    = !empty($data['cart']) && is_array($data['cart']);
    $hasService = !empty($data['serviceDescription']) && floatval($data['serviceCost']) > 0;

    if (!$hasCart && !$hasService) {
        sendResponse(false, [], 'No items to save');
    }

    // Begin transaction
    $conn->begin_transaction();

    // Prepare sales insert
    $sql = "
        INSERT INTO sales (
            si_number, date, mechanic_id, category, quantity, item_name,
            customer_name, vehicle, plate_no, odometer, cp_number,
            total_amount, total_cost, gross_profit,
            remarks, discrepancy, front_incentive, skill_incentive,
            branch_id, payment_method, description
        )
        VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    /**
     * INSERT PRODUCTS & DEDUCT INVENTORY
     */
    if ($hasCart) {
        foreach ($data['cart'] as $item) {

            $category = $item['type'] ?? 'Product';
            $item_name = $item['name'] ?? '';
            $quantity = (int)($item['quantity'] ?? 0);
            $price = (float)($item['price'] ?? 0);
            $cost = (float)($item['cost'] ?? 0);
            $inventory_id = (int)($item['id'] ?? 0); // <- make sure cart items have 'id' matching inventory_id

            if ($item_name === '' || $quantity <= 0 || $inventory_id === 0) {
                continue;
            }

            // 1️⃣ Check if enough stock
            $res = $conn->query("SELECT quantity FROM inventory WHERE inventory_id = $inventory_id FOR UPDATE");
            $row = $res->fetch_assoc();
            if (!$row || $row['quantity'] < $quantity) {
                throw new Exception("Not enough stock for product: $item_name");
            }

            // 2️⃣ Deduct stock
            $stmtInv = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE inventory_id = ?");
            $stmtInv->bind_param("ii", $quantity, $inventory_id);
            if (!$stmtInv->execute()) {
                throw new Exception("Failed to update inventory for $item_name: {$stmtInv->error}");
            }

            // 3️⃣ Insert into sales
            $total_amount = $price * $quantity;
            $total_cost   = $cost * $quantity;
            $gross_profit = $total_amount - $total_cost;
            $description = $product_description;

            $stmt->bind_param(
                "sisissssssdddssddiss",
                $si_number,
                $mechanic_id,
                $category,
                $quantity,
                $item_name,
                $customer_name,
                $vehicle,
                $plate_no,
                $odometer,
                $cp_number,
                $total_amount,
                $total_cost,
                $gross_profit,
                $remarks,
                $discrepancy,
                $front_inc,
                $skill_inc,
                $branch_id,
                $payment_method,
                $description
            );

            if (!$stmt->execute()) {
                throw new Exception("Product insert failed: {$stmt->error}");
            }
        }
    }

    /**
     * INSERT SERVICE
     */
    if ($hasService) {
        $service_desc = trim($data['serviceDescription']);
        $service_cost = (float)$data['serviceCost'];

        $service_item_name = 'Service';
        $service_category  = 'Service';
        $service_qty = 1;

        $total_amount = $service_cost;
        $total_cost   = $service_cost;
        $gross_profit = 0;

        $stmt->bind_param(
            "sisissssssdddssddiss",
            $si_number,
            $mechanic_id,
            $service_category,
            $service_qty,
            $service_desc,
            $customer_name,
            $vehicle,
            $plate_no,
            $odometer,
            $cp_number,
            $total_amount,
            $total_cost,
            $gross_profit,
            $remarks,
            $discrepancy,
            $front_inc,
            $skill_inc,
            $branch_id,
            $payment_method,
            $product_description
        );

        if (!$stmt->execute()) {
            throw new Exception("Service insert failed: {$stmt->error}");
        }
    }

    $stmt->close();
    $conn->commit();

    sendResponse(true, [
        'message' => 'Sale saved successfully',
        'si_number' => $si_number
    ]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    sendResponse(false, [], $e->getMessage());
}
