<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['timezone'])) {
        $_SESSION['timezone'] = $_POST['timezone'];
    }
    if (isset($_POST['date_format'])) {
        $_SESSION['date_format'] = $_POST['date_format'];
    }
    if (isset($_POST['time_format'])) {
        $_SESSION['time_format'] = $_POST['time_format'];
    }
    if (isset($_POST['custom_date']) && isset($_POST['custom_time'])) {
        $_SESSION['custom_date'] = $_POST['custom_date'];
        $_SESSION['custom_time'] = $_POST['custom_time'];
    }
    $success_message = "Time and Date Updated Successfully!";
}

$current_timezone = $_SESSION['timezone'] ?? 'Asia/Manila';
$current_date_format = $_SESSION['date_format'] ?? 'Y-m-d';
$current_time_format = $_SESSION['time_format'] ?? '25';

date_default_timezone_set($current_timezone);


if (isset($_SESSION['custom_date']) && isset($_SESSION['custom_time'])) {
    $custom_datetime = $_SESSION['custom_date'] . ' ' . $_SESSION['custom_time'];
    $timestamp = strtotime($custom_datetime);
} else {
    $timestamp = time();
}

if ($current_time_format == '12') {
    $time_display = date('h:i A', $timestamp);
} else {
    $time_display = date('H:i', $timestamp);
}

if ($current_date_format == 'Y-m-d') {
    $date_display = date('Y-m-d', $timestamp);
} elseif ($current_date_format == 'm/d/Y') {
    $date_display = date('m/d/Y', $timestamp);
} else {
    $date_display = date('d/m/Y', $timestamp);
}


$input_date = isset($_SESSION['custom_date']) ? $_SESSION['custom_date'] : date('Y-m-d');
$input_time = isset($_SESSION['custom_time']) ? $_SESSION['custom_time'] : date('H:i');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date and Time</title>
    
</head>
<body>
    <div class="settings-container">
        <h1>Date and Time</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <div class="current-display">
            <p><strong>Current Date & Time: <?php echo $date_display . ' ' . $time_display; ?></strong></p>
        </div>
        
        <form method="POST" action="">
            <div class="date-time-inputs">
                <div class="form-group">
                    <label for="custom_date">Date</label>
                    <input type="date" name="custom_date" id="custom_date" value="<?php echo $input_date; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="custom_time">Time</label>
                    <input type="time" name="custom_time" id="custom_time" value="<?php echo $input_time; ?>" required>
                </div>
            </div>
            
            <div class="settings-grid">
                <div class="form-group">
                    <label for="timezone">Timezone</label>
                    <select name="timezone" id="timezone">
                        <option value="Asia/Manila" <?php echo ($current_timezone == 'Asia/Manila') ? 'selected' : ''; ?>>Philippines</option>
                        <option value="America/New_York" <?php echo ($current_timezone == 'America/New_York') ? 'selected' : ''; ?>>US Eastern</option>
                        <option value="America/Los_Angeles" <?php echo ($current_timezone == 'America/Los_Angeles') ? 'selected' : ''; ?>>US Pacific</option>
                        <option value="Europe/London" <?php echo ($current_timezone == 'Europe/London') ? 'selected' : ''; ?>>London</option>
                        <option value="Asia/Tokyo" <?php echo ($current_timezone == 'Asia/Tokyo') ? 'selected' : ''; ?>>Tokyo</option>
                        <option value="Asia/Singapore" <?php echo ($current_timezone == 'Asia/Singapore') ? 'selected' : ''; ?>>Singapore</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_format">Date Format</label>
                    <select name="date_format" id="date_format">
                        <option value="Y-m-d" <?php echo ($current_date_format == 'Y-m-d') ? 'selected' : ''; ?>>(2025-12-15) </option>
                        <option value="m/d/Y" <?php echo ($current_date_format == 'm/d/Y') ? 'selected' : ''; ?>>(12/15/2025) </option>
                        <option value="d/m/Y" <?php echo ($current_date_format == 'd/m/Y') ? 'selected' : ''; ?>>(15/12/2025) </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="time_format">Time Format</label>
                    <select name="time_format" id="time_format">
                        <option value="24" <?php echo ($current_time_format == '24') ? 'selected' : ''; ?>>24-hour (14:30)</option>
                        <option value="12" <?php echo ($current_time_format == '12') ? 'selected' : ''; ?>>12-hour (2:30 PM)</option>
                    </select>
                </div>
            </div>
            
            <button type="submit">Save Settings</button>
        </form>
        
        <a href="../settings.php" class="back-link">Back to Settings</a>
    </div>
</body>
</html>