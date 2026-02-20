<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

$accessoriesId      = isset($_POST['accessories_id']) ? (int)$_POST['accessories_id'] : 0;
$productName        = $_POST['product_name'] ?? '';
$price              = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$cost               = isset($_POST['cost']) ? (float)$_POST['cost'] : 0;
$quantity           = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$typeofaccessories  = $_POST['typeofaccessories'] ?? '';
$criticalStock      = isset($_POST['critical']) ? (int)$_POST['critical'] : 0;

$branchId = $_SESSION['branch_id'] ?? 1;
$userId   = $_SESSION['user_id'] ?? 0;

if (!$accessoriesId || empty($productName)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$stmt = $conn->prepare("SELECT product_id FROM accessories_details WHERE accessories_id = ?");
$stmt->bind_param("i", $accessoriesId);
$stmt->execute();
$stmt->bind_result($productId);
$stmt->fetch();
$stmt->close();

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Product not found for this accessory.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        p.product_name, p.price, p.cost, p.critical_stock_level,
        i.quantity,
        a.typeofaccessories
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id AND i.branch_id = ?
    JOIN accessories_details a ON p.product_id = a.product_id
    WHERE p.product_id = ?
");
$stmt->bind_param("ii", $branchId, $productId);
$stmt->execute();
$result = $stmt->get_result();
$old = $result->fetch_assoc();
$stmt->close();

$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare("UPDATE accessories_details SET typeofaccessories = ? WHERE accessories_id = ?");
    $stmt1->bind_param("si", $typeofaccessories, $accessoriesId);
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
    if ($old['typeofaccessories'] != $typeofaccessories) $changes[] = "TypeOfAccessories '{$old['typeofaccessories']}' → '$typeofaccessories'";
    if ($old['price'] != $price) $changes[] = "Price {$old['price']} → $price";
    if ($old['cost'] != $cost) $changes[] = "Cost {$old['cost']} → $cost";
    if ($old['quantity'] != $quantity) $changes[] = "Qty {$old['quantity']} → $quantity";
    if ($old['critical_stock_level'] != $criticalStock) $changes[] = "Critical {$old['critical_stock_level']} → $criticalStock";
    if ($old['product_name'] != $productName) $changes[] = "Product Name '{$old['product_name']}' → '$productName'";

    if (!empty($changes)) {
        $logMessage = "Updated product '$productName': " . implode(', ', $changes);
        auditLog($conn, $userId, 'UPDATE', 'products', $productId, $logMessage);
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
