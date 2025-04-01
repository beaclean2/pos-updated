<?php
require_once 'db_connect.php';
require_once 'auth_function.php';
checkAdminOrUserLogin();

header('Content-Type: application/json');

try {
    // Fetch unpaid bills
    $stmt = $pdo->prepare("SELECT * FROM pos_bill WHERE bill_status = 'Pending' ORDER BY bill_created_at DESC");
    $stmt->execute();
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each bill, fetch its items
    foreach ($bills as &$bill) {
        $stmt = $pdo->prepare("SELECT * FROM pos_bill_items WHERE bill_id = ?");
        $stmt->execute([$bill['bill_id']]);
        $bill['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'unpaidBills' => $bills
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>