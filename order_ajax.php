<?php
require_once 'db_connect.php';
require_once 'auth_function.php';
checkAdminOrUserLogin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $categoryId = $input['category_id'] ?? 0;

    // Fetch products with category filter
    $sql = "SELECT 
                p.product_id,
                p.product_name,
                p.product_price,
                p.product_image,
                p.product_status,
                c.category_name
            FROM pos_product p
            INNER JOIN pos_category c ON p.category_id = c.category_id
            WHERE p.product_status = 'Available'
            AND c.category_status = 'Active'";

    if ($categoryId > 0) {
        $sql .= " AND p.category_id = :category_id";
    }

    $stmt = $pdo->prepare($sql);
    if ($categoryId > 0) {
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
    }
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add image paths and format data
    foreach ($products as &$product) {
        $product['product_image'] = $product['product_image'] ? 'uploads/products/' . $product['product_image'] : '';
        $product['product_price'] = (float)$product['product_price'];
    }

    echo json_encode($products);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}