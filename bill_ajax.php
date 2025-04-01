<?php
require_once 'db_connect.php';
require_once 'auth_function.php';
checkAdminOrUserLogin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle bill creation
    if (isset($input['bill_number'])) {
        handleBillCreation($input);
    }
    else {
        throw new Exception("Invalid request");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleBillCreation($input) {
    global $pdo;
    
    $pdo->beginTransaction();
    
    try {
        // Create bill
        $stmt = $pdo->prepare("INSERT INTO pos_bill 
                              (bill_number, bill_total, bill_created_by) 
                              VALUES (?, ?, ?)");
        $stmt->execute([
            $input['bill_number'],
            $input['bill_total'],
            $input['bill_created_by']
        ]);
        
        $billId = $pdo->lastInsertId();
        
        // Add bill items
        $stmt = $pdo->prepare("INSERT INTO pos_bill_items 
                              (bill_id, product_name, product_qty, product_price) 
                              VALUES (?, ?, ?, ?)");
        
        foreach ($input['items'] as $item) {
            $stmt->execute([
                $billId,
                $item['product_name'],
                $item['product_qty'],
                $item['product_price']
            ]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'bill_id' => $billId,
            'bill_number' => $input['bill_number']
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>