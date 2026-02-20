<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Access the database connection globally
global $conn;

$branch_id = $_SESSION['branch_id'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20; // Set limit for products display, e.g., 20 per page
$offset = ($page - 1) * $limit;

// Filters that will drive the pagination
$search = trim($_GET['search'] ?? '');
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '9';

// Initial Queries for Dropdowns
$mechanicQuery = $conn->query("SELECT mechanic_id, mechanic_name FROM mechanics WHERE branch_id = '$branch_id'");
$categoryQuery = $conn->query("SELECT category_id, category_name FROM categories");
$catQuery = $conn->query("SELECT category_id, category_name FROM categories"); 



?>
<script>
    // Make branch_id available globally for JS
    window.branch_id = <?php echo $branch_id_js; ?>;
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale</title>
    <link rel= "stylesheet" href ="assets/css/getProducts.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">

        <div class="topbar">
            <i class="fa-solid fa-bars toggle" id="toggleBtn" aria-label="Toggle sidebar"></i>
            <h1>Point of Sale</h1>
            <div class="logo">
                <img src="images/rocarsn.png" alt="Rocars" class="logo-img">
            </div>
        </div>

        <div class="product-section">

            <form method="GET" class="filters" id="productFilterForm">
                <input type="text" name="search" id="searchInput" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                <select name="category" id="Category">
                    <option value="">All Categories</option>
                    <?php
                    if ($categoryQuery) {
                        $categoryQuery->data_seek(0); 
                        while($category = $categoryQuery->fetch_assoc()):
                    ?>
                    <option value="<?= $category['category_id'] ?>" 
                        <?= (string)$category_filter === (string)$category['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['category_name']); ?>
                    </option>
                    <?php endwhile; } ?>
                </select>
                <button type="submit" style="display:none;" id="submitFilter"></button>
                <button type="button" id="quickService" class="quickbtn">Quick Service Entry</button>
            </form>

            <div class="content-wrapper">
                
                <div class="inventory-container" id="inventoryContainer">
                    
                    <div class="product-grid">
                        <?php if ($productResult && $productResult->num_rows > 0): ?>
                            <?php while ($product = $productResult->fetch_assoc()): ?>
                            <div class="product-card" data-product-id="<?= $product['product_id'] ?>">
                                <h4><?= htmlspecialchars($product['product_name']) ?></h4>
                                <p>₱<?= number_format($product['price'], 2) ?></p>
                                <button class="add-to-cart-btn" data-product-id="<?= $product['product_id'] ?>">Add</button>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-results">No products found matching the criteria.</div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
        
                        $build_query = fn($p) => http_build_query(array_merge($_GET, ['page' => $p]));
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="?<?= $build_query(1) ?>" class="page-btn">« First</a>
                            <a href="?<?= $build_query($page - 1) ?>" class="page-btn">‹ Prev</a>
                        <?php endif; ?>

                        <?php if ($start_page > 1): ?>
                            <span style="padding: 0 5px;">...</span>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?<?= $build_query($i) ?>" 
                               class="page-btn <?= $i==$page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($end_page < $totalPages): ?>
                            <span style="padding: 0 5px;">...</span>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= $build_query($page + 1) ?>" class="page-btn">Next ›</a>
                            <a href="?<?= $build_query($totalPages) ?>" class="page-btn">Last »</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    </div>
                    
                <div class="cart-container">
                    <div class="cart-sidebar">
                        <h2>Cart (<span id="cartCount">0</span>)</h2>
                        <div class="cart-items" id="cartItems"></div>
                        <hr>
                        <div class="parent-container">
                            <div class="cart-summary">
                                <div class="input-group">
                                    <label class="required">Select Technician</label>
                                    <select name="mech_id" id="mech_id" required>
                                        <option value="" disabled selected>Technician</option>
                                        <?php 
                                        if ($mechanicQuery) {
                                            $mechanicQuery->data_seek(0);
                                            while($row = $mechanicQuery->fetch_assoc()): 
                                        ?>
                                        <option value="<?php echo $row['mechanic_id'] ?>">
                                            <?php echo htmlspecialchars_decode($row['mechanic_name']) ?>
                                        </option>
                                        <?php endwhile; } ?>
                                    </select>
                                    <div id="saleErrors" style="color:red; text-align:center; font-weight:bold;"></div>
                                    <label class="required">SI Number</label>
                                    <input type="text" id="si_number" placeholder="Enter SI Number" required>
                                    <div class="autocomplete-wrapper">
                                        <label>Plate Number</label>
                                        <input type="text" name="plate_number" id="plateNoCart" class="plateNo" placeholder="Enter plate number" autocomplete="off">
                                        <div id="suggestionsCart" class="suggestions"></div>
                                    </div>

                                    <label>Customer Name</label>
                                    <input type="text" id="customerNameCart" placeholder="Enter customer name">
                                    <label>Vehicle</label>
                                    <input type="text" id="vehicleCart" placeholder="Enter vehicle model">
                                    <label>Phone Number</label>
                                    <input type="text" id="customerPhoneCart" placeholder="Enter phone number">
                                    <label>Odometer</label>
                                    <input type="text" id="odometerCart" placeholder="Enter mileage">
                                    <label>Remarks</label>
                                    <input type="text" id="remarks" placeholder="Enter Remarks">
                                    <label for="">Description</label>
                                    <input type="text" name="productDescription" placeholder="description">
                                    <label>Discrepancy</label>
                                    <input type="text" id="discrepancy" placeholder="Enter discrepancy">
                                    <label>Skilled Incentive</label>
                                    <input type="number" id="skilled" min="0" value="" placeholder="Enter skilled incentive">
                                    <label>Frontline Incentive</label>
                                    <input type="number" id="frontline" min="0" value="" placeholder="Enter frontline incentive">
                                    
                                    <label>Payment Method</label>
                                    <select id="paymentMethod">
                                        <option>Cash</option>
                                        <option>Credit Card</option>
                                        <option>Debit Card</option>
                                        <option>G-Cash</option>
                                        <option>Bank Transfer</option>
                                    </select>
                                    <h1>Service</h1>
                                    <label for="">Service Description</label>
                                    <input type="text" name="serviceDescription"  placeholder="Enter Service Description">
                                    <label for="">Cost</label>
                                    <input type="number" name="serviceCost" placeholder="Enter Service Cost">
                                </div>

                                <hr>
                                <p><strong>Product Subtotal:</strong> ₱<span id="subtotal">0.00</span></p>

                                <div id="saleError" style="color:red; text-align:center; font-weight:bold;"></div>

                                <button class="checkout-btn" id="checkoutBtn">Complete Sale</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<div id="openQuickService">
    <div class="serviceModal">
        <span id="closeQuickService">&times;</span>
        <form id="serviceform">
            <label>Select Technician</label>
            <select name="mechanic_id" id="mechanic_id" required>
                <option value="" disabled selected>Technician</option>
                <?php 
                if ($mechanicQuery) $mechanicQuery->data_seek(0);
                while($row = $mechanicQuery->fetch_assoc()): 
                ?>
                <option value="<?= $row['mechanic_id'] ?>">
                    <?= htmlspecialchars_decode($row['mechanic_name']) ?>
                </option>
                <?php endwhile; ?>
            </select><br>
            <label>Plate Number</label>
            <input type="text" name="plate_number" id="plateNoModal" class="plateNo" placeholder="Enter plate number" autocomplete="off" required>
            <div id="suggestionsModal" class="suggestions"></div>

            <label>Customer Name</label>
            <input type="text" id="customerNameModal" name="customer_name" required>
            <label>Vehicle</label>
            <input type="text" id="vehicleModal" name="vehicle" placeholder="Vehicle Details" required>
            <label>Odometer</label>
            <input type="text" id="odometerModal" name="odometer">
            <label>Phone Number</label>
            <input type="text" id="cpModal" name="cp_number">
            <label>Description</label>
            <input type="text" name="description">
            <label>SI number</label>
            <input type="text" name="si_number"><br>
            <label>Category</label>
            <input type="hidden" name="category" value="services"> <!-- Added hidden field -->
            <label>Total Amount</label>
            <input type="number" name="total_amount"><br>
            <label>Remarks</label>
            <input type="text" name="remarks">
            <label>Discrepancy</label>
            <input type="text" name="discrepancy">
            <label>Front Incentive</label>
            <input type="number" name="front_incentive">
            <label>Skill Incentive</label>
            <input type="number" name="skill_incentive">
            <label>Payment Method</label>
            <select name="payment_method">
            <option>Cash</option>
            <option>Credit Card</option>
            <option>Debit Card</option>
            <option>G-Cash</option>
            </select>
            <button type="submit">Submit</button>
        </form>
    </div>
</div>

<div id="message" style="background-color:black; color:white;"></div>

    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <div id="receiptContent"></div>
            <div class="no-print" style="text-align: center; margin-top: 20px; display: flex; justify-content: space-around;">
                <button onclick="printReceipt()" style="padding: 10px 20px; background-color: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">🖨️ Print Receipt</button>
                <button onclick="document.getElementById('receiptModal').style.display='none'" style="padding: 10px 20px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">Close</button>
            </div>
        </div>
    </div>


    <script>
        // JS to handle form submission for filtering (simulating the previous JS functionality)
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('submitFilter').click();
            }
        });
        document.getElementById('Category').addEventListener('change', function() {
            document.getElementById('submitFilter').click();
        });

        const branch_id = <?= json_encode($_SESSION['branch_id']); ?>;

    </script>


    <script src="assets/js/getProducts.js"></script>
    <script src="assets/js/sidebar-toggle.js"></script>
</body>
</html>