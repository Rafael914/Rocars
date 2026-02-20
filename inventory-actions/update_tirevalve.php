<?php
header('Content-Type: application/json');

require '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

$tirevalveId = (int)($_POST['tirevalve_id'] ?? 0);
$productName = $_POST['product_name'] ?? '';
$price = (float)($_POST['price'] ?? 0);
$cost = (float)($_POST['cost'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);
$criticalStock = isset($_POST['critical']) ? (int)$_POST['critical'] : 0;
$valve_type = $_POST['valve_type'] ?? '';
$material = $_POST['material'] ?? '';
$color = $_POST['color'] ?? '';
$branchId = $_SESSION['branch_id'] ?? 1;
$user_id = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare("SELECT product_id FROM tirevalve_details WHERE tirevalve_id = ?");
$stmt->bind_param("i", $tirevalveId);
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
        t.valve AS valve_type, t.material, t.color
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id
    JOIN tirevalve_details t ON p.product_id = t.product_id
    WHERE p.product_id = ? AND i.branch_id = ?
");
$stmt->bind_param("ii", $productId, $branchId);
$stmt->execute();
$result = $stmt->get_result();
$old = $result->fetch_assoc();
$stmt->close();

$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare("UPDATE tirevalve_details SET valve = ?, material = ?, color = ? WHERE tirevalve_id = ?");
    $stmt1->bind_param("sssi", $valve_type, $material, $color, $tirevalveId);
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
    if ($old['valve_type'] != $valve_type) $changes[] = "Valve Type '{$old['valve_type']}' → '$valve_type'";
    if ($old['material'] != $material) $changes[] = "Material '{$old['material']}' → '$material'";
    if ($old['color'] != $color) $changes[] = "Color '{$old['color']}' → '$color'";
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
    echo json_encode(['success' => true, 'message' => 'Tire valve updated successfully.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
