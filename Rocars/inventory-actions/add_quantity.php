<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

$user_id = $_SESSION['user_id'] ?? 0;

$inventory_id = intval($_POST['inventory_id']);
$add_qty      = intval($_POST['add_qty']);

if ($add_qty <= 0) {
    exit('Invalid quantity');
}

/* OPTIONAL: Get old quantity for description */
$oldQty = 0;
$getStmt = $conn->prepare("
    SELECT quantity 
    FROM inventory 
    WHERE inventory_id = ?
");
$getStmt->bind_param("i", $inventory_id);
$getStmt->execute();
$getStmt->bind_result($oldQty);
$getStmt->fetch();
$getStmt->close();

/* Update quantity */
$stmt = $conn->prepare("
    UPDATE inventory
    SET quantity = quantity + ?
    WHERE inventory_id = ?
");
$stmt->bind_param("ii", $add_qty, $inventory_id);

if ($stmt->execute()) {

    $newQty = $oldQty + $add_qty;

    /* ✅ AUDIT LOG */
    auditLog(
        $conn,
        $user_id,
        'UPDATE',
        'inventory',
        $inventory_id,
        "Stock increased by {$add_qty}. Old: {$oldQty}, New: {$newQty}"
    );

    echo 'success';

} else {
    echo 'error';
}

$stmt->close();
