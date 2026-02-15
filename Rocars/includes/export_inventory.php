<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Branch filter
$branch_id = $_SESSION['branch_id'] ?? null;

// Search filter
$search = trim($_GET['search'] ?? '');
$whereClauses = [];
$params = [];
$types = "";

// Branch condition
if (!empty($branch_id)) {
    $whereClauses[] = "i.branch_id = ?";
    $params[] = $branch_id;
    $types .= "i";
}

// Search condition
if ($search !== '') {
    $searchTerm = "%$search%";
    $searchColumns = [
        'p.product_name', 'c.category_name', 't.brand', 't.pattern', 't.size', 't.made',
        'bd.brand', 'bd.voltage',
        'eo.brand', 'eo.oiltype',
        'fd.brand', 'fd.typeoffilter',
        'ld.typeoflugnut', 'ld.size',
        'md.brand', 'md.model',
        'mt.brand', 'mt.model',
        'tv.valve_type',
        'ww.model'
    ];

    $searchParts = [];
    foreach ($searchColumns as $col) {
        $searchParts[] = "$col LIKE ?";
        $params[] = $searchTerm;
        $types .= "s";
    }
    $whereClauses[] = "(" . implode(" OR ", $searchParts) . ")";
}

// Final WHERE clause
$whereSQL = $whereClauses ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Main query
$sql = "SELECT 
    p.product_id, p.product_name, p.cost, p.price,
    c.category_name,
    i.quantity,
    -- Tire
    t.tire_id, t.brand AS tire_brand, t.pattern AS tire_pattern, t.made AS tire_made, t.size AS tire_size,
    -- Battery
    bd.battery_id, bd.brand AS battery_brand, bd.voltage AS battery_voltage,
    -- Engine Oil
    eo.oil_id, eo.brand AS eo_brand, eo.oiltype, eo.capacity AS eo_capacity,
    -- Filter
    fd.filter_id, fd.brand AS filter_brand, fd.typeoffilter,
    -- Lugnuts
    ld.lugnut_id, ld.typeoflugnut, ld.size AS lugnut_size,
    -- Mags
    md.mags_id, md.brand AS md_brand, md.model AS md_model, md.size AS md_size, md.material AS md_material,
    -- Motorcycle Tires
    mt.motortire_id, mt.brand AS mt_brand, mt.model AS mt_model, mt.type AS mt_type, mt.size AS mt_size,
    -- Tire Valve
    tv.tirevalve_id, tv.valve_type, tv.material AS tv_material, tv.color AS tv_color,
    -- Wheel Weights
    ww.wheel_id, ww.model AS ww_model, ww.weight AS ww_weight, ww.material AS ww_material,
    -- Nitrogen
    nd.nitrogen_id, nd.nitrogen_percentage, nd.input_date AS nitrogen_input, nd.type_of_vehicle AS nitrogen_vehicle,
    -- Accessories
    ad.accessories_id, ad.typeofaccessories,
    -- Others
    od.id AS other_id, od.description AS other_description
FROM products p
INNER JOIN inventory i ON p.product_id = i.product_id
LEFT JOIN categories c ON p.cat_id = c.category_id
LEFT JOIN tire_details t ON p.product_id = t.product_id
LEFT JOIN battery_details bd ON p.product_id = bd.product_id
LEFT JOIN engineoil_details eo ON p.product_id = eo.product_id
LEFT JOIN filter_details fd ON p.product_id = fd.product_id
LEFT JOIN lugnuts_details ld ON p.product_id = ld.product_id
LEFT JOIN mags_details md ON p.product_id = md.product_id
LEFT JOIN motorcycle_tires_details mt ON p.product_id = mt.product_id
LEFT JOIN tirevalve_details tv ON p.product_id = tv.product_id
LEFT JOIN wheelweights_details ww ON p.product_id = ww.product_id
LEFT JOIN nitrogen_details nd ON p.product_id = nd.product_id
LEFT JOIN accessories_details ad ON p.product_id = ad.product_id
LEFT JOIN other_details od ON p.product_id = od.product_id
$whereSQL
ORDER BY p.product_id ASC";

// Prepare statement
$stmt = $conn->prepare($sql);
if ($params) {
    $bindNames = [];
    $bindNames[] = $types;
    foreach ($params as $key => $value) {
        $bindNames[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindNames);
}
$stmt->execute();
$result = $stmt->get_result();

// Create Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header row
$headers = [
    'Product ID', 'Product Name', 'Category', 'Quantity', 'Price', 'Cost',
    'Tire Brand', 'Tire Pattern', 'Tire Made', 'Tire Size',
    'Battery Brand', 'Battery Voltage',
    'Engine Oil Brand', 'Oil Type', 'Oil Capacity',
    'Filter Brand', 'Filter Type',
    'Lugnut Type', 'Lugnut Size',
    'Mags Brand', 'Mags Model', 'Mags Size', 'Mags Material',
    'Motorcycle Tire Brand', 'Motorcycle Tire Model', 'Motorcycle Tire Type', 'Motorcycle Tire Size',
    'Tire Valve Type', 'Tire Valve Material', 'Tire Valve Color',
    'Wheel Weight Model', 'Wheel Weight', 'Wheel Weight Material',
    'Nitrogen %', 'Nitrogen Input', 'Nitrogen Vehicle Type',
    'Accessories Type', 'Other Description'
];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// Data rows
$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue("A$rowNum", $row['product_id']);
    $sheet->setCellValue("B$rowNum", $row['product_name']);
    $sheet->setCellValue("C$rowNum", $row['category_name']);
    $sheet->setCellValue("D$rowNum", $row['quantity']);
    $sheet->setCellValue("E$rowNum", $row['price']);
    $sheet->setCellValue("F$rowNum", $row['cost']);

    $sheet->setCellValue("G$rowNum", $row['tire_brand']);
    $sheet->setCellValue("H$rowNum", $row['tire_pattern']);
    $sheet->setCellValue("I$rowNum", $row['tire_made']);
    $sheet->setCellValue("J$rowNum", $row['tire_size']);

    $sheet->setCellValue("K$rowNum", $row['battery_brand']);
    $sheet->setCellValue("L$rowNum", $row['battery_voltage']);

    $sheet->setCellValue("M$rowNum", $row['eo_brand']);
    $sheet->setCellValue("N$rowNum", $row['oiltype']);
    $sheet->setCellValue("O$rowNum", $row['eo_capacity']);

    $sheet->setCellValue("P$rowNum", $row['filter_brand']);
    $sheet->setCellValue("Q$rowNum", $row['typeoffilter']);

    $sheet->setCellValue("R$rowNum", $row['typeoflugnut']);
    $sheet->setCellValue("S$rowNum", $row['lugnut_size']);

    $sheet->setCellValue("T$rowNum", $row['md_brand']);
    $sheet->setCellValue("U$rowNum", $row['md_model']);
    $sheet->setCellValue("V$rowNum", $row['md_size']);
    $sheet->setCellValue("W$rowNum", $row['md_material']);

    $sheet->setCellValue("X$rowNum", $row['mt_brand']);
    $sheet->setCellValue("Y$rowNum", $row['mt_model']);
    $sheet->setCellValue("Z$rowNum", $row['mt_type']);
    $sheet->setCellValue("AA$rowNum", $row['mt_size']);

    $sheet->setCellValue("AB$rowNum", $row['valve_type']);
    $sheet->setCellValue("AC$rowNum", $row['tv_material']);
    $sheet->setCellValue("AD$rowNum", $row['tv_color']);

    $sheet->setCellValue("AE$rowNum", $row['ww_model']);
    $sheet->setCellValue("AF$rowNum", $row['ww_weight']);
    $sheet->setCellValue("AG$rowNum", $row['ww_material']);

    $sheet->setCellValue("AH$rowNum", $row['nitrogen_percentage']);
    $sheet->setCellValue("AI$rowNum", $row['nitrogen_input']);
    $sheet->setCellValue("AJ$rowNum", $row['nitrogen_vehicle']);

    $sheet->setCellValue("AK$rowNum", $row['typeofaccessories']);
    $sheet->setCellValue("AL$rowNum", $row['other_description']);

    $rowNum++;
}

// Export
$filename = "inventory_export.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
