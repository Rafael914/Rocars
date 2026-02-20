<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// 1. Collect Common Product Data
$category_id  = (int)($_POST['category_id'] ?? 0);
$product_name = trim($_POST['product_name'] ?? '');
$price        = floatval($_POST['price'] ?? 0);
$cost         = floatval($_POST['cost'] ?? 0);
$quantity     = floatval($_POST['quantity'] ?? 0); // allow decimal
$critical     = floatval($_POST['critical'] ?? 0);
$branch_id    = $_SESSION['branch_id'] ?? 1; 

// 2. Collect "Generic" Detail Fields
$detail1 = $_POST['detail1'] ?? null;
$detail2 = $_POST['detail2'] ?? null;
$detail3 = $_POST['detail3'] ?? null;
$detail4 = $_POST['detail4'] ?? null;
$detail5 = $_POST['detail5'] ?? null;
$detail6 = $_POST['detail6'] ?? null;

// Validate required fields
if (!$category_id || !$product_name || !$price) {
    echo json_encode(['success' => false, 'message' => 'Category, Product Name, or Price is missing']);
    exit;
}

// 3. Insert into main PRODUCTS table
$sql_prod = "INSERT INTO products 
    (product_name, cat_id, price, cost, detail1, detail2, detail3, detail4, detail5, detail6, critical_stock_level) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql_prod);
$stmt->bind_param("sddddsssssd", 
    $product_name, 
    $category_id, 
    $price, 
    $cost, 
    $detail1, 
    $detail2, 
    $detail3, 
    $detail4, 
    $detail5, 
    $detail6,
    $critical
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => "Product insert failed: " . $stmt->error]);
    exit;
}

$product_id = $conn->insert_id;
$stmt->close();

// 4. Insert into INVENTORY table
$stmt = $conn->prepare("INSERT INTO inventory (product_id, quantity, branch_id) VALUES (?, ?, ?)");
$stmt->bind_param("ddd", $product_id, $quantity, $branch_id); // quantity is decimal now
$stmt->execute();
$stmt->close();

// 5. Insert into CATEGORY-SPECIFIC tables
$stmt = null;
switch($category_id) {
    case 1: // Accessories
        $stmt = $conn->prepare("INSERT INTO accessories_details 
            (product_id, typeofaccessories, brand, model_number, material, color, fitment_details) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $product_id, $_POST['typeofaccessories'], $_POST['brand'], $_POST['model_number'], $_POST['material'], $_POST['color'], $_POST['fitment_details']);
        break;

    case 2: // Battery
        $voltage = $_POST['Voltage'] ?? '';
        $stmt = $conn->prepare("INSERT INTO battery_details (product_id, brand, voltage, model_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $product_id, $_POST['brand'], $voltage, $_POST['Model_number']);
        break;

    case 3: // Engine Oil
        $stmt = $conn->prepare("INSERT INTO engineoil_details (product_id, brand, oiltype, capacity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $product_id, $_POST['brand'], $_POST['oiltype'], $_POST['capacity']);
        break;

    case 4: // Filter
        $stmt = $conn->prepare("INSERT INTO filter_details (product_id, brand, typeoffilter, vehicle_application, filter_specs, material) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $product_id, $_POST['brand'], $_POST['typeoffilter'], $_POST['vehicle_application'], $_POST['filter_specs'], $_POST['material']);
        break;

    case 5: // Lugnuts
        $size = floatval($_POST['size'] ?? 0); // decimal allowed
        $stmt = $conn->prepare("INSERT INTO lugnuts_details (product_id, typeoflugnut, size) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $product_id, $_POST['typeoflugnuts'], $size);
        break;

    case 6: // Mags
        $stmt = $conn->prepare("INSERT INTO mags_details (product_id, brand, model, size, material) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $product_id, $_POST['brand'], $_POST['model'], $_POST['size'], $_POST['material']);
        break;

    case 9: // Tire
        $stmt = $conn->prepare("INSERT INTO tire_details (product_id, brand, size, pattern, made) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $product_id, $_POST['brand'], $_POST['size'], $_POST['pattern'], $_POST['made']);
        break;

    case 10: // Tire Valve
        $stmt = $conn->prepare("INSERT INTO tirevalve_details (product_id, valve_type, material, color) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $product_id, $_POST['valvetype'], $_POST['material'], $_POST['color']);
        break;

    case 11: // Wheelweights
        $weight = floatval($_POST['weight'] ?? 0); // allow decimal
        $stmt = $conn->prepare("INSERT INTO wheelweights_details (product_id, model, weight, material) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issd", $product_id, $_POST['model'], $weight, $_POST['material']);
        break;

    case 12: // Motorcycle Tires
        $stmt = $conn->prepare("INSERT INTO motorcycletires_details (product_id, brand, model, type, size) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $product_id, $_POST['brand'], $_POST['model'], $_POST['type'], $_POST['size']);
        break;

    case 13: // Others
        $stmt = $conn->prepare("INSERT INTO others_details (product_id, description) VALUES (?, ?)");
        $stmt->bind_param("is", $product_id, $_POST['description']);
        break;

    default:
        $stmt = null;
        break;
}

// Execute category statement if exists
if ($stmt) {
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true, 'message' => "Product added successfully", 'product_id' => $product_id]);
exit;
?>
