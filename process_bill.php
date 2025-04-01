<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

// Ensure user is logged in
checkAdminOrUserLogin();

// Handle bill creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get the latest order
        $orderStmt = $pdo->prepare("
            SELECT order_id, order_total 
            FROM pos_order 
            ORDER BY order_id DESC 
            LIMIT 1
        ");
        $orderStmt->execute();
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception('No order found');
        }

        // Insert bill record
        $billStmt = $pdo->prepare("
            INSERT INTO pos_bills (
                order_id, 
                customer_name, 
                bill_status, 
                bill_amount, 
                bill_datetime
            ) VALUES (?, ?, 'Unpaid', ?, NOW())
        ");
        $billStmt->execute([
            $order['order_id'],
            $_POST['customer_name'] ?? 'Anonymous',
            $order['order_total']
        ]);

        // Update order status to Billed
        $updateOrderStmt = $pdo->prepare("
            UPDATE pos_order 
            SET order_status = 'Billed' 
            WHERE order_id = ?
        ");
        $updateOrderStmt->execute([$order['order_id']]);

        // Commit transaction
        $pdo->commit();

        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Bill created successfully',
            'order_id' => $order['order_id']
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Return error response
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}