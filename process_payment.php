<?php
require_once 'db_connect.php';
require_once 'auth_function.php';
checkAdminOrUserLogin();

header('Content-Type: application/json');

try {
    // Validate input
    $required = ['order_id', 'order_total', 'payment_mode', 'amount_received'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $orderId = (int)$_POST['order_id'];
    $orderTotal = (float)$_POST['order_total'];
    $amountReceived = (float)$_POST['amount_received'];
    $paymentMode = $_POST['payment_mode'];
    $transactionRef = $_POST['transaction_reference'] ?? null;

    // Start transaction
    $pdo->beginTransaction();

    // Verify order exists and is pending
    $stmt = $pdo->prepare("SELECT * FROM pos_order WHERE order_id = ? AND order_status = 'Pending' FOR UPDATE");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("Order not found or already processed");
    }

    // Verify amount
    if ($amountReceived < $orderTotal && $paymentMode === 'Cash') {
        throw new Exception("Cash payment must cover full order amount");
    }

    // Record payment
    $stmt = $pdo->prepare("INSERT INTO payments 
                          (order_id, amount, payment_method, transaction_reference) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $orderId,
        $amountReceived,
        $paymentMode,
        $transactionRef
    ]);
    $paymentId = $pdo->lastInsertId();

    // Update order status
    $stmt = $pdo->prepare("UPDATE pos_order SET order_status = 'Paid' WHERE order_id = ?");
    $stmt->execute([$orderId]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'payment_id' => $paymentId,
        'message' => 'Payment processed successfully'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?><?php
require_once 'db_connect.php';
require_once 'auth_function.php';
checkAdminOrUserLogin();

header('Content-Type: application/json');

try {
    // Validate input
    $required = ['order_id', 'order_total', 'payment_mode', 'amount_received'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $orderId = (int)$_POST['order_id'];
    $orderTotal = (float)$_POST['order_total'];
    $amountReceived = (float)$_POST['amount_received'];
    $paymentMode = $_POST['payment_mode'];
    $transactionRef = $_POST['transaction_reference'] ?? null;

    // Start transaction
    $pdo->beginTransaction();

    // Verify order exists and is pending
    $stmt = $pdo->prepare("SELECT * FROM pos_order WHERE order_id = ? AND order_status = 'Pending' FOR UPDATE");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("Order not found or already processed");
    }

    // Verify amount
    if ($amountReceived < $orderTotal && $paymentMode === 'Cash') {
        throw new Exception("Cash payment must cover full order amount");
    }

    // Record payment
    $stmt = $pdo->prepare("INSERT INTO payments 
                          (order_id, amount, payment_method, transaction_reference) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $orderId,
        $amountReceived,
        $paymentMode,
        $transactionRef
    ]);
    $paymentId = $pdo->lastInsertId();

    // Update order status
    $stmt = $pdo->prepare("UPDATE pos_order SET order_status = 'Paid' WHERE order_id = ?");
    $stmt->execute([$orderId]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'payment_id' => $paymentId,
        'message' => 'Payment processed successfully'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>