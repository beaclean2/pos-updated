<?php
require_once 'db_connect.php';
require_once 'auth_function.php';
checkAdminOrUserLogin();

header('Content-Type: application/json');

try {
    // Validate input
    $required = ['bill_id', 'bill_total', 'payment_mode', 'amount_received'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $billId = (int)$_POST['bill_id'];
    $billTotal = (float)$_POST['bill_total'];
    $amountReceived = (float)$_POST['amount_received'];
    $paymentMode = $_POST['payment_mode'];
    $transactionRef = $_POST['transaction_reference'] ?? null;

    // Start transaction
    $pdo->beginTransaction();

    // Verify bill exists and is pending
    $stmt = $pdo->prepare("SELECT * FROM pos_bill WHERE bill_id = ? AND bill_status = 'Pending' FOR UPDATE");
    $stmt->execute([$billId]);
    $bill = $stmt->fetch();

    if (!$bill) {
        throw new Exception("Bill not found or already paid");
    }

    // Verify amount
    if ($amountReceived < $billTotal && $paymentMode === 'Cash') {
        throw new Exception("Cash payment must cover full bill amount");
    }

    // Record payment
    $stmt = $pdo->prepare("INSERT INTO payments 
                          (bill_id, amount, payment_method, transaction_reference) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $billId,
        $amountReceived,
        $paymentMode,
        $transactionRef
    ]);
    $paymentId = $pdo->lastInsertId();

    // Update bill status
    $stmt = $pdo->prepare("UPDATE pos_bill 
                          SET bill_status = 'Paid', 
                              payment_mode = ?,
                              payment_completed = 1 
                          WHERE bill_id = ?");
    $stmt->execute([$paymentMode, $billId]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'bill_id' => $billId,
        'payment_id' => $paymentId,
        'message' => 'Bill payment processed successfully'
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