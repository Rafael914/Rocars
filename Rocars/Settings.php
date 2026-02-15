<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'settings-actions/language.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('settings'); ?></title>
</head>
<body>
    <div class="settings-container">
        <h1><?php echo translate('settings'); ?></h1>
        <a href="settings-actions/AddUserAccount.php" class="btn"><?php echo translate('add_account'); ?></a><br>
        <a href="settings-actions/language-settings.php" class="btn"><?php echo translate('language'); ?></a><br>
        <a href="settings-actions/time-and-date-settings.php" class="btn"><?php echo translate('time_date'); ?></a><br>
        <a href="settings-actions/change-password.php" class="btn"><?php echo translate('Change Password'); ?></a>
    </div>

    
</body>
</html>