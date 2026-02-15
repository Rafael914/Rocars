<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/auditLog.php';

$user_id = $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = intval($_POST['product_id'] ?? 0);

    if ($id > 0) {
        // 1. Fetch details BEFORE deleting to build the log message
        // This joins your 'products' table (p) with your 'categories' table (c)
        $infoSql = "SELECT p.product_name, c.category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.cat_id = c.category_id 
                    WHERE p.product_id = ?";
        
        $infoStmt = $conn->prepare($infoSql);
        $infoStmt->bind_param("i", $id);
        $infoStmt->execute();
        $result = $infoStmt->get_result();
        $product = $result->fetch_assoc();

        if ($product) {
            $productName = $product['product_name'];
            $categoryName = $product['category_name'] ?? 'General'; // Fallback if category is missing
            
            // Define the log message using Category Name instead of ID
            $logMessage = "Deleted product: $productName from Category: $categoryName";

            // 2. Now perform the actual deletion
            $delStmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
            $delStmt->bind_param("i", $id);

            if ($delStmt->execute()) {
                // 3. Log the action only after successful deletion
                auditLog($conn, $user_id, 'DELETE', 'products', $id, $logMessage);
                
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting product']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>