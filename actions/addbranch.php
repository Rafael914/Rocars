<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json'); // ✅ fixed typo

if(!isset($_POST['branch_name']) || empty(trim($_POST['branch_name']))) {
    echo json_encode(['success' => false, 'message' => 'Branch name is required']);
    exit;
}

$branchName = trim($_POST['branch_name']);

$stmt = $conn->prepare("INSERT INTO branches (branch_name) VALUES (?)");
$stmt->bind_param("s", $branchName);

if($stmt->execute()){
    echo json_encode(['success' => true, 'message' => 'Branch added successfully']);
}else{
    echo json_encode(['success' => false, 'message' => 'Insert failed: '.$stmt->error]);
}

$stmt->close();
$conn->close();
exit;
?>
