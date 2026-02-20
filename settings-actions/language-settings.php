<?php
session_start();
require_once '../includes/config.php';
require_once '../settings-actions/language.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];

    $success_message = translate('language_updated');
}


$current_language = $_SESSION['language'] ?? 'en';
?>

<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('language_settings'); ?></title>
</head>
<body>
    <div class="settings-container">
        <h1><?php echo translate('language_settings'); ?></h1>
        
        <?php if (isset($success_message)): ?>
            <p style="color: green;"><?php echo $success_message; ?></p>
        <?php endif; ?>
        
        <p><?php echo translate('select_language'); ?></p>
        
        <form method="POST" action="">
            <select name="language">
                <option value="en" <?php echo ($current_language == 'en') ? 'selected' : ''; ?>>English</option>
                <option value="es" <?php echo ($current_language == 'es') ? 'selected' : ''; ?>>Español</option>
                <option value="fr" <?php echo ($current_language == 'fr') ? 'selected' : ''; ?>>Français</option>
                <option value="fl" <?php echo ($current_language == 'fl') ? 'selected' : ''; ?>>Filipino</option>
            </select>
            <button type="submit"><?php echo translate('save'); ?></button>
        </form>
        
        <br>
        <a href="../Settings.php">Back to Settings</a>
    </div>
</body>
</html>