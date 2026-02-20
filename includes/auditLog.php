<?php
function auditLog($conn,$user_id, $action, $table_name, $record_id, $description = ''){
    $stmt = $conn->prepare("INSERT INTO audit_logs(user_id, action, table_name, record_id,description)
                            VALUES(?,?,?,?,?)");
    $stmt->bind_param("issis", 
                    $user_id, 
                    $action, 
                    $table_name, 
                    $record_id, 
                    $description);

    $stmt->execute();
    $stmt->close();
}



?>