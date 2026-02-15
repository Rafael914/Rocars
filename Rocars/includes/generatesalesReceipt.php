<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

/* VALIDATE SI NUMBER */
if (!isset($_GET['si_number']) || empty($_GET['si_number'])) {
    die("Invalid request.");
}

$si_number = trim($_GET['si_number']);

/* FETCH SALES ITEMS */
$sql = "SELECT s.*, m.mechanic_name
        FROM sales s
        LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
        WHERE s.si_number = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $si_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Receipt not found.");
}

$items = [];
$totalAmount = 0;

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $totalAmount += floatval($row['total_amount']);
}

$header = $items[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 0;
    background: transparent;
}

/* RECEIPT CONTAINER */
.receipt {
    width: 100%;
    max-width: 300px;
    margin: 20px auto;
    font-family: "Courier New", Courier, monospace;
    font-size: 0.95rem;
    line-height: 1.5;
    color: #000;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    background: transparent;
    color: #000;
    box-shadow: none;
    border-radius: 0;
}

/* HEADER */
.receipt-header {
    text-align: center;
    border-bottom: 2px dashed #000;
    padding-bottom: 10px;
    margin-bottom: 16px;
}

.receipt-header h3 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: bold;
    letter-spacing: 1px;
}

.receipt-header p {
    margin: 4px 0;
    font-size: 0.85rem;
}

/* INFO */
.receipt-info p {
    margin: 3px 0;
    font-size: 0.85rem;
}

/* ITEMS */
.receipt-items {
    border-top: 2px dashed #000;
    border-bottom: 2px dashed #000;
    padding: 14px 0;
    margin: 16px 0;
}

.receipt-item-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.92rem;
    margin-bottom: 8px;
}

.receipt-item-row span:first-child {
    flex: 1;
    padding-right: 10px;
    word-wrap: break-word;
}

.receipt-item-row span:last-child {
    min-width: 80px;
    text-align: right;
    font-weight: 600;
}

/* TOTAL */
.receipt-total {
    display: flex;
    justify-content: space-between;
    font-size: 1.25rem;
    font-weight: bold;
    margin-top: 16px;
    padding-top: 12px;
    border-top: 2px solid #000;
}

/* REMARKS */
.remarks {
    text-align: center;
    font-size: 0.8rem;
    margin-top: 12px;
    padding-top: 8px;
    border-top: 1px dashed #000;
}

/* FOOTER */
.receipt-footer {
    text-align: center;
    margin-top: 20px;
    font-size: 0.8rem;
}

/* ACTIONS */
.receipt-actions {
    margin-top: 20px;
    display: flex;
    justify-content: center;
}

.receipt-actions button {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    background: #000;
    color: #fff;
}

/* PRINT STYLES - APPLIED FOR MODAL PRINTING */


</style>
</head>

<body>

<div class="receipt">

    <!-- HEADER -->
    <div class="receipt-header">
        <h3>ROCARS GARAGE</h3>
        <p>Official Sales Receipt</p>
        <p>
            SI No: <strong><?= htmlspecialchars($header['si_number']) ?></strong><br>
            <?= date("m/d/Y h:i A", strtotime($header['date'])) ?>
        </p>
    </div>

    <!-- INFO -->
    <div class="receipt-info">
        <p><strong>Mechanic:</strong> <?= htmlspecialchars($header['mechanic_name'] ?? 'N/A') ?></p>
        <p><strong>Customer:</strong> <?= htmlspecialchars($header['customer_name'] ?? 'Walk-in') ?></p>
        <p><strong>Vehicle:</strong> <?= htmlspecialchars($header['vehicle'] ?? 'N/A') ?> / <?= htmlspecialchars($header['plate_no'] ?? 'N/A') ?></p>
        <p><strong>Payment:</strong> <?= htmlspecialchars($header['payment_method'] ?? 'Cash') ?></p>
    </div>

    <!-- ITEMS -->
    <div class="receipt-items">
        <?php foreach ($items as $item): ?>
            <div class="receipt-item-row">
                <span><?= intval($item['quantity'] ?? 1) ?> × <?= htmlspecialchars($item['item_name'] ?? 'Service/Item') ?></span>
                <span>₱<?= number_format($item['total_amount'], 2) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="receipt-total">
        <span>TOTAL</span>
        <span>₱<?= number_format($totalAmount, 2) ?></span>
    </div>

    <?php if (!empty($header['remarks'])): ?>
        <div class="remarks">
            Remarks: <?= htmlspecialchars($header['remarks']) ?>
        </div>
    <?php endif; ?>

    <div class="receipt-footer">
        Thank you for your business!
    </div>

    <div class="receipt-actions">
        <button onclick="parent.printReceipt()">🖨 Print</button>
    </div>

</div>

</body>
</html>