<?php
require_once 'db_connect.php';
require_once 'auth_function.php';
checkAdminOrUserLogin();

// Validate parameters
$billId = filter_input(INPUT_GET, 'bill_id', FILTER_VALIDATE_INT);
$billTotal = filter_input(INPUT_GET, 'bill_total', FILTER_VALIDATE_FLOAT);

if (!$billId || $billId <= 0) {
    die(json_encode(['success' => false, 'error' => 'Invalid Bill ID']));
}

if (!$billTotal || $billTotal <= 0) {
    die(json_encode(['success' => false, 'error' => 'Invalid Bill Total']));
}

// Verify bill exists and is pending
try {
    $stmt = $pdo->prepare("SELECT * FROM pos_bill WHERE bill_id = ? AND bill_status = 'Pending'");
    $stmt->execute([$billId]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bill) {
        throw new Exception("Bill not found or already paid");
    }

    // Fetch bill items
    $stmt = $pdo->prepare("SELECT * FROM pos_bill_items WHERE bill_id = ?");
    $stmt->execute([$billId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die(json_encode(['success' => false, 'error' => $e->getMessage()]));
}

// Get configuration data
$confData = getConfigData($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Bill Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .payment-container {
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .payment-header {
            background-color: #343a40;
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: white;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .payment-body {
            padding: 2rem;
        }
        .payment-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .summary-total {
            font-weight: bold;
            border-top: 1px solid #dee2e6;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }
        .btn-pay {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 8px;
        }
        .payment-method-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h2 class="mb-0">Complete Bill Payment</h2>
            <button class="close-btn" onclick="window.location.href='unpaid_bills.php'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="payment-body">
            <div class="payment-summary">
                <h5 class="mb-3">Bill Summary</h5>
                <div class="mb-3">
                    <strong>Bill #:</strong> <?= htmlspecialchars($bill['bill_number']) ?>
                </div>
                
                <div class="table-responsive mb-3">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= $item['product_qty'] ?></td>
                                    <td><?= $confData['currency'] . number_format($item['product_price'], 2) ?></td>
                                    <td><?= $confData['currency'] . number_format($item['product_price'] * $item['product_qty'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Total</th>
                                <th><?= $confData['currency'] . number_format($billTotal, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <form id="paymentForm">
                <input type="hidden" name="bill_id" value="<?= $billId ?>">
                <input type="hidden" name="bill_total" value="<?= $billTotal ?>">
                
                <div class="form-group">
                    <label class="form-label">Payment Method *</label>
                    <select class="form-select" name="payment_mode" required>
                        <option value="">-- Select Payment Method --</option>
                        <option value="Cash">
                            <i class="fas fa-money-bill-wave payment-method-icon"></i> Cash
                        </option>
                        <option value="Credit Card">
                            <i class="fas fa-credit-card payment-method-icon"></i> Credit Card
                        </option>
                        <option value="MPesa">
                            <i class="fas fa-mobile-alt payment-method-icon"></i> MPesa
                        </option>
                        <option value="Bank Transfer">
                            <i class="fas fa-university payment-method-icon"></i> Bank Transfer
                        </option>
                    </select>
                    <div class="invalid-feedback" id="paymentModeError"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Amount Received *</label>
                    <input type="number" class="form-control" name="amount_received" 
                           value="<?= number_format($billTotal, 2, '.', '') ?>" 
                           min="<?= number_format($billTotal, 2, '.', '') ?>" 
                           step="0.01" required>
                    <div class="invalid-feedback" id="amountReceivedError"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Transaction Reference (if applicable)</label>
                    <input type="text" class="form-control" name="transaction_reference">
                </div>
                
                <button type="submit" class="btn btn-primary btn-pay">
                    <i class="fas fa-check-circle"></i> Complete Payment
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('paymentForm');
            const paymentModeSelect = form.querySelector('[name="payment_mode"]');
            const amountReceivedInput = form.querySelector('[name="amount_received"]');
            
            // Auto-adjust amount received for non-cash payments
            paymentModeSelect.addEventListener('change', function() {
                if (this.value === 'Cash') {
                    amountReceivedInput.removeAttribute('readonly');
                    amountReceivedInput.value = '';
                } else {
                    amountReceivedInput.setAttribute('readonly', true);
                    amountReceivedInput.value = <?= number_format($billTotal, 2, '.', '') ?>;
                }
            });
            
            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Reset validation
                form.classList.remove('was-validated');
                document.getElementById('paymentModeError').textContent = '';
                document.getElementById('amountReceivedError').textContent = '';
                
                // Validate form
                let isValid = true;
                
                if (!paymentModeSelect.value) {
                    document.getElementById('paymentModeError').textContent = 'Please select a payment method';
                    isValid = false;
                }
                
                const amountReceived = parseFloat(amountReceivedInput.value);
                if (!amountReceived || amountReceived <= 0) {
                    document.getElementById('amountReceivedError').textContent = 'Please enter a valid amount';
                    isValid = false;
                } else if (paymentModeSelect.value === 'Cash' && amountReceived < <?= number_format($billTotal, 2, '.', '') ?>) {
                    document.getElementById('amountReceivedError').textContent = 'Amount received must be at least the bill total';
                    isValid = false;
                }
                
                if (!isValid) {
                    form.classList.add('was-validated');
                    return;
                }
                
                // Prepare form data
                const formData = new FormData(form);
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
                
                // Submit payment
                fetch('process_bill_payment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Redirect to success page
                        window.location.href = `payment_success.php?bill_id=${data.bill_id}`;
                    } else {
                        throw new Error(data.message || 'Payment failed');
                    }
                })
                .catch(error => {
                    alert('Payment Error: ' + error.message);
                    console.error('Error:', error);
                })
                .finally(() => {
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                });
            });
        });
    </script>
</body>
</html>