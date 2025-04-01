<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminOrUserLogin();
include('header.php');

// Query to fetch only unpaid bills
$billsStmt = $pdo->prepare("
    SELECT 
        bill_id, 
        bill_number, 
        bill_total as bill_amount, 
        bill_created_at as bill_datetime
    FROM pos_bill 
    WHERE bill_status = 'Pending'
    ORDER BY bill_created_at DESC
");
$billsStmt->execute();
$unpaidBills = $billsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="mt-4">Unpaid Bills</h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table mr-1"></i>
            Unpaid Bills List
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Bill Number</th>
                            <th>Bill Amount</th>
                            <th>Bill Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($unpaidBills) > 0): ?>
                            <?php foreach ($unpaidBills as $bill): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['bill_id']); ?></td>
                                <td><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                                <td><?php echo number_format($bill['bill_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($bill['bill_datetime']); ?></td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="payBill(<?php echo $bill['bill_id']; ?>, <?php echo $bill['bill_amount']; ?>)">Pay</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No unpaid bills found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// In the JavaScript section
function payBill(billId, billAmount) {
    window.location.href = `pay_bill.php?bill_id=${billId}&bill_amount=${billAmount}`;
}

// Check if we need to refresh the page (after a successful payment)
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('refresh')) {
        // Remove the refresh parameter from URL to prevent infinite refresh
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
});
</script>

<?php include('footer.php'); ?>