<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}


function isLoggedin(){
    return isset($_SESSION['user_id']);
}

// function isAdmin(){
//     return $_SESSION['role'] === 'Admin';
// }

// function isMember(){
//     return $_SESSION['role'] === 'cashier';
//     return $_SESSION['role'] === 'inventory staff';
    

// }

function generateSecurePassword($length = 10){
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = "";
    $maxIndex = strlen($characters) - 1;
    for($i = 0; $i < $length; $i++){
        $password = $characters[random_int(0, $maxIndex)];

    }
    return $password;
}

function sanitizeInput($data){
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function secureCookies(){
    session_start([
    'cookie_lifetime' => 86400,
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true,
]);
}
?>