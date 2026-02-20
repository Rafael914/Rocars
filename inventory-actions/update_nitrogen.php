<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

$nitrogenId   = (int)($_POST['nitrogen_id'] ?? 0);
$productName  = $_POST['product_name'] ?? '';
$price        = (float)($_POST['price'] ?? 0);
$cost         = (float)($_POST['cost'] ?? 0);
$quantity     = (int)($_POST['quantity'] ?? 0);
$criticalStock = isset($_POST['critical']) ? (int)$_POST['critical'] : 0;
$nitrogen_percentage = (float)($_POST['nitrogen_percentage'] ?? 0);
$input_date   = $_POST['input_date'] ?? '';
$type_of_vehicle = $_POST['type_of_vehicle'] ?? '';
$branchId     = $_SESSION['branch_id'] ?? 1;
$user_id      = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare("SELECT product_id FROM nitrogen_details WHERE nitrogen_id = ?");
$stmt->bind_param("i", $nitrogenId);
$stmt->execute();
$stmt->bind_result($productId);
$stmt->fetch();
$stmt->close();

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        p.product_name, p.price, p.cost, p.critical_stock_level,
        i.quantity,
        n.nitrogen_percentage, n.input_date, n.type_of_vehicle
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id
    JOIN nitrogen_details n ON p.product_id = n.product_id
    WHERE p.product_id = ? AND i.branch_id = ?
");
$stmt->bind_param("ii", $productId, $branchId);
$stmt->execute();
$result = $stmt->get_result();
$old = $result->fetch_assoc();
$stmt->close();

$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare("UPDATE nitrogen_details SET nitrogen_percentage = ?, input_date = ?, type_of_vehicle = ? WHERE nitrogen_id = ?");
    $stmt1->bind_param("dssi", $nitrogen_percentage, $input_date, $type_of_vehicle, $nitrogenId);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $conn->prepare("UPDATE products SET product_name = ?, price = ?, cost = ?, critical_stock_level = ? WHERE product_id = ?");
    $stmt2->bind_param("sddii", $productName, $price, $cost, $criticalStock, $productId);
    $stmt2->execute();
    $stmt2->close();

    $stmt3 = $conn->prepare("UPDATE inventory SET quantity = ? WHERE product_id = ? AND branch_id = ?");
    $stmt3->bind_param("iii", $quantity, $productId, $branchId);
    $stmt3->execute();
    $stmt3->close();

    $changes = [];
    if ($old['nitrogen_percentage'] != $nitrogen_percentage) $changes[] = "Nitrogen Percentage '{$old['nitrogen_percentage']}' → '$nitrogen_percentage'";
    if ($old['input_date'] != $input_date) $changes[] = "Input Date '{$old['input_date']}' → '$input_date'";
    if ($old['type_of_vehicle'] != $type_of_vehicle) $changes[] = "Type of Vehicle '{$old['type_of_vehicle']}' → '$type_of_vehicle'";
    if ($old['product_name'] != $productName) $changes[] = "Product Name '{$old['product_name']}' → '$productName'";
    if ($old['price'] != $price) $changes[] = "Price {$old['price']} → $price";
    if ($old['cost'] != $cost) $changes[] = "Cost {$old['cost']} → $cost";
    if ($old['critical_stock_level'] != $criticalStock) $changes[] = "Critical {$old['critical_stock_level']} → $criticalStock";
    if ($old['quantity'] != $quantity) $changes[] = "Qty {$old['quantity']} → $quantity";

    if (!empty($changes)) {
        $logMessage = "Updated product '$productName': " . implode(', ', $changes);
        auditLog($conn, $user_id, 'UPDATE', 'products', $productId, $logMessage);
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
