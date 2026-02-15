<?php
session_start();
$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

if ($branch_id > 0) {
    $_SESSION['branch_id'] = $branch_id;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}