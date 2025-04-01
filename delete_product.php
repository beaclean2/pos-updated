<?php
require_once 'db_connect.php';
require_once 'auth_function.php';
checkAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];

    try {
        // Check if product exists
        $checkStmt = $pdo->prepare("SELECT product_id FROM pos_product WHERE product_id = ?");
        $checkStmt->execute([$productId]);
        if ($checkStmt->rowCount() === 0) {
            die('Product not found');
        }

        // Delete product
        $deleteStmt = $pdo->prepare("DELETE FROM pos_product WHERE product_id = ?");
        $deleteStmt->execute([$productId]);

        echo 'success';
    } catch (PDOException $e) {
        error_log("Delete Product Error: " . $e->getMessage());
        // Handle foreign key constraint errors (e.g., product in orders)
        if ($e->errorInfo[1] === 1451) { // MySQL error code for foreign key violation
            die('Cannot delete product: It is referenced in existing orders.');
        } else {
            die('error');
        }
    }
} else {
    die('Invalid request');
}
?>