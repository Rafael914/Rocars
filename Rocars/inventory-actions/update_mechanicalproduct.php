<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

$mechanicalId  = (int)($_POST['mechanical_id'] ?? 0);
$productName   = $_POST['product_name'] ?? '';
$price         = (float)($_POST['price'] ?? 0);
$cost          = (float)($_POST['cost'] ?? 0);
$quantity      = (int)($_POST['quantity'] ?? 0);
$criticalStock = isset($_POST['critical']) ? (int)$_POST['critical'] : 0;
$part_name     = $_POST['part_name'] ?? '';
$made          = $_POST['made'] ?? '';
$model         = $_POST['model'] ?? '';
$branchId      = $_SESSION['branch_id'] ?? 1;
$user_id       = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare("SELECT product_id FROM mechanical_details WHERE mechanical_id = ?");
$stmt->bind_param("i", $mechanicalId);
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
        m.part_name, m.made, m.model
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id
    JOIN mechanical_details m ON p.product_id = m.product_id
    WHERE p.product_id = ? AND i.branch_id = ?
");
$stmt->bind_param("ii", $productId, $branchId);
$stmt->execute();
$result = $stmt->get_result();
$old = $result->fetch_assoc();
$stmt->close();

$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare("UPDATE mechanical_details SET part_name = ?, made = ?, model = ? WHERE mechanical_id = ?");
    $stmt1->bind_param("sssi", $part_name, $made, $model, $mechanicalId);
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
    if ($old['part_name'] != $part_name) $changes[] = "Part Name '{$old['part_name']}' → '$part_name'";
    if ($old['made'] != $made) $changes[] = "Made '{$old['made']}' → '$made'";
    if ($old['model'] != $model) $changes[] = "Model '{$old['model']}' → '$model'";
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
