<?php
// Make sure to connect to your database
include('db_connection.php');  // Adjust this path to your actual db connection file

// Get order_id from the URL
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;

if (!$order_id) {
    echo "No order ID provided.";
    exit;
}

// Fetch order details from the database using the order_id
$query = "SELECT * FROM orders WHERE order_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Check if the order was found
if (!$order) {
    echo "Order not found.";
    exit;
}

// Get additional order details (e.g., items, amounts, customer info)
$order_items_query = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = $pdo->prepare($order_items_query);
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Start building the receipt HTML
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .receipt-container {
            width: 300px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #000;
            font-size: 14px;
        }
        .receipt-container h1 {
            text-align: center;
            font-size: 18px;
            margin: 0;
        }
        .receipt-container p {
            margin: 5px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .items-table th, .items-table td {
            text-align: left;
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }
        .total {
            margin-top: 10px;
            font-weight: bold;
        }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                width: 100%;
                padding: 20px;
                border: none;
            }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <h1>Receipt</h1>
    <p>Order ID: <?php echo $order['order_id']; ?></p>
    <p>Date: <?php echo date('Y-m-d H:i:s', strtotime($order['order_date'])); ?></p>

    <p><strong>Customer:</strong> <?php echo $order['customer_name']; ?></p>
    <p><strong>Payment Method:</strong> <?php echo $order['payment_method']; ?></p>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_amount = 0;
            foreach ($order_items as $item) {
                $item_total = $item['price'] * $item['quantity'];
                $total_amount += $item_total;
                ?>
                <tr>
                    <td><?php echo $item['item_name']; ?></td>
                    <td><?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item_total, 2); ?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>

    <div class="total">
        <p><strong>Total: </strong><?php echo number_format($total_amount, 2); ?></p>
    </div>
</div>

<script>
    window.onload = function() {
        window.print(); // Automatically open print dialog on page load
        window.onafterprint = function() {
            window.close(); // Close the window after printing
        };
    };
</script>

</body>
</html>
