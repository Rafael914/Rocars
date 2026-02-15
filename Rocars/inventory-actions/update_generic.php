<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

$product_id      = (int)($_POST['product_id'] ?? 0);
$product_name    = $_POST['product_name'] ?? '';
$price           = (float)($_POST['price'] ?? 0);
$cost            = (float)($_POST['cost'] ?? 0);
$quantity        = (int)($_POST['quantity'] ?? 0);
$criticalStock   = isset($_POST['critical']) ? (int)$_POST['critical'] : 0;
$detail1         = $_POST['detail1'] ?? '';
$detail2         = $_POST['detail2'] ?? '';
$detail3         = $_POST['detail3'] ?? '';
$detail4         = $_POST['detail4'] ?? '';
$detail5         = $_POST['detail5'] ?? '';
$detail6         = $_POST['detail6'] ?? '';
$branchId        = $_SESSION['branch_id'] ?? 0;
$user_id         = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT p.product_name, p.price, p.cost, p.critical_stock_level,
           i.quantity,
           p.detail1, p.detail2, p.detail3, p.detail4, p.detail5, p.detail6
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id
    WHERE p.product_id = ? AND i.branch_id = ?
");
$stmt->bind_param("ii", $product_id, $branchId);
$stmt->execute();
$result = $stmt->get_result();
$old = $result->fetch_assoc();
$stmt->close();

if (!$old) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare("
        UPDATE products
        SET product_name=?, price=?, cost=?, critical_stock_level=?,
            detail1=?, detail2=?, detail3=?, detail4=?, detail5=?, detail6=?
        WHERE product_id=?
    ");
    $stmt1->bind_param(
        "sddisssssssi",
        $product_name, $price, $cost, $criticalStock,
        $detail1, $detail2, $detail3, $detail4, $detail5, $detail6,
        $product_id
    );
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $conn->prepare("UPDATE inventory SET quantity = ? WHERE product_id = ? AND branch_id = ?");
    $stmt2->bind_param("iii", $quantity, $product_id, $branchId);
    $stmt2->execute();
    $stmt2->close();

    $changes = [];
    if ($old['product_name'] != $product_name) $changes[] = "Product Name '{$old['product_name']}' → '$product_name'";
    if ($old['price'] != $price) $changes[] = "Price {$old['price']} → $price";
    if ($old['cost'] != $cost) $changes[] = "Cost {$old['cost']} → $cost";
    if ($old['critical_stock_level'] != $criticalStock) $changes[] = "Critical {$old['critical_stock_level']} → $criticalStock";
    if ($old['quantity'] != $quantity) $changes[] = "Qty {$old['quantity']} → $quantity";
    if ($old['detail1'] != $detail1) $changes[] = "Detail1 '{$old['detail1']}' → '$detail1'";
    if ($old['detail2'] != $detail2) $changes[] = "Detail2 '{$old['detail2']}' → '$detail2'";
    if ($old['detail3'] != $detail3) $changes[] = "Detail3 '{$old['detail3']}' → '$detail3'";
    if ($old['detail4'] != $detail4) $changes[] = "Detail4 '{$old['detail4']}' → '$detail4'";
    if ($old['detail5'] != $detail5) $changes[] = "Detail5 '{$old['detail5']}' → '$detail5'";
    if ($old['detail6'] != $detail6) $changes[] = "Detail6 '{$old['detail6']}' → '$detail6'";

    if (!empty($changes)) {
        $logMessage = "Updated product '$product_name': " . implode(', ', $changes);
        auditLog($conn, $user_id, 'UPDATE', 'products', $product_id, $logMessage);
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
