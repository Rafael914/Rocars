<?php
require_once 'includes/config.php';


$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
?>


<button id="categoryList" class="btn category-list">Category List</button>

<!-- Modal -->
<div id="categoryModal" class="modal" style="display:none; position:absolute; top:50px; left:50px; background:#fff; border:1px solid #ccc; padding:20px; z-index:1000; width:400px; border-radius:6px;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Category List</h3>
        <table class="category-table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="categoryTableBody">
                <?php $i=1; while($row = $categories->fetch_assoc()): ?>
                <tr id="category-<?php echo $row['category_id']; ?>">
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $row['category_name']; ?></td>
                    <td>
                        <button class="deleteBtn" data-id="<?php echo $row['category_id']; ?>">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="toast" style="display:none; position:fixed; bottom:10px; left:50%; transform:translateX(-50%); background:#333; color:#fff; padding:8px 15px; border-radius:4px; z-index:2000;"></div>
