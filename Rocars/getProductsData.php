<?php

require_once 'includes/config.php';
require_once 'includes/auth.php'; 

if (!isset($conn) || !isset($_SESSION['branch_id'])) {

    die("Configuration or session data is missing.");
}

$branch_id = $_SESSION['branch_id'];


$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is never less than 1

$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$category = isset($_GET['category']) ? trim($_GET['category']) : "";

$baseUrl = "getProductsData.php?page=";
if (!empty($search)) {

    $baseUrl .= "&search=" . urlencode($search);
}
if (!empty($category)) {
    $baseUrl .= "&category=" . urlencode($category);
}


// 2. SQL Query Construction
$baseSql = "FROM inventory AS i
    LEFT JOIN products AS p ON i.product_id = p.product_id
    LEFT JOIN categories AS c ON p.cat_id = c.category_id
    LEFT JOIN tire_details AS t ON p.product_id = t.product_id
    LEFT JOIN wheelweights_details AS ww ON p.product_id = ww.product_id
    LEFT JOIN tirevalve_details AS tv ON p.product_id = tv.product_id
    LEFT JOIN nitrogen_details AS nd ON p.product_id = nd.product_id
    LEFT JOIN motorcycle_tires_details AS mt ON p.product_id = mt.product_id
    LEFT JOIN mags_details AS md ON p.product_id = md.product_id
    LEFT JOIN lugnuts_details AS ld ON p.product_id = ld.product_id
    LEFT JOIN filter_details AS fd ON p.product_id = fd.product_id
    LEFT JOIN engineoil_details AS eo ON p.product_id = eo.product_id
    LEFT JOIN battery_details AS bd ON p.product_id = bd.product_id
    LEFT JOIN accessories_details AS ad ON p.product_id = ad.product_id
    WHERE i.branch_id = ?";

$params = [$branch_id];
$types = "i"; // 'i' for $branch_id (integer)

if (!empty($category)) {
    $baseSql .= " AND c.category_id = ?";
    $params[] = $category;
    $types .= "i"; 
}

if (!empty($search)) {
    $baseSql .= " AND (
        p.product_name LIKE ?
        OR t.brand LIKE ? OR t.pattern LIKE ? OR t.made LIKE ? OR t.size LIKE ?
        OR ww.model LIKE ? OR ww.weight LIKE ? OR ww.material LIKE ?
        OR tv.valve_type LIKE ? OR tv.material LIKE ? OR tv.color LIKE ?
        OR nd.nitrogen_percentage LIKE ? OR nd.input_date LIKE ? OR nd.type_of_vehicle LIKE ?
        OR mt.brand LIKE ? OR mt.model LIKE ? OR mt.type LIKE ? OR mt.size LIKE ?
        OR md.brand LIKE ? OR md.model LIKE ? OR md.size LIKE ? OR md.material LIKE ?
        OR ld.typeoflugnut LIKE ? OR ld.size LIKE ?
        OR fd.brand LIKE ? OR fd.typeoffilter LIKE ?
        OR eo.brand LIKE ? OR eo.oiltype LIKE ? OR eo.capacity LIKE ?
        OR bd.brand LIKE ? OR bd.voltage LIKE ?
        OR ad.typeofaccessories LIKE ? OR ad.brand LIKE ? OR ad.model_number LIKE ?
        OR ad.material LIKE ? OR ad.color LIKE ? OR ad.fitment_details LIKE ?
    )";

    $like = "%$search%";

    // ✅ 37 LIKE parameters
    $params = array_merge($params, array_fill(0, 37, $like));
    $types  .= str_repeat("s", 37);
}

// 3. Count Total Products
$countSql = "SELECT COUNT(DISTINCT i.inventory_id) AS total " . $baseSql;
$countStmt = $conn->prepare($countSql);

if (!$countStmt) {
    die("Count prepare failed: (" . $conn->errno . ") " . $conn->error);
}

$countStmt->bind_param($types, ...$params); 
$countStmt->execute();
$countResult = $countStmt->get_result();
$total = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = max(ceil($total / $limit), 1);

// Adjust page if it exceeds total pages
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit; 
}


// 4. Fetch Product Data
$dataSql = "SELECT 
             i.inventory_id, i.quantity,
             c.category_id, c.category_name,
             p.product_id, p.product_name, p.price, p.cost, p.detail1, p.detail2, p.detail3, p.detail4, p.detail5, p.detail6,
             t.brand AS tire_brand, t.pattern AS tire_pattern, t.made AS tire_made, t.size AS tire_size,
             ww.model AS ww_model, ww.weight AS ww_weight, ww.material AS ww_material,
             tv.valve_type AS tv_type, tv.material AS tv_material, tv.color AS tv_color,
             nd.nitrogen_percentage, nd.input_date AS nitrogen_input, nd.type_of_vehicle AS nitrogen_vehicle,
             mt.brand AS mt_brand, mt.model AS mt_model, mt.type AS mt_type, mt.size AS mt_size,
             md.brand AS md_brand, md.model AS md_model, md.size AS md_size, md.material AS md_material,
             ld.typeoflugnut, ld.size AS lugnut_size,
             fd.brand AS filter_brand, fd.typeoffilter,
             eo.brand AS eo_brand, eo.oiltype, eo.capacity AS eo_capacity,
             bd.brand AS battery_brand, bd.voltage AS battery_voltage, bd.model_number AS battery_number,
             ad.typeofaccessories, ad.brand AS acc_brand, ad.model_number AS acc_model_number, ad.material AS acc_material, ad.color AS acc_color, ad.fitment_details
             " . $baseSql . "
             ORDER BY p.product_name ASC
             LIMIT ?, ?";

$params_with_limit = array_merge($params, [$offset, $limit]);
$types_with_limit = $types . "ii"; 

$stmt = $conn->prepare($dataSql);

if (!$stmt) {
    die("Data prepare failed: (" . $conn->errno . ") " . $conn->error);
}

$stmt->bind_param($types_with_limit, ...$params_with_limit);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
   echo "
<div style='text-align:center;'>
                     <img 
                        src='images/noProduct.png' 
                        alt='No products found'
                        style='max-width:40%; height:auto; max-height:300px; display:block; margin:10rem auto;'
                    >
</div>
";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <link rel="stylesheet" href="assets/css/getProductsData.css">
    </head>
<body>


<div class="product-grid">
    <?php while ($row = $result->fetch_assoc()): 
        $qty = max(0, (int)$row['quantity']);
        $in_stock = $qty > 0;
        
        // Define aliases for accessories details to avoid display conflicts
        $acc_brand = $row['acc_brand'] ?? null; 
        $acc_model_number = $row['acc_model_number'] ?? null; 
        $acc_material = $row['acc_material'] ?? null;
        $acc_color = $row['acc_color'] ?? null;
        $acc_fitment_details = $row['acc_fitment_details'] ?? null;
    ?>
    <div class="inventory-row"> 


        <div class="inventory-info">
            <div class="top-row">
                <div class="product-name"><?= htmlspecialchars($row['product_name']); ?></div>
<div class="product-qty <?= $in_stock ? 'in-stock' : 'out-stock' ?>"
     id="qty-<?= $row['product_id'] ?>">
    Qty: <strong><?= $qty ?></strong>
</div>
            </div>

            <div class="product-category">
                <strong>Category:</strong> <?= htmlspecialchars($row['category_name']); ?>
            </div>
    
        <div class="specs">
                <?php switch ($row['category_id']):
                    case 1: // Accessories ?>
                        <?php if ($row['typeofaccessories']): ?>
                            <div class="inventory-cell"><strong>Type:</strong> <?= htmlspecialchars($row['typeofaccessories']) ?></div>
                        <?php endif; ?>
                        <?php if ($acc_brand): ?>
                            <div class="inventory-cell"><strong>Brand:</strong> <?= htmlspecialchars($acc_brand) ?></div>
                        <?php endif; ?>
                        <?php if ($acc_model_number): ?>
                            <div class="inventory-cell"><strong>Model Number:</strong> <?= htmlspecialchars($acc_model_number) ?></div>
                        <?php endif; ?>
                        <?php if ($acc_material): ?>
                            <div class="inventory-cell"><strong>Material:</strong> <?= htmlspecialchars($acc_material) ?></div>
                        <?php endif; ?>
                        <?php if ($acc_color): ?>
                            <div class="inventory-cell"><strong>Color:</strong> <?= htmlspecialchars($acc_color) ?></div>
                        <?php endif; ?>
                        <?php if ($acc_fitment_details): ?>
                            <div class="inventory-cell"><strong>Fitment Details:</strong> <?= htmlspecialchars($acc_fitment_details) ?></div>
                        <?php endif; ?>
                        <?php break; ?>
                    <?php case 2: // Battery ?>
                        <?php if ($row['battery_brand']): ?>
                            <div class="inventory-cell"><strong>Brand:</strong> <?= htmlspecialchars($row['battery_brand']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['battery_voltage']): ?>
                            <div class="inventory-cell"><strong>Voltage:</strong> <?= htmlspecialchars($row['battery_voltage']) ?>V</div>
                        <?php endif; ?>
                        <?php if ($row['battery_number']): ?>
                            <div class="inventory-cell"><strong>Model No.:</strong> <?= htmlspecialchars($row['battery_number']) ?></div>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case 3: // Engine Oil ?>
                        <?php if ($row['eo_brand']): ?>
                            <div class="inventory-cell"><strong>Brand:</strong> <?= htmlspecialchars($row['eo_brand']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['oiltype']): ?>
                            <div class="inventory-cell"><strong>Oil Type:</strong> <?= htmlspecialchars($row['oiltype']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['eo_capacity']): ?>
                            <div class="inventory-cell"><strong>Capacity:</strong> <?= htmlspecialchars($row['eo_capacity']) ?></div>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case 4: // Filter ?>
                        <?php if ($row['filter_brand']): ?>
                            <div class="inventory-cell"><strong>Brand:</strong> <?= htmlspecialchars($row['filter_brand']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['typeoffilter']): ?>
                            <div class="inventory-cell"><strong>Type:</strong> <?= htmlspecialchars($row['typeoffilter']) ?></div>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case 5: // Lugnuts ?>
                        <?php if ($row['typeoflugnut']): ?>
                            <div class="inventory-cell"><strong>Type:</strong> <?= htmlspecialchars($row['typeoflugnut']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['lugnut_size']): ?>
                            <div class="inventory-cell"><strong>Size:</strong> <?= htmlspecialchars($row['lugnut_size']) ?></div>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case 6: // Mags ?>
                        <?php if ($row['md_brand']): ?>
                            <div class="inventory-cell"><strong>Brand:</strong> <?= htmlspecialchars($row['md_brand']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['md_model']): ?>
                            <div class="inventory-cell"><strong>Model:</strong> <?= htmlspecialchars($row['md_model']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['md_size']): ?>
                            <div class="inventory-cell"><strong>Size:</strong> <?= htmlspecialchars($row['md_size']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['md_material']): ?>
                            <div class="inventory-cell"><strong>Material:</strong> <?= htmlspecialchars($row['md_material']) ?></div>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case 7: // Mechanical Product ?>
                        <div class="inventory-cell"><em>No specialized details.</em></div>
                        <?php break; ?>

                    <?php case 8: // Nitrogen ?>
                        <?php if ($row['nitrogen_percentage']): ?>
                            <div class="inventory-cell"><strong>Nitrogen %:</strong> <?= htmlspecialchars($row['nitrogen_percentage']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['nitrogen_input']): ?>
                            <div class="inventory-cell"><strong>Input Date:</strong> <?= htmlspecialchars($row['nitrogen_input']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['nitrogen_vehicle']): ?>
                            <div class="inventory-cell"><strong>Vehicle Type:</strong> <?= htmlspecialchars($row['nitrogen_vehicle']) ?></div>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case 9: // Tire ?>
                        <?php if ($row['tire_brand']): ?>
                            <div class="inventory-cell"><strong>Brand:</strong> <?= htmlspecialchars($row['tire_brand']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['tire_pattern']): ?>
                            <div class="inventory-cell"><strong>Pattern:</strong> <?= htmlspecialchars($row['tire_pattern']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['tire_made']): ?>
                            <div class="inventory-cell"><strong>Made:</strong> <?= htmlspecialchars($row['tire_made']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['tire_size']): ?>
                            <div class="inventory-cell"><strong>Size:</strong> <?= htmlspecialchars($row['tire_size']) ?></div>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case 10: // Tire Valve ?>
                        <?php if ($row['tv_type']): ?>
                            <div class="inventory-cell"><strong>Valve Type:</strong> <?= htmlspecialchars($row['tv_type']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['tv_material']): ?>
                            <div class="inventory-cell"><strong>Material:</strong> <?= htmlspecialchars($row['tv_material']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['tv_color']): ?>
                            <div class="inventory-cell"><strong>Color:</strong> <?= htmlspecialchars($row['tv_color']) ?></div>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case 11: // Wheel Weights ?>
                        <?php if ($row['ww_model']): ?>
                            <div class="inventory-cell"><strong>Model:</strong> <?= htmlspecialchars($row['ww_model']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['ww_weight']): ?>
                            <div class="inventory-cell"><strong>Weight:</strong> <?= htmlspecialchars($row['ww_weight']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['ww_material']): ?>
                            <div class="inventory-cell"><strong>Material:</strong> <?= htmlspecialchars($row['ww_material']) ?></div>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case 12: // Motorcycle Tires ?>
                        <?php if ($row['mt_brand']): ?>
                            <div class="inventory-cell"><strong>Brand:</strong> <?= htmlspecialchars($row['mt_brand']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['mt_model']): ?>
                            <div class="inventory-cell"><strong>Model:</strong> <?= htmlspecialchars($row['mt_model']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['mt_type']): ?>
                            <div class="inventory-cell"><strong>Type:</strong> <?= htmlspecialchars($row['mt_type']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['mt_size']): ?>
                            <div class="inventory-cell"><strong>Size:</strong> <?= htmlspecialchars($row['mt_size']) ?></div>
                        <?php endif; ?>
                        <?php break; ?>
                        
                    <?php default: ?>
                      <p class="details-title"><strong>Details</strong></p>
                        <div class="details-container">
                            <?php if ($row['detail1']): ?>
                                <div class="detail-item"><?= htmlspecialchars($row['detail1']) ?></div>
                            <?php endif; ?>
                            <?php if ($row['detail2']): ?>
                                <div class="detail-item"><?= htmlspecialchars($row['detail2']) ?></div>
                            <?php endif; ?>
                            <?php if ($row['detail3']): ?>
                                <div class="detail-item"><?= htmlspecialchars($row['detail3']) ?></div>
                            <?php endif; ?>
                            <?php if ($row['detail4']): ?>
                                <div class="detail-item"><?= htmlspecialchars($row['detail4']) ?></div>
                            <?php endif; ?>
                            <?php if ($row['detail5']): ?>
                                <div class="detail-item"><?= htmlspecialchars($row['detail5']) ?></div>
                            <?php endif; ?>
                            <?php if ($row['detail6']): ?>
                                <div class="detail-item"><?= htmlspecialchars($row['detail6']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php break; ?>
                <?php endswitch; ?>
            </div>

            <div class="price">₱<?= number_format((float)$row['price'], 2) ?></div>

            <?php if ($in_stock): ?>
                <button
                    class="add-to-cart-btn available"
                    type="button"
                    data-id="<?= htmlspecialchars($row['product_id']); ?>"
                    data-name="<?= htmlspecialchars($row['product_name']); ?>"
                    data-price="<?= htmlspecialchars($row['price']); ?>"
                    data-cost= "<?= htmlspecialchars($row['cost']); ?>"
                    data-qty="<?= $qty ?>"
                    data-type="<?= isset($row['category_name']) ? htmlspecialchars($row['category_name']) : ''; ?>">Add to Cart</button>
            <?php else: ?>
                <button class="add-to-cart-btn disabled" disabled>
                    Out of Stock
                </button>
            <?php endif; ?>
        </div>
    </div>
    
<?php endwhile; ?>
</div>


<br>
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <button class="page-btn" data-page="<?= $page-1 ?>">‹ Prev</button>
        <?php endif; ?>
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
            <button class="page-btn <?= $i==$page?'active':'' ?>" data-page="<?= $i ?>"><?= $i ?></button>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <button class="page-btn" data-page="<?= $page+1 ?>">Next ›</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</body>
</html>