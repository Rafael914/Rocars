<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $conn;

$branch_id = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 1;
$recordPerPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '9');
$offset = ($page - 1) * $recordPerPage;

$s = "%$search%";

// --- 1. DYNAMIC WHERE CLAUSE & PARAMETERS ---
$whereSQL = "WHERE i.branch_id = ?";
$bindTypes = "i";
$bindValues = [$branch_id];

if (!empty($search)) {
    $whereSQL .= " AND (p.product_name LIKE ? OR t.brand LIKE ? OR t.size LIKE ? OR c.category_name LIKE ?)";
    $bindTypes .= "ssss";
    array_push($bindValues, $s, $s, $s, $s);
}

if (!empty($category_filter)) {
    $whereSQL .= " AND c.category_id = ?";
    $bindTypes .= "i";
    $bindValues[] = (int)$category_filter;
}

// --- 2. COUNT TOTAL ROWS ---
$countSQL = "SELECT COUNT(DISTINCT p.product_id) 
             FROM products p
             INNER JOIN inventory i ON p.product_id = i.product_id
             LEFT JOIN categories c ON p.cat_id = c.category_id
             LEFT JOIN tire_details t ON p.product_id = t.product_id
             $whereSQL";

$stmtTotal = $conn->prepare($countSQL);
$stmtTotal->bind_param($bindTypes, ...$bindValues);
$stmtTotal->execute();
$stmtTotal->bind_result($totalRows);
$stmtTotal->fetch();
$stmtTotal->close();

$totalPages = ceil($totalRows / $recordPerPage);

// --- 3. DATA QUERY (ALL FIELDS PRESERVED) ---
$dataSQL = "SELECT 
              i.inventory_id, i.quantity, i.branch_id,
              p.product_id, p.product_name, p.cost, p.price,
              p.detail1, p.detail2, p.detail3, p.detail4, p.detail5, p.detail6, p.critical_stock_level,
              c.category_id, c.category_name,
              CASE 
                WHEN p.critical_stock_level IS NOT NULL AND i.quantity <= p.critical_stock_level THEN 1
                ELSE 0
              END AS is_critical,
              t.tire_id, t.brand AS tire_brand, t.pattern AS tire_pattern, 
              t.made AS tire_made, t.size AS tire_size,
              ww.wheel_id, ww.model AS ww_model, ww.weight AS ww_weight, ww.material AS ww_material,
              tv.tirevalve_id, tv.valve_type AS tv_type, tv.material AS tv_material, tv.color AS tv_color,
              nd.nitrogen_id, nd.nitrogen_percentage, nd.input_date AS nitrogen_input, nd.type_of_vehicle AS nitrogen_vehicle,
              mt.motortire_id, mt.brand AS mt_brand, mt.model AS mt_model, mt.type AS mt_type, mt.size AS mt_size,
              md.mags_id, md.brand AS md_brand, md.model AS md_model, md.size AS md_size, md.material AS md_material,
              ld.lugnut_id, ld.typeoflugnut, ld.size AS lugnut_size,
              fd.filter_id, fd.brand AS filter_brand, fd.typeoffilter,
              eo.oil_id, eo.brand AS eo_brand, eo.oiltype, eo.capacity AS eo_capacity,
              bd.battery_id, bd.brand AS battery_brand, bd.voltage AS battery_voltage,
              ad.accessories_id, ad.typeofaccessories,
              od.id AS other_id, od.description
    FROM products p
    INNER JOIN inventory i ON p.product_id = i.product_id
    LEFT JOIN categories c ON p.cat_id = c.category_id
    LEFT JOIN tire_details t ON p.product_id = t.product_id
    LEFT JOIN wheelweights_details ww ON p.product_id = ww.product_id
    LEFT JOIN tirevalve_details tv ON p.product_id = tv.product_id
    LEFT JOIN nitrogen_details nd ON p.product_id = nd.product_id
    LEFT JOIN motorcycle_tires_details mt ON p.product_id = mt.product_id
    LEFT JOIN mags_details md ON p.product_id = md.product_id
    LEFT JOIN lugnuts_details ld ON p.product_id = ld.product_id
    LEFT JOIN filter_details fd ON p.product_id = fd.product_id
    LEFT JOIN engineoil_details eo ON p.product_id = eo.product_id
    LEFT JOIN battery_details bd ON p.product_id = bd.product_id
    LEFT JOIN accessories_details ad ON p.product_id = ad.product_id
    LEFT JOIN other_details od ON p.product_id = od.product_id
    $whereSQL
    ORDER BY is_critical DESC, p.product_name ASC
    LIMIT ?, ?";

// Prepare Data Binding (Adding Offset and Record Limit)
$dataBindTypes = $bindTypes . "ii";
$dataBindValues = $bindValues;
array_push($dataBindValues, $offset, $recordPerPage);

$stmt = $conn->prepare($dataSQL);
$stmt->bind_param($dataBindTypes, ...$dataBindValues);
$stmt->execute();
$result = $stmt->get_result();

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
$categoryQuery = $conn->query("SELECT category_id, category_name FROM categories");
$selected_category = isset($category_filter) && $category_filter !== '' ? $category_filter : 9;
$selected_category = $category_filter;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory Management</title>
<link rel="stylesheet" href="assets/css/sidebar.css">
<link rel="stylesheet" href="assets/css/topbar.css">
<link rel = "stylesheet" href="assets/css/inventorysearchmodal.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

<?php include 'includes/sidebar.php';  ?>

<div class="main-content" id="mainContent">
    <div class="topbar">
        <i class="fa-solid fa-bars toggle" id="toggleBtn" aria-label="Toggle sidebar"></i>
        <h1>Inventory Management</h1>
        <div class="logo">
            <img src="images/rocarsn.png" alt="Logo" class="logo-img">
        </div>
    </div>
    
    <div id="editOverlay" style="display:none;">
        
    </div>
    
<div class="inventory-controls">
    <div class="controls-row">
        <input type="text" id="searchInput" class="search-input" placeholder="Search product or category" value="<?= htmlspecialchars($search) ?>" autocomplete="off">
        <a href="includes/export_inventory.php?search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>" class="btn btn-primary export-btn">Export to Excel</a>
        
<select id="Category" onchange="refreshInventoryModal(1)">
    <option value="">All Categories</option>
    <?php if ($categoryQuery): ?>
        <?php while($category = $categoryQuery->fetch_assoc()): ?>
            <option value="<?= $category['category_id'] ?>" 
                <?= ($selected_category == $category['category_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($category['category_name']); ?>
            </option>
        <?php endwhile; ?>
    <?php endif; ?>
</select>


        <select name="addProducts" id="categorySelect" class="category-select">
            <option value="">Add Product:</option>
            <?php
                $categoryResult = $conn->query("SELECT * FROM categories ORDER BY category_name");
                while ($cat = $categoryResult->fetch_assoc()):
                ?>
                    <option value="<?= htmlspecialchars($cat['category_id']) ?>">
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                <?php endwhile; ?>
        </select>
        <button id="openCategoryModal" class="btn btn-secondary add-category-btn" style="background-color:white; color:black; border:1px solid #777575">Add Category</button>
        <button id="categoryList" class="btn category-list">Category List</button>
    </div>
</div>


        <div id="inventoryTableContainer"> 
        
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Table</title>
</head>
<body>
    <div class="container">
        <div class="table-wrapper">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Cost</th>
                        <th>Stock</th>
                        <th>Critical</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="inventory-row"
                                data-category="<?= htmlspecialchars($row['category_name']) ?>"
                                data-product-id="<?= htmlspecialchars($row['product_id']) ?>"
                                data-tire-id="<?= $row['tire_id'] ?? '' ?>"
                                data-accessories-id="<?= $row['accessories_id'] ?? '' ?>"
                                data-battery-id="<?= $row['battery_id'] ?? '' ?>"
                                data-oil-id="<?= htmlspecialchars($row['oil_id'] ?? '') ?>"
                                data-filter-id="<?= $row['filter_id'] ?? '' ?>"
                                data-lugnut-id="<?= $row['lugnut_id'] ?? '' ?>"
                                data-mags-id="<?= $row['mags_id'] ?? '' ?>"
                                data-mechanical-id="<?= $row['mechanical_id'] ?? '' ?>"
                                data-motortire-id="<?= $row['motortire_id'] ?? '' ?>"
                                data-nitrogen-id="<?= $row['nitrogen_id'] ?? '' ?>"
                                data-tirevalve-id="<?= $row['tirevalve_id'] ?? '' ?>"
                                data-wheel-id="<?= $row['wheel_id'] ?? '' ?>"
                                data-other-id="<?= htmlspecialchars($row['id'] ?? '') ?>"
                                data-product-name="<?= htmlspecialchars($row['product_name']) ?>"
                                data-critical ="<?= htmlspecialchars($row['critical_stock_level']) ?>"
                                >
                                <td class="product-details" >
                                    <?php
                                    $details = [];

                                    if (!empty($row['product_name'])) {
                                        $details[] = "<strong>" . htmlspecialchars($row['product_name']) . "</strong>";
                                    }

                                    // Accessories
                                    if ($row['category_id'] == 1) {
                                        if (!empty($row['typeofaccessories'])) $details[] = "Type: " . htmlspecialchars($row['typeofaccessories']);
                                    }
                                    // Battery
                                    elseif ($row['category_id'] == 2) {
                                        if (!empty($row['battery_brand'])) $details[] = "Brand: " . htmlspecialchars($row['battery_brand']);
                                        if (!empty($row['battery_voltage'])) $details[] = "Voltage: " . htmlspecialchars($row['battery_voltage']) . "V";
                                    }
                                    // Engine Oil
                                    elseif ($row['category_id'] == 3) {
                                        if (!empty($row['eo_brand'])) $details[] = "Brand: " . htmlspecialchars($row['eo_brand']);
                                        if (!empty($row['oiltype'])) $details[] = "Oil Type: " . htmlspecialchars($row['oiltype']);
                                        if (!empty($row['eo_capacity'])) $details[] = "Capacity: " . htmlspecialchars($row['eo_capacity']);
                                    }
                                    // Filter
                                    elseif ($row['category_id'] == 4) {
                                        if (!empty($row['filter_brand'])) $details[] = "Brand: " . htmlspecialchars($row['filter_brand']);
                                        if (!empty($row['typeoffilter'])) $details[] = "Type: " . htmlspecialchars($row['typeoffilter']);
                                    }
                                    // Lugnuts
                                    elseif ($row['category_id'] == 5) {
                                        if (!empty($row['typeoflugnut'])) $details[] = "Type: " . htmlspecialchars($row['typeoflugnut']);
                                        if (!empty($row['lugnut_size'])) $details[] = "Size: " . htmlspecialchars($row['lugnut_size']);
                                    }
                                    // Mags
                                    elseif ($row['category_id'] == 6) {
                                        if (!empty($row['md_brand'])) $details[] = "Brand: " . htmlspecialchars($row['md_brand']);
                                        if (!empty($row['md_model'])) $details[] = "Model: " . htmlspecialchars($row['md_model']);
                                        if (!empty($row['md_size'])) $details[] = "Size: " . htmlspecialchars($row['md_size']);
                                        if (!empty($row['md_material'])) $details[] = "Material: " . htmlspecialchars($row['md_material']);
                                    }
                                    // Mechanical Product (7)
                                    elseif ($row['category_id'] == 7) {
                                        $details[] = "No detailed specs."; // Assuming this category has separate details in mechanical_details
                                    }
                                    // Nitrogen
                                    elseif ($row['category_id'] == 8) {
                                        if (!empty($row['nitrogen_percentage'])) $details[] = "Nitrogen %: " . htmlspecialchars($row['nitrogen_percentage']);
                                        if (!empty($row['nitrogen_input'])) $details[] = "Input Date: " . htmlspecialchars($row['nitrogen_input']);
                                        if (!empty($row['nitrogen_vehicle'])) $details[] = "Vehicle Type: " . htmlspecialchars($row['nitrogen_vehicle']);
                                    }
                                    // Tire
                                    elseif ($row['category_id'] == 9) {
                                        if (!empty($row['tire_brand'])) $details[] = "Brand: " . htmlspecialchars($row['tire_brand']);
                                        if (!empty($row['tire_pattern'])) $details[] = "Pattern: " . htmlspecialchars($row['tire_pattern']);
                                        if (!empty($row['tire_made'])) $details[] = "Made: " . htmlspecialchars($row['tire_made']);
                                        if (!empty($row['tire_size'])) $details[] = "Size: " . htmlspecialchars($row['tire_size']);
                                    }
                                    // Tire Valve
                                    elseif ($row['category_id'] == 10) {
                                        if (!empty($row['tv_type'])) $details[] = "Valve Type: " . htmlspecialchars($row['tv_type']);
                                        if (!empty($row['tv_material'])) $details[] = "Material: " . htmlspecialchars($row['tv_material']);
                                        if (!empty($row['tv_color'])) $details[] = "Color: " . htmlspecialchars($row['tv_color']);
                                    }
                                    // Wheel Weights
                                    elseif ($row['category_id'] == 11) {
                                        if (!empty($row['ww_model'])) $details[] = "Model: " . htmlspecialchars($row['ww_model']);
                                        if (!empty($row['ww_weight'])) $details[] = "Weight: " . htmlspecialchars($row['ww_weight']);
                                        if (!empty($row['ww_material'])) $details[] = "Material: " . htmlspecialchars($row['ww_material']);
                                    }
                                    // Motorcycle Tires
                                    elseif ($row['category_id'] == 12) {
                                        if (!empty($row['mt_brand'])) $details[] = "Brand: " . htmlspecialchars($row['mt_brand']);
                                        if (!empty($row['mt_model'])) $details[] = "Model: " . htmlspecialchars($row['mt_model']);
                                        if (!empty($row['mt_type'])) $details[] = "Type: " . htmlspecialchars($row['mt_type']);
                                        if (!empty($row['mt_size'])) $details[] = "Size: " . htmlspecialchars($row['mt_size']);
                                    }
                                    // Other/Default
                                    else {
                                        if (!empty($row['description'])) $details[] = "Description: " . htmlspecialchars($row['description']);
                                        if (!empty($row['detail1'])) $details[] = "" . htmlspecialchars($row['detail1']);
                                        if (!empty($row['detail2'])) $details[] = "" . htmlspecialchars($row['detail2']);
                                        if (!empty($row['detail3'])) $details[] = "" . htmlspecialchars($row['detail3']);
                                        if (!empty($row['detail4'])) $details[] = "" . htmlspecialchars($row['detail4']);
                                        if (!empty($row['detail5'])) $details[] = "" . htmlspecialchars($row['detail5']);
                                        if (!empty($row['detail6'])) $details[] = "" . htmlspecialchars($row['detail6']);
                                    }

                                    // Output all details as a list
                                    if (!empty($details)) {
                                        echo "<ul><li>" . implode("</li><li>", $details) . "</li></ul>";
                                    } else {
                                        echo "No details available.";
                                    }
                                    ?>
                                </td>
                                <td class="category"><?= htmlspecialchars($row['category_name']) ?></td>
                                <td class="price">
                                <span class="currentPrice">
                                    <?= htmlspecialchars($row['price']) ?>
                                </span><br>

                                <input
                                    type="number"
                                    class="percentInput"
                                    placeholder="%"
                                    min="0"
                                    style="width:55px; margin-left:6px;"
                                    data-product-id="<?= $row['product_id'] ?>"
                                    data-original-price="<?= $row['price'] ?>"
                                >

                                <button
                                    type="button"
                                    class="savePriceBtn"
                                    title="Save price"
                                >✔</button>
                            </td>
                                <td class="cost"><?= htmlspecialchars($row['cost']) ?></td>
                           
                            <td class="quantity">
                                <div class="qty-control" id="qty-<?= $row['inventory_id'] ?>" data-inventory-id="<?= $row['inventory_id'] ?>"> <button class="qty-btn minus" data-action="minus">−</button> 
                                <span class="qty-value"><?= (int)$row['quantity'] ?></span>
                                 <button class="qty-btn plus" data-action="plus">+</button> 
                                </div><br>

                                <input
                                    type="number"
                                    class="qtyInput"
                                    placeholder="+qty"
                                    min="1"
                                    style="width:60px; margin-left:6px;"
                                    data-inventory-id="<?= $row['inventory_id'] ?>"
                                    data-current-qty="<?= (int)$row['quantity'] ?>"
                                >

                                <button
                                    type="button"
                                    class="saveQtyBtn"
                                    title="Add quantity"
                                >✔</button>
                            </td>




                                <td class="critical">                                
                                <?php if( $row['is_critical']): ?>
                                    <div class="<?= $row['is_critical'] ? 'critical-row' : '' ?>">critical</div><br>
                                <?php endif; ?>
                                <?= htmlspecialchars($row['critical_stock_level']) ?>

                                </td>
                                <td class="actions">
                                    <button class="editBtn" title="Edit Product">✏️</button>
                                    <button class="deleteBtn" data-product-id="<?= $row['product_id'] ?>" title="Delete Product">🗑️</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-data">
            <td colspan="10" style="text-align:center; padding:40px;">
                    <img 
                        src="images/noProduct.png" 
                        alt="No products found"
                        style="max-width:40%; height:auto; max-height:300px; display:block; margin:0 auto;"
                    >
                </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
    </div>


<?php if($totalPages>1): ?>
<div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
<?php if($page>1): ?><button class="page-btn" data-page="<?= $page-1 ?>" style="padding: 8px 15px; border: 1px solid #ccc; background: white; cursor: pointer; border-radius: 4px;">Prev</button><?php endif; ?>
<?php for($i=1;$i<=$totalPages;$i++): ?>
<button class="page-btn <?= $i==$page?'active':'' ?>" data-page="<?= $i ?>" style="padding: 8px 15px; border: 1px solid #ccc; background: <?= $i==$page?'#2b2b2b':'white' ?>; color: <?= $i==$page?'white':'black' ?>; cursor: pointer; border-radius: 4px;"><?= $i ?></button>
<?php endfor; ?>
<?php if($page<$totalPages): ?><button class="page-btn" data-page="<?= $page+1 ?>" style="padding: 8px 15px; border: 1px solid #ccc; background: white; cursor: pointer; border-radius: 4px;">Next</button><?php endif; ?>
</div>
<?php endif; ?>

<?php if (!isset($_GET['is_ajax'])): // Only include modals/scripts on full page load ?>
        </div>
    </div>
</div>

<div id="dynamicModal" class="modal">
    <div class="modal-contentss">
        <span class="close" onclick="closeDynamicModal()">&times;</span>
        <div class="modal-header">
            <h3 id="modalTitle">Add Product</h3>
            
        </div>

        <form id="dynamicForm" method="POST" action="inventory-actions/insertproduct.php">
            <input type="hidden" name="category_id" id="categoryIdInput">
            <div id="dynamicFields"></div>
            <button type="submit" class="submitBtn">Add Product</button>
        </form>

    </div>
</div>


<div id="categoryModal" class="modal">
    <div class="modal-contents">
        <span class="close">&times;</span>
        <div class="modal-header">
        <h3 style="text-align:center;">Add Category</h3>
        </div>
        <form id="categoryForm">
            <label">Category Name:</label>
            <input type="text" name="category_name" required style="width: 100%; padding: 8px;">
            <button type="submit" style="margin-top: 10px; width:100%;background-color:black; color:white;">Add Category</button>
        </form>
    </div>
</div>

<div id="categoryListModal" class="modal category-list-modal">
    <div class="modal-overlay"></div>
    <div class="category-modal-content">
        <span class="close-category-list">&times;</span>
        <h3>Category List</h3>
        <table class="category-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="categoryTableBody">
                <?php $i = 1; while($row = $categories->fetch_assoc()): ?>
                <tr id="category-<?php echo $row['category_id']; ?>">
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $row['category_name']; ?></td>
                    <td>
                        <button class="deleteBtns" data-id="<?php echo $row['category_id']; ?>">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="toast" style="display:none; position:fixed; bottom:10px; left:50%; transform:translateX(-50%); background:#333; color:#fff; padding:8px 15px; border-radius:4px; z-index:99999999999;"></div>


<script>

const categoryModal = document.getElementById('categoryModal');
const openCategoryBtn = document.getElementById('openCategoryModal');
const closeCategoryBtn = categoryModal.querySelector('.close');
const categoryForm = document.getElementById('categoryForm');
const overlay = document.getElementById('editOverlay');
const categorySelect = document.getElementById("categorySelect");
const modal = document.getElementById("dynamicModal");
const closeBtn = modal.querySelector(".close");
const dynamicFields = document.getElementById("dynamicFields");
const dynamicForm = document.getElementById("dynamicForm");
const categoryIdInput = document.getElementById("categoryIdInput");

const categoryListBtn = document.getElementById('categoryList'); // button to open
const categoryListModal = document.getElementById('categoryListModal');
const closeCategoryList = categoryListModal.querySelector('.close-category-list');

const modalTitle = document.getElementById("modalTitle"); // new

categorySelect.addEventListener("change", async () => {
    const categoryId = categorySelect.value;

    if (!categoryId) {
        modal.style.display = "none";
        modalTitle.textContent = "Add Product"; // reset title
        return;
    }

    categoryIdInput.value = categoryId;

    // Set the modal title dynamically
    const categoryName = categorySelect.options[categorySelect.selectedIndex].text;
    modalTitle.textContent = `Add ${categoryName}`;

    try {
        const response = await fetch(`inventory-actions/fetch_categoryfields.php?category_id=${categoryId}`);
        const fields = await response.json();

        // Clear existing fields before adding new ones
        dynamicFields.innerHTML = "";

        fields.forEach(f => {
            // 1. Create Container
            const wrapper = document.createElement("div");
            wrapper.style.marginBottom = "15px";

            // 2. Create Label
            const label = document.createElement("label");
            label.innerText = f.label;
            label.style.display = "block";
            label.style.fontWeight = "600";
            label.style.marginBottom = "5px";
            label.style.fontSize = "14px";

            // 3. Create Input
            const input = document.createElement("input");
            input.name = f.name;

            if (f.type === "number") {
                // Use text type to allow decimals
                input.type = "text";
                input.placeholder = "Enter number, e.g., 45.1";

                // Restrict input to digits and only one dot
                input.addEventListener("input", () => {
                    input.value = input.value.replace(/[^0-9.]/g, ''); // remove invalid chars
                    const parts = input.value.split('.');
                    if (parts.length > 2) {
                        input.value = parts[0] + '.' + parts[1]; // only keep first dot
                    }
                });
            } else {
                input.type = f.type;
            }

            // Apply required
            if (f.required) input.required = true;

            // 4. Styling
            input.style.width = "100%";
            input.style.padding = "10px";
            input.style.border = "1px solid #ccc";
            input.style.borderRadius = "4px";
            input.style.boxSizing = "border-box";

            // 5. Assemble and Append
            wrapper.appendChild(label);
            wrapper.appendChild(input);
            dynamicFields.appendChild(wrapper);
        });

        // Show the modal after fields are generated
        modal.style.display = "block";

    } catch (err) {
        showToast("Error fetching fields. Please check your connection.", 5000);
        console.error("Fetch Error:", err);
    }
});




document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('saveQtyBtn')) {
            const button = event.target;
            const td = button.closest('td');
            
            const input = td.querySelector('.qtyInput');
            const qtySpan = td.querySelector('.qty-value');
            
            const inventoryId = input.dataset.inventoryId;
            const currentQty = parseInt(qtySpan.textContent.trim()) || 0;
            const addValue = parseInt(input.value.trim()) || 0;

            if (isNaN(addValue) || addValue === 0) {
                alert("Please enter a valid quantity.");
                return;
            }

            const newQty = currentQty + addValue;

            // 1. Update UI immediately for a "snappy" feel
            qtySpan.textContent = newQty;
            input.value = '';

            // 2. Start AJAX Update
            // Prepare the data to send to PHP
            const formData = new FormData();
            formData.append('inventory_id', inventoryId);
            formData.append('new_qty', newQty);

            // Fetch sends the data to your PHP file
            fetch('inventory-actions/update_typeQuantity.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    qtySpan.style.color = "#28a745";
                    setTimeout(() => { qtySpan.style.color = ""; }, 1000);
                    refreshInventoryModal();
                } else {
                    // Fail! Revert UI and alert
                    alert("Database Error: " + data.message);
                    qtySpan.textContent = currentQty; 
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Network error. Could not save.");
                qtySpan.textContent = currentQty; // Revert UI
            });
        }
    });
});


document.addEventListener('click', function (e) {

    if (!e.target.classList.contains('savePriceBtn')) return;

    const td = e.target.closest('.price');
    const input = td.querySelector('.percentInput');

    const productId = input.dataset.productId;
    const original  = parseFloat(input.dataset.originalPrice);
    const percent   = parseFloat(input.value);

    if (isNaN(percent)) {
        alert('Please enter percentage');
        return;
    }

    let newPrice = original + (original * percent / 100);
    newPrice = Math.round(newPrice); 

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('original_price', original);
    formData.append('percent', percent);

    fetch('inventory-actions/update_price.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(resp => {
        if (resp.trim() === 'success') {
            td.querySelector('.currentPrice').textContent = newPrice;
            input.value = '';
            input.dataset.originalPrice = newPrice;
        } else {
            alert(resp);
        }
    });
});



categoryListBtn.addEventListener('click', () => {
    categoryListModal.style.display = 'block';
});

closeCategoryList.addEventListener('click', () => {
    categoryListModal.style.display = 'none';
});

window.addEventListener('click', (e) => {
    if (e.target === categoryListModal.querySelector('.modal-overlay')) {
        categoryListModal.style.display = 'none';
    }
});
// Function to show toast messages
function showToast(message, duration = 3000) {
    toast.textContent = message;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, duration);
}

const categoryTableBody = document.getElementById('categoryTableBody');
categoryTableBody.addEventListener('click', async (e) => {
    // Only handle clicks on delete buttons
    const deleteBtn = e.target.closest('.deleteBtns'); // use singular variable here
    if (!deleteBtn) return;

    const id = deleteBtn.dataset.id; // also singular

    if (!id) return;

    if (!confirm('Are you sure you want to delete this category?')) return;

    try {
        const res = await fetch(`inventory-actions/delete_category.php?id=${id}`, {
            method: 'GET'
        });
        const data = await res.text();

        if (data === 'success') {
            // Remove row from table
            const row = document.getElementById(`category-${id}`);
            if (row) row.remove();
            showToast('Category deleted!');
        } else {
            showToast('Error deleting category!');
        }
    } catch (err) {
        console.error(err);
        showToast('Failed to delete category!');
    }
});





function showToast(message, duration = 3000) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, duration);
}


openCategoryBtn.addEventListener('click', () => categoryModal.style.display = 'flex');


closeCategoryBtn.addEventListener('click', () => categoryModal.style.display = 'none');


window.addEventListener('click', e => {
    if (e.target === categoryModal) categoryModal.style.display = 'none';
});


categoryForm.addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(categoryForm);

    try {
        const res = await fetch('inventory-actions/add_category.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message);
            categoryForm.reset();
            categoryModal.style.display = 'none';
            location.reload();

        } else {
            showToast("Error: " + data.message, 5000);
        }
    } catch (err) {
        showToast("Request failed: " + err.message, 5000);
    }
});

async function loadCategories() {
    try {
        const res = await fetch('inventory-actions/fetch_categories.php');
        const categories = await res.json();

        const select = document.getElementById('categorySelect');
        select.innerHTML = '<option value="">Add Product:</option>'; 
        categories.forEach(c => {
            const option = document.createElement('option');
            option.value = c.category_id;
            option.textContent = c.category_name;
            select.appendChild(option);
        });
    } catch (err) {
        console.error('Failed to load categories:', err);
    }
}



categorySelect.addEventListener("change", async () => {
    const categoryId = categorySelect.value;
    if (!categoryId) {
        modal.style.display = "none";
        return;
    }
 
    categoryIdInput.value = categoryId;

    try {
        
        const response = await fetch(`inventory-actions/fetch_categoryfields.php?category_id=${categoryId}`);
        const fields = await response.json();


        dynamicFields.innerHTML = "";

        fields.forEach(f => {
            const wrapper = document.createElement("div");
            wrapper.style.marginBottom = "10px";

            const label = document.createElement("label");

            label.innerText = f.label;
            label.style.display = "block";

            const input = document.createElement("input");
            input.name = f.name;
            input.type = f.type;
            if (f.required) input.required = true;
            input.style.width = "100%";
            input.style.padding = "5px";

            wrapper.appendChild(label);
            wrapper.appendChild(input);

            dynamicFields.appendChild(wrapper);
        });

 
        modal.style.display = "block";
    } catch (err) {
        showToast("Error fetching fields.", 5000);
        console.error(err);
    }
});



// Close modal
closeBtn.addEventListener("click", () => { modal.style.display = "none"; categorySelect.value = ""; });







// Submit dynamic form
dynamicForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(dynamicForm);

    try {
        const response = await fetch(dynamicForm.action, {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast("Product added successfully!");
            dynamicForm.reset();
            
            refreshInventoryModal();
            categorySelect.value = ""; 
        } else {
            showToast("Error: " + result.message, 5000);
        }
    } catch (err) {
        showToast("Request failed: " + err.message, 5000);
        console.error(err);
    }
});


// --- EDIT OVERLAY LOGIC ---

function showOverlayForm(formHtml, submitEndpoint) {
    overlay.innerHTML = formHtml;
    overlay.style.display = 'flex';

    const form = overlay.querySelector('form');

    overlay.querySelectorAll('.cancelEdit').forEach(btn => {
        btn.addEventListener('click', () => overlay.style.display = 'none');
    });

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        const formData = new FormData(this);

        fetch(`inventory-actions/${submitEndpoint}`, {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(response => {
                console.log(response);
                if (response.success) {
                    showToast('Updated successfully!');
                    overlay.style.display = 'none';
                    refreshInventoryModal(); 
                } else {
                    showToast(response.message || 'Update failed.');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Something went wrong.');
            });
    });
}
window.addEventListener('click', (e) => {
    if (e.target === overlay) {
        overlay.style.display = "none";
    }
});

const filterCategory = document.getElementById('Category');
function refreshInventoryModal(page = 1) {
    // 1. Get the category element and its value
    const filterSelect = document.getElementById('Category');
    const categoryValue = filterSelect ? filterSelect.value : '';
    
    // 2. Get search input and current page
    const query = document.getElementById('searchInput')?.value || '';
    const currentPage = page || 1;

    const tableBody = document.getElementById('inventoryTableBody');
    tableBody.innerHTML = '<tr><td colspan="10" style="text-align:center;">Loading...</td></tr>';

    // 3. Construct URL with correct variable names
    const url = `inventorysearchmodal.php?is_ajax=1` + 
                `&search=${encodeURIComponent(query)}` + 
                `&page=${currentPage}` + 
                `&category=${encodeURIComponent(categoryValue)}`;

    fetch(url)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newTableBody = doc.getElementById('inventoryTableBody');
            tableBody.innerHTML = newTableBody ? newTableBody.innerHTML : '<tr><td colspan="10" style="text-align:center;">No data found.</td></tr>';

            const newPagination = doc.querySelector('.pagination');
            const oldPagination = document.querySelector('.pagination');

            if (oldPagination) {
                oldPagination.innerHTML = newPagination ? newPagination.innerHTML : '';
            } else if (newPagination) {
                document.querySelector('.table-wrapper')?.insertAdjacentHTML('afterend', newPagination.outerHTML);
            }

            // Re-bind actions to new elements
            bindEditButtons();
            bindDeleteButtons();
            bindPaginationButtons();
        })
        .catch(err => {
            console.error('Refresh Inventory Error:', err);
            tableBody.innerHTML = '<tr><td colspan="10" style="text-align:center; color:red;">Error loading data.</td></tr>';
        });
}

document.addEventListener('DOMContentLoaded', () => {
    if (filterCategory) {
        filterCategory.addEventListener('change', () => {
            refreshInventoryModal(1);
        });
    }
});




// --- DYNAMIC BINDING FUNCTIONS ---

function bindPaginationButtons() {
    document.querySelectorAll('.page-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            refreshInventoryModal(this.dataset.page);
        });
    });
}


function bindDeleteButtons() {
    document.querySelectorAll('.deleteBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            if (!confirm('Are you sure you want to delete this product?')) return;

            fetch('./inventory-actions/delete_details.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Product deleted successfully!');
                    refreshInventoryModal(); // Full refresh after delete
                } else {
                    showToast(data.message || 'Failed to delete product');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error deleting product');
            });
        });
    });
}
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('qty-btn')) return;

        const control = e.target.closest('.qty-control');
        const qtySpan = control.querySelector('.qty-value');
        const inventoryId = control.dataset.inventoryId;

        // FIX 1: Use .trim() to remove newlines and spaces
        // FIX 2: Use || 0 as a fallback if the span is somehow empty
        let qty = parseInt(qtySpan.textContent.trim(), 10) || 0;
        
        const action = e.target.dataset.action;

        if (action === 'plus') qty++;
        if (action === 'minus' && qty > 0) qty--;

        // Update the display
        qtySpan.textContent = qty;

        control.closest('tr').classList.add('qty-updated');

        updateQuantity(inventoryId, qty);
    });
    function updateQuantity(inventoryId, quantity) {
        fetch('inventory-actions/update_quantity.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({inventory_id: inventoryId, quantity: quantity})
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('Failed to update quantity');
            }
        })
        .catch(err => console.error(err));
    }
});

function bindEditButtons() {
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function(){
const row = this.closest('tr');
            
            // 1. Clean Category & IDs
            const category = row.dataset.category?.toLowerCase().trim();
            const productId = row.dataset.productId;
            const critical = row.dataset.critical || '';
            
            // 2. Clean Product Name
            const productName = row.dataset.productName || row.querySelector('.product-name')?.textContent.trim() || '';

            // 3. Clean Price & Cost (Remove currency symbols/spaces)
            const price = row.querySelector('.price')?.textContent.replace(/[^0-9.]/g, '') || '0';
            const cost = row.querySelector('.cost')?.textContent.replace(/[^0-9.]/g, '') || '0';
            
            // 4. CLEAN QUANTITY (The specific fix for your error)
            // Target the .qty-value span specifically to avoid the buttons
            const qtyElement = row.querySelector('.qty-value');
            const quantity = qtyElement ? qtyElement.textContent.trim() : '0';

            // Category: Tire (9)
            if(category === 'tire'){
                const tireId = row.dataset.tireId;
                if(!tireId){ showToast('No tire_id'); return; }

                fetch(`inventory-actions/fetch_tires.php?tire_id=${tireId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Tire</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="tire_id" value="${data.tire_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Brand:<input name="brand" value="${data.brand || ''}"></label>
                                <label>Size:<input name="size" value="${data.size || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Pattern:<input name="pattern" value="${data.pattern || ''}"></label>
                                <label>Made:<input name="made" value="${data.made || ''}"></label>
                            </div>

                            <div class="form-row">
                                <label>Critical:<input name="critical" value="${critical || ''}"></label>
                            </div>

                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_tires.php');
                }).catch(err => { console.error(err); showToast('Error fetching tire'); });
                return;
            }

            // Category: Accessories (1)
            if(category === 'accessories'){
                const accessoriesId = row.dataset.accessoriesId;
                if(!accessoriesId){ showToast('No accessories_id'); return; }

                fetch(`inventory-actions/fetch_accessories.php?accessories_id=${accessoriesId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Accessories</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="accessories_id" value="${data.accessories_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Type:<input name="typeofaccessories" value="${data.typeofaccessories || ''}"></label>
                                <label>Brand:<input name="brand" value="${data.brand || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Model Number:<input name="model_number" value="${data.model_number || ''}"></label>
                                <label>Material:<input name="material" value="${data.material || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Fitment:<input name="fitment" value="${data.fitment_details || ''}"></label>
                                <label>Critical:<input name="critical" value="${critical || ''}"></label>
                            </div>
                            

                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_accessories.php');
                }).catch(err => { console.error(err); showToast('Error fetching accessories'); });
                return;
            }

            // Category: Battery (2)
            if(category === 'battery'){
                const batteryId = row.dataset.batteryId;
                if(!batteryId){ showToast('No battery_id'); return; }

                fetch(`inventory-actions/fetch_battery.php?battery_id=${batteryId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Battery</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="battery_id" value="${data.battery_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Brand:<input name="brand" value="${data.brand || ''}"></label>
                                <label>Voltage:<input name="voltage" value="${data.voltage || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Model Number:<input name="model_number" value="${data.model_number || ''}"></label>
                                <label>Capacity:<input name="capacity" value="${data.capacity || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Critical:<input name="critical" value="${critical || ''}"></label>
                            </div>
                           

                             
                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_battery.php');
                }).catch(err => { console.error(err); showToast('Error fetching battery'); });
                return;
            }

            // Category: Engine Oil (3)
            if(category === 'engine oil'){
                const oilId = row.dataset.oilId;
                if(!oilId){ showToast('No oil_id'); return; }

                fetch(`inventory-actions/fetch_engineoil.php?oil_id=${oilId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Engine Oil</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="oil_id" value="${data.oil_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Brand:<input name="brand" value="${data.brand || ''}"></label>
                                <label>Oil Type:<input name="oiltype" value="${data.oiltype || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Capacity:<input name="capacity" value="${data.capacity || ''}"></label>
                                <label>Critical:<input name="critical" value="${critical || ''}"></label>
                            </div>
                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_engineoil.php');
                }).catch(err => { console.error(err); showToast('Error fetching engine oil'); });
                return;
            }

            // Category: Filter
            if(category === 'filter'){
                const filterId = row.dataset.filterId;
                if(!filterId){ showToast('No filter_id'); return; }

                fetch(`inventory-actions/fetch_filter.php?filter_id=${filterId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Filter</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="filter_id" value="${data.filter_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Type:<input name="typeoffilter" value="${data.typeoffilter || ''}"></label>
                                  <label>Critical:<input name="critical" value="${critical || ''}"></label>
                            </div>
                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_filter.php');
                }).catch(err => { console.error(err); showToast('Error fetching filter'); });
                return;
            }
            
            // Category: Lugnuts
            if(category === 'lugnuts'){
                const lugnutId = row.dataset.lugnutId;
                if(!lugnutId){showToast('No lugnut Id'); return;}

                fetch(`inventory-actions/fetch_lugnuts.php?lugnut_id=${lugnutId}`)
                .then(res =>res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Lugnut</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="lugnut_id" value="${data.lugnut_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Type of Lugnut:<input name="typeoflugnut" value="${data.typeoflugnut || ''}"></label>
                                <label>Size:<input name="size" value="${data.size || ''}"></label>
                            </div>

                            <div class="form-row">
                              <label>Critical:<input name="critical" value="${critical || ''}"></label>
                            </div>

                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml,'update_lugnuts.php');
                }).catch(err => { console.error(err); showToast('Error fetching lugnut'); });
                return;
            }
            
            // Category: Mechanical Product (7)
            if(category === 'mechanicalproduct'){
                const mechanicalId = row.dataset.mechanicalId;
                if(!mechanicalId){showToast('No mechanical Id'); return;}
                
                fetch(`inventory-actions/fetch_mechanicalproduct.php?mechanical_id=${mechanicalId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Mechanical Product</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="mechanical_id" value="${data.mechanical_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Part Name:<input name="part_name" value="${data.part_name || ''}"></label>
                                <label>Made:<input name="made" value="${data.made || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Model:<input name="model" value="${data.model || ''}"></label>
                                <label>Technical Spec:<input name="technical_spec" value="${data.technical_spec || ''}"></label>
                            </div>

                            <div class="form-row">
                            <label>Critical:<input name="critical" value="${critical || ''}"></label>

                            </div>


                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml,'update_mechanicalproduct.php');
                }).catch(err => {console.error(err); showToast('Error fetching mechanical side');});
                return;
            }
            
            // Category: Wheel Weights (11)
            if(category === 'wheelweights'){
                const wheelId = row.dataset.wheelId; 
                if(!wheelId){ showToast('No wheelweights Id'); return; }

                fetch(`inventory-actions/fetch_wheelweights.php?wheel_id=${wheelId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Wheel Weights</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="wheel_id" value="${data.wheel_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Model:<input name="model" value="${data.model || ''}"></label>
                                <label>Weight:<input name="weight" value="${data.weight || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Material:<input name="material" value="${data.material || ''}"></label>
                                <label>Critical:<input name="critical" value="${critical || ''}"></label>

                            </div>
                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_wheelweights.php');
                }).catch(err => { console.error(err); showToast('Error fetching wheelweights'); });
                return;
            }
            
            // Category: Tire Valve (10)
            if(category === 'tire valve'){
                const tirevalveId = row.dataset.tirevalveId;
                if(!tirevalveId){ showToast('No tirevalve ID'); return; }

                fetch(`inventory-actions/fetch_tirevalve.php?tirevalve_id=${tirevalveId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Tire Valve</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="tirevalve_id" value="${data.tirevalve_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Valve Type:<input name="valve_type" value="${data.valve_type || ''}"></label>
                                <label>Material:<input name="material" value="${data.material || ''}"></label>
                            </div>
                            <div class="form-row">
                            <label>Color:<input name="color" value="${data.color || ''}"></label>
                            <label>Critical:<input name="critical" value="${critical || ''}"></label>

                            </div>
                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_tirevalve.php');
                }).catch(err => { console.error(err); showToast('Error fetching tirevalve'); });
                return;
            }
            
            // Category: Motorcycle Tires (12)
            if(category === 'motorcycle tires'){
                const motortireId = row.dataset.motortireId;
                if(!motortireId){ showToast('No motortire ID'); return; }

                fetch(`inventory-actions/fetch_motorcycletire.php?motortire_id=${motortireId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Motorcycle Tire</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="motortire_id" value="${data.motortire_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Brand:<input name="brand" value="${data.brand || ''}"></label>
                                <label>Model:<input name="model" value="${data.model || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Type:<input name="type" value="${data.type || ''}"></label>
                                <label>Size:<input name="size" value="${data.size || ''}"></label>
                            </div>

                            <div class="form-row">
                            <label>Critical:<input name="critical" value="${critical || ''}"></label>

                            </div>


                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_motorcycletire.php');
                }).catch(err => { console.error(err); showToast('Error fetching motorcycle'); });
                return;
            }
            
            // Category: Others
            if(category === 'others'){
                const otherId = row.dataset.otherId;
                if(!otherId){ showToast('No other Id'); return; }

                fetch(`inventory-actions/fetch_others.php?other_id=${otherId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Other Product</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="other_id" value="${data.id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Description:<input name="description" value="${data.description || ''}"></label>
                                <label>Critical:<input name="critical" value="${critical || ''}"></label>

                            </div>
                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_other.php');
                }).catch(err => { console.error(err); showToast('Error fetching other details'); });
                return;
            }

            if (category === 'mags') {
    const magsId = row.dataset.magsId; // Ensure your row has data-mags-id
    if (!magsId) { showToast('No Mags ID'); return; }

    fetch(`inventory-actions/fetch_mags.php?mags_id=${magsId}`)
    .then(res => res.json())
    .then(data => {
        const formHtml = `
            <form class="inlineEditForm">
                <h3>Edit Mags Details</h3>
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="mags_id" value="${data.mags_id}">
                
                <div class="form-row">
                    <label>Product Name:<input name="product_name" value="${productName}"></label>
                    <label>Brand:<input name="brand" value="${data.brand || ''}"></label>
                </div>
                
                <div class="form-row">
                    <label>Model:<input name="model" value="${data.model || ''}"></label>
                    <label>Size (Inch):<input type="number" name="size" value="${data.size || ''}"></label>
                </div>

                <div class="form-row">
                    <label>Material:<input name="material" value="${data.material || ''}"></label>
                    <label>Price:<input type="number" name="price" value="${price}"></label>
                </div>

                <div class="form-row">
                    <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                    <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                </div>
                <div class="form-row">
                <label>Critical:<input name="critical" value="${critical || ''}"></label>
                </div>


                <div class="form-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="cancelEdit">Cancel</button>
                </div>
            </form>`;
        showOverlayForm(formHtml, 'update_mags.php');
    })
    .catch(err => { 
        console.error(err); 
        showToast('Error fetching Mags details'); 
    });
    return;
}

            // Category: Nitrogen
            if(category === 'nitrogen'){
                const nitrogenId = row.dataset.nitrogenId;
                if(!nitrogenId){ showToast('No Nitrogen ID'); return; }

                fetch(`inventory-actions/fetch_nitrogen.php?nitrogen_id=${nitrogenId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Nitrogen Product</h3>
                            <input type="hidden" name="product_id" value="${productId}">
                            <input type="hidden" name="nitrogen_id" value="${data.nitrogen_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${productName}"></label>
                                <label>Price:<input type="number" name="price" value="${price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Percentage:<input name="nitrogen_percentage" value="${data.nitrogen_percentage || ''}"></label>
                                <label>Input Date:<input name="input_date" type="date" value="${data.input_date || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Vehicle Type:<input name="type_of_vehicle" value="${data.type_of_vehicle || ''}"></label>
                                <label>Critical:<input name="critical" value="${critical || ''}"></label>
                            </div>
                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_nitrogen.php');
                }).catch(err => { console.error(err); showToast('Error fetching Nitrogen details'); });
                return;
            }
            
            // Generic Product
            if(productId){
                fetch(`inventory-actions/fetch_generic.php?product_id=${productId}`)
                .then(res => res.json())
                .then(data => {
                    const formHtml = `
                        <form class="inlineEditForm">
                            <h3>Edit Generic Product</h3>
                            <input type="hidden" name="product_id" value="${data.product_id}">
                            <div class="form-row">
                                <label>Product Name:<input name="product_name" value="${data.product_name || productName}"></label>
                                <label>Price:<input type="number" name="price" value="${data.price || price}"></label>
                            </div>
                            <div class="form-row">
                                <label>Cost:<input type="number" name="cost" value="${data.cost || cost}"></label>
                                <label>Quantity:<input type="number" name="quantity" value="${quantity}"></label>
                            </div>
                            <div class="form-row">
                                <label>Detail 1:<input name="detail1" value="${data.detail1 || ''}"></label>
                                <label>Detail 2:<input name="detail2" value="${data.detail2 || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Detail 3:<input name="detail3" value="${data.detail3 || ''}"></label>
                                <label>Detail 4:<input name="detail4" value="${data.detail4 || ''}"></label>
                            </div>
                            <div class="form-row">
                                <label>Detail 5:<input name="detail5" value="${data.detail5 || ''}"></label>
                                <label>Detail 6:<input name="detail6" value="${data.detail6 || ''}"></label>
                            </div>

                            <div class="form-row">
                            <label>Critical:<input name="critical" value="${critical || ''}"></label>
                            </div>
                            <div class="form-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </div>
                        </form>`;
                    showOverlayForm(formHtml, 'update_generic.php');
                }).catch(err => { console.error(err); showToast('Error loading generic editor.'); });
                return;
            }
            showToast('Missing category and product ID.');
        });
    });
}


// let searchTimeout;
// document.getElementById('searchInput').addEventListener('input', function() {
//     const query = this.value;
//     clearTimeout(searchTimeout);

//     searchTimeout = setTimeout(() => {
//         refreshInventoryModal(1); 
//     }, 0); 
// });

document.getElementById('searchInput').addEventListener('input', function() {
    refreshInventoryModal(1);
});




bindEditButtons();
bindDeleteButtons();
bindPaginationButtons();

</script>
<script src="assets/js/sidebar-toggle.js"></script>
</body>
</html>

<?php endif;     ?>