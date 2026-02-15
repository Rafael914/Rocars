<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

// POST variables
$tireId        = (int)($_POST['tire_id'] ?? 0);
$productName   = $_POST['product_name'] ?? '';
$price         = (float)($_POST['price'] ?? 0);
$cost          = (float)($_POST['cost'] ?? 0);
$quantity      = (int)($_POST['quantity'] ?? 0);
$criticalStock = isset($_POST['critical']) ? (int)$_POST['critical'] : 0;
$brand         = $_POST['brand'] ?? '';
$size          = $_POST['size'] ?? '';
$pattern       = $_POST['pattern'] ?? '';
$made          = $_POST['made'] ?? '';
$branchId      = $_SESSION['branch_id'] ?? 1;
$user_id       = $_SESSION['user_id'] ?? 0;

// Get product_id for the tire
$stmt = $conn->prepare("SELECT product_id FROM tire_details WHERE tire_id = ?");
$stmt->bind_param("i", $tireId);
$stmt->execute();
$stmt->bind_result($productId);
$stmt->fetch();
$stmt->close();

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Get current product data
$stmt = $conn->prepare("
    SELECT 
        p.product_name, p.price, p.cost, p.critical_stock_level,
        i.quantity,
        t.brand, t.size, t.pattern, t.made
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id
    JOIN tire_details t ON p.product_id = t.product_id
    WHERE p.product_id = ? AND i.branch_id = ?
");
$stmt->bind_param("ii", $productId, $branchId);
$stmt->execute();
$result = $stmt->get_result();
$old = $result->fetch_assoc();
$stmt->close();

$conn->begin_transaction();

try {
    // Update tire details
    $stmt1 = $conn->prepare("UPDATE tire_details SET brand = ?, size = ?, pattern = ?, made = ? WHERE tire_id = ?");
    $stmt1->bind_param("ssssi", $brand, $size, $pattern, $made, $tireId);
    $stmt1->execute();
    $stmt1->close();

    // Update product info including critical stock
    $stmt2 = $conn->prepare("UPDATE products SET product_name = ?, price = ?, cost = ?, critical_stock_level = ? WHERE product_id = ?");
    $stmt2->bind_param("sddii", $productName, $price, $cost, $criticalStock, $productId);
    $stmt2->execute();
    $stmt2->close();

    // Update inventory quantity
    $stmt3 = $conn->prepare("UPDATE inventory SET quantity = ? WHERE product_id = ? AND branch_id = ?");
    $stmt3->bind_param("iii", $quantity, $productId, $branchId);
    $stmt3->execute();
    $stmt3->close();

    // Prepare audit log
    $changes = [];
    if ($old['brand'] != $brand) $changes[] = "Brand '{$old['brand']}' → '$brand'";
    if ($old['size'] != $size) $changes[] = "Size '{$old['size']}' → '$size'";
    if ($old['pattern'] != $pattern) $changes[] = "Pattern '{$old['pattern']}' → '$pattern'";
    if ($old['made'] != $made) $changes[] = "Made '{$old['made']}' → '$made'";
    if ($old['product_name'] != $productName) $changes[] = "Product Name '{$old['product_name']}' → '$productName'";
    if ($old['price'] != $price) $changes[] = "Price {$old['price']} → $price";
    if ($old['cost'] != $cost) $changes[] = "Cost {$old['cost']} → $cost";
    if ($old['critical_stock_level'] != $criticalStock) $changes[] = "Critical {$old['critical_stock_level']} → $criticalStock";
    if ($old['quantity'] != $quantity) $changes[] = "Qty {$old['quantity']} → $quantity";

    // Write audit log if there are changes
    if (!empty($changes)) {
        $logMessage = "Updated tire '$productName': " . implode(', ', $changes);
        auditLog($conn, $user_id, 'UPDATE', 'products', $productId, $logMessage);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Tire updated successfully.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
