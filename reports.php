<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

// Ensure user is logged in
checkAdminOrUserLogin();

// Get configuration data
$confData = getConfigData($pdo);

// Set default report type and date range
$reportType = $_GET['type'] ?? 'sales';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Function to get sales summary data
function getSalesSummary($pdo, $startDate, $endDate) {
    $query = "
        SELECT 
            DATE(s.sale_date) AS sale_date,
            COUNT(s.sale_id) AS total_orders,
            SUM(s.total_amount) AS daily_total,
            GROUP_CONCAT(DISTINCT s.payment_mode) AS payment_modes,
            SUM(CASE WHEN s.sale_type = 'order' THEN 1 ELSE 0 END) AS order_count,
            SUM(CASE WHEN s.sale_type = 'bill' THEN 1 ELSE 0 END) AS bill_count
        FROM 
            sales s
        WHERE 
            s.sale_datetime BETWEEN ? AND ? 
        GROUP BY 
            DATE(s.sale_date)
        ORDER BY 
            sale_date DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get stock quantity data
function getStockReport($pdo) {
    $query = "
        SELECT 
            p.product_id,
            p.product_name,
            p.product_quantity as stock_quantity,
            p.product_price,
            p.product_buying_price,
            p.product_status,
            c.category_name
        FROM 
            pos_product p
        LEFT JOIN 
            pos_category c ON p.category_id = c.category_id
        ORDER BY 
            p.product_quantity ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get pending bills

function getPendingBills($pdo) {
    $query = "
        SELECT 
            b.bill_id,
            b.bill_number,
            b.bill_total,
            b.bill_created_at,
            u.user_name AS created_by,
            b.payment_mode,
            b.payment_date
        FROM 
            pos_bill b
        LEFT JOIN 
            pos_user u ON b.bill_created_by = u.user_id
        WHERE 
            b.bill_status = 'Pending'
        ORDER BY 
            b.bill_created_at DESC
    ";
 
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Include header
include('header.php');
?>

<h1 class="mt-4">Reports</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Reports</li>
</ol>

<!-- Report Selection Form -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-filter"></i> Report Filters
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="reportType" class="form-label">Report Type</label>
                <select class="form-select" id="reportType" name="type">
                    <option value="sales" <?php echo $reportType == 'sales' ? 'selected' : ''; ?>>Sales Summary</option>
                    <option value="stock" <?php echo $reportType == 'stock' ? 'selected' : ''; ?>>Stock Quantity</option>
                    <option value="bills" <?php echo $reportType == 'bills' ? 'selected' : ''; ?>>Pending Bills</option>
                </select>
            </div>
            
            <div class="col-md-3 date-range" id="dateRangeContainer" <?php echo $reportType == 'stock' || $reportType == 'bills' ? 'style="display:none;"' : ''; ?>>
                <label for="startDate" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo $startDate; ?>">
            </div>
            
            <div class="col-md-3 date-range" <?php echo $reportType == 'stock' || $reportType == 'bills' ? 'style="display:none;"' : ''; ?>>
                <label for="endDate" class="form-label">End Date</label>
                <input type="date" class="form-control" id="endDate" name="end_date" value="<?php echo $endDate; ?>">
            </div>
            
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <button type="button" id="printReport" class="btn btn-secondary">Print Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Report Results -->
<div class="card mb-4" id="reportContainer">
    <div class="card-header">
        <i class="fas fa-table"></i> 
        <?php 
        switch($reportType) {
            case 'sales':
                echo 'Sales Summary Report (' . date('M d, Y', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate)) . ')';
                break;
            case 'stock':
                echo 'Current Stock Quantity Report';
                break;
            case 'bills':
                echo 'Pending Bills Report';
                break;
        }
        ?>
    </div>
    <div class="card-body">
        <?php if ($reportType == 'sales'): ?>
            <?php $salesData = getSalesSummary($pdo, $startDate, $endDate); ?>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <h3 class="card-text">
                                <?php 
                                $totalSales = array_sum(array_column($salesData, 'daily_total'));
                                echo $confData['currency'] . number_format($totalSales, 2);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Orders</h5>
                            <h3 class="card-text">
                                <?php echo array_sum(array_column($salesData, 'total_orders')); ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Average Sale</h5>
                            <h3 class="card-text">
                                <?php echo array_sum(array_column($salesData, 'order_count')); ?>
                            </h3>

                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Bill Payments</h5>
                        <h3 class="card-text">
                            <?php echo array_sum(array_column($salesData, 'bill_count')); ?>
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Average Sale</h5>
                        <h3 class="card-text">
                            <?php 
                            $totalOrders = array_sum(array_column($salesData, 'total_orders'));
                            $avgSale = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
                            echo $confData['currency'] . number_format($avgSale, 2);
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="salesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Total Sales</th>
                            <th>Payment Methods</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salesData)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No sales data found for the selected period</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($salesData as $sale): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                    <td><?php echo $sale['total_orders']; ?></td>
                                    <td><?php echo $confData['currency'] . number_format($sale['daily_total'], 2); ?></td>
                                    <td><?php echo $sale['payment_modes']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($reportType == 'stock'): ?>
            <?php $stockData = getStockReport($pdo); ?>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Products</h5>
                            <h3 class="card-text"><?php echo count($stockData); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">In Stock</h5>
                            <h3 class="card-text">
                                <?php 
                                $inStock = array_filter($stockData, function($item) {
                                    return $item['product_status'] == 'Available';
                                });
                                echo count($inStock);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">Out of Stock</h5>
                            <h3 class="card-text">
                                <?php 
                                $outOfStock = array_filter($stockData, function($item) {
                                    return $item['product_status'] == 'Out of Stock';
                                });
                                echo count($outOfStock);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="stockTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Buying Price</th>
                            <th>Selling Price</th>
                            <th>Value</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stockData)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No product data found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stockData as $product): ?>
                                <tr>
                                    <td><?php echo $product['product_id']; ?></td>
                                    <td><?php echo $product['product_name']; ?></td>
                                    <td><?php echo $product['category_name']; ?></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td><?php echo $confData['currency'] . number_format($product['product_buying_price'], 2); ?></td>
                                    <td><?php echo $confData['currency'] . number_format($product['product_price'], 2); ?></td>
                                    <td><?php echo $confData['currency'] . number_format($product['stock_quantity'] * $product['product_buying_price'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['product_status'] == 'Available' ? 'success' : 'danger'; ?>">
                                            <?php echo $product['product_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            

        <?php elseif ($reportType == 'bills'): ?>
            <?php $billsData = getPendingBills($pdo); ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Pending Bills</h5>
                            <h3 class="card-text"><?php echo count($billsData); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Outstanding</h5>
                            <h3 class="card-text">
                                <?php 
                                $totalOutstanding = array_sum(array_column($billsData, 'bill_total'));
                                echo $confData['currency'] . number_format($totalOutstanding, 2);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="billsTable">
                    <thead>
                        <tr>
                            <th>Bill #</th>
                            <th>Date Created</th>
                            <th>Created By</th>
                            <th>Amount</th>
                            <th>Payment Mode</th>
                            <th>Payment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($billsData)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No pending bills found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($billsData as $bill): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($bill['bill_created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($bill['created_by'] ?? 'N/A'); ?></td>
                                    <td><?php echo $confData['currency'] . number_format($bill['bill_total'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($bill['payment_mode'] ?? 'Not specified'); ?></td>
                                    <td><?php echo $bill['payment_date'] ? date('M d, Y H:i', strtotime($bill['payment_date'])) : 'Unpaid'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                            onclick="window.location.href='pay.php?bill_id=<?php echo $bill['bill_id']; ?>&total=<?php echo $bill['bill_total']; ?>'">
                                            Process Payment
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
$(document).ready(function() {
    // Initialize DataTables for better table functionality
    $('#salesTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10
    });
    
    $('#stockTable').DataTable({
        "order": [[3, "asc"]],
        "pageLength": 10
    });
    
    $('#billsTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 10
    });
    
    // Handle report type change to show/hide date range inputs
    $('#reportType').change(function() {
        if ($(this).val() === 'sales') {
            $('.date-range').show();
        } else {
            $('.date-range').hide();
        }
    });
    

    $('#printReport').click(function() {
        const printWindow = window.open('', '_blank', 'width=1000,height=600');
        const reportContent = document.getElementById('reportContainer').cloneNode(true);
        const reportTitle = document.querySelector('.card-header').textContent.trim();
        
        // Remove DataTables classes and elements
        $(reportContent).find('.dataTables_info, .dataTables_length, .dataTables_filter, .dataTables_paginate').remove();
        $(reportContent).find('table').removeClass('table-striped table-bordered').css('width', '100%');

        const printHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${reportTitle}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .print-header { text-align: center; margin-bottom: 30px; }
                    .print-header h2 { margin-bottom: 5px; }
                    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; font-weight: bold; }
                    .summary-card { margin-bottom: 20px; padding: 15px; border: 1px solid #eee; }
                    @media print {
                        @page { margin: 1cm; }
                        button { display: none; }
                        table { page-break-inside: avoid; }
                    }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h2><?= $confData['restaurant_name'] ?? 'Restaurant Name' ?></h2>
                    <p><?= $confData['restaurant_address'] ?? '' ?></p>
                    <p>Tel: <?= $confData['restaurant_phone'] ?? '' ?></p>
                    <h3>${reportTitle}</h3>
                    <p>Printed: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</p>
                </div>
                ${reportContent.innerHTML}
            </body>
            </html>
        `;

        printWindow.document.open();
        printWindow.document.write(printHTML);
        printWindow.document.close();
        
        // Trigger print after content loads
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    });
});
</script>