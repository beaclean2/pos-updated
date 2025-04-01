<?php
if (!isset($_GET["order_id"])) {
    die("Invalid request");
}

$orderId = intval($_GET["order_id"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
</head>
<body>
    <h2>Payment Completed Successfully!</h2>
    <p>Order ID: <?php echo $orderId; ?></p>
    <a href="add_order.php">Back to Orders</a>
    <a href="print_receipt.php?order_id=<?php echo $orderId; ?>" target="_blank">Print Receipt</a>
</body>
</html>
