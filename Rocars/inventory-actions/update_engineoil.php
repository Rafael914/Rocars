<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

$engineoilId   = (int)($_POST['oil_id'] ?? 0);
$productName   = $_POST['product_name'] ?? '';
$price         = (float)($_POST['price'] ?? 0);
$cost          = (float)($_POST['cost'] ?? 0);
$quantity      = (int)($_POST['quantity'] ?? 0);
$criticalStock = isset($_POST['critical']) ? (int)$_POST['critical'] : 0;
$brand         = $_POST['brand'] ?? '';
$oiltype       = $_POST['oiltype'] ?? '';
$capacity      = $_POST['capacity'] ?? '';

$branchId = $_SESSION['branch_id'] ?? 1;
$userId   = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare("SELECT product_id FROM engineoil_details WHERE oil_id = ?");
$stmt->bind_param("i", $engineoilId);
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
        e.brand, e.oiltype, e.capacity
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id AND i.branch_id = ?
    JOIN engineoil_details e ON p.product_id = e.product_id
    WHERE p.product_id = ?
");
$stmt->bind_param("ii", $branchId, $productId);
$stmt->execute();
$result = $stmt->get_result();
$old = $result->fetch_assoc();
$stmt->close();

$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare("UPDATE engineoil_details SET brand = ?, oiltype = ?, capacity = ? WHERE oil_id = ?");
    $stmt1->bind_param("sssi", $brand, $oiltype, $capacity, $engineoilId);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $conn->prepare("UPDATE products SET product_name = ?, price = ?, cost = ?, critical_stock_level = ? WHERE product_id = ?");
    $stmt2->bind_param("sdiii", $productName, $price, $cost, $criticalStock, $productId);
    $stmt2->execute();
    $stmt2->close();

    $stmt3 = $conn->prepare("UPDATE inventory SET quantity = ? WHERE product_id = ? AND branch_id = ?");
    $stmt3->bind_param("iii", $quantity, $productId, $branchId);
    $stmt3->execute();
    $stmt3->close();

    $changes = [];
    if ($old['brand'] != $brand) $changes[] = "Brand '{$old['brand']}' → '$brand'";
    if ($old['oiltype'] != $oiltype) $changes[] = "Oil Type '{$old['oiltype']}' → '$oiltype'";
    if ($old['capacity'] != $capacity) $changes[] = "Capacity '{$old['capacity']}' → '$capacity'";
    if ($old['price'] != $price) $changes[] = "Price {$old['price']} → $price";
    if ($old['cost'] != $cost) $changes[] = "Cost {$old['cost']} → $cost";
    if ($old['quantity'] != $quantity) $changes[] = "Qty {$old['quantity']} → $quantity";
    if ($old['critical_stock_level'] != $criticalStock) $changes[] = "Critical {$old['critical_stock_level']} → $criticalStock";
    if ($old['product_name'] != $productName) $changes[] = "Product Name '{$old['product_name']}' → '$productName'";

    if (!empty($changes)) {
        $logMessage = "Updated engine oil '$productName': " . implode(', ', $changes);
        auditLog($conn, $userId, 'UPDATE', 'products', $productId, $logMessage);
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
