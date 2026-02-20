<?php
require_once 'includes/config.php';

// Fetch branches for dropdown
$branchQuery = "SELECT branch_id, branch_name FROM branches";
$branchResult = mysqli_query($conn, $branchQuery);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_id = $_POST['branch_id'];
    $mechanic_name = $_POST['mechanic_name'];
    $contact_number = $_POST['contact_number'];

    $query = "INSERT INTO mechanics (branch_id, mechanic_name, contact_number)
              VALUES ('$branch_id', '$mechanic_name', '$contact_number')";
    
    if (mysqli_query($conn, $query)) {
        echo "<p style='color:green;'>Mechanic added successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
    }
}
?>

<form method="POST">
  <label for="branch_id">Branch:</label>
  <select name="branch_id" id="branch_id" required>
      <option value="" disabled selected>Select Branch</option>
      <?php while($brow = mysqli_fetch_assoc($branchResult)): ?>
          <option value="<?= htmlspecialchars($brow['branch_id']); ?>">
              <?= htmlspecialchars($brow['branch_name']); ?>
          </option>
      <?php endwhile; ?>
  </select><br><br>

  <label>Mechanic Name:</label>
  <input type="text" name="mechanic_name" required><br><br>

  <label>Contact Number:</label>
  <input type="text" name="contact_number"><br><br>

  <button type="submit">Add Mechanic</button>
</form>
  