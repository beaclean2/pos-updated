<?php
require_once 'db_connect.php';
require_once 'auth_function.php';
checkAdminOrUserLogin();

// Fetch categories and configuration
$categories = $pdo->query("SELECT category_id, category_name FROM pos_category WHERE category_status = 'Active'")->fetchAll(PDO::FETCH_ASSOC);
$confData = getConfigData($pdo);

include('header.php');
?>

<h1 class="mt-4">Order Management</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">New Order</li>
</ol>

<div class="row">
    <!-- Products Section -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <b>Products</b>
                <div class="float-end">
                    <input type="text" id="productSearch" placeholder="Search products..." class="form-control form-control-sm">
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <button type="button" class="btn btn-primary mb-2" onclick="loadCategoryProducts()">All Products</button>
                    <?php foreach ($categories as $category): ?>
                        <button type="button" class="btn btn-primary mb-2" onclick="loadCategoryProducts('<?= $category['category_id'] ?>')">
                            <?= htmlspecialchars($category['category_name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="row" id="productList"></div>
            </div>
        </div>
    </div>

    <!-- Order Summary Section -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <b>Order Summary</b>
                <button class="btn btn-sm btn-success float-end" onclick="resetOrder()">New Order</button>
                <button class="btn btn-sm btn-info float-end me-2" onclick="checkUnpaidBills()">Unpaid Bills</button>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="cartItems"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3"><b>Subtotal</b></td>
                            <td id="subtotal">0.00</td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>Total</b></td>
                            <td id="total">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <button class="btn btn-warning" onclick="createBill()">
                        <i class="fas fa-file-invoice"></i> Create Bill
                    </button>
                    <button class="btn btn-success" onclick="processOrder()" id="payButton">
                        <i class="fas fa-credit-card"></i> Process Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Unpaid Bills Modal (will be populated dynamically) -->
<div id="unpaidBillsModal"></div>

<?php include('footer.php'); ?>

<script>
const currency = "<?= htmlspecialchars($confData['currency']) ?>";
let cart = [];

// Load products
function loadCategoryProducts(categoryId = 0) {
    fetch('order_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_id: categoryId })
    })
    .then(response => response.json())
    .then(products => {
        const productList = document.getElementById('productList');
        productList.innerHTML = products.map(product => `
            <div class="col-md-3 mb-3">
                <div class="card product-card ${product.product_status === 'Available' ? 'available' : 'unavailable'}" 
                     onclick="${product.product_status === 'Available' ? `addToCart('${escapeHtml(product.product_name)}', ${product.product_price})` : ''}">
                    <img src="${product.product_image ? escapeHtml(product.product_image) : '/img/default-product.png'}" 
                         class="card-img-top" 
                         alt="${escapeHtml(product.product_name)}">
                    <div class="card-body">
                        <h6 class="card-title">${escapeHtml(product.product_name)}</h6>
                        <p class="card-text">
                            ${currency}${product.product_price.toFixed(2)}
                            <span class="badge bg-${product.product_status === 'Available' ? 'success' : 'danger'} float-end">
                                ${product.product_status}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        `).join('');
    })
    .catch(error => {
        console.error('Error loading products:', error);
        showAlert('danger', 'Failed to load products');
    });
}

// Add to cart function
function addToCart(name, price) {
    const existingItem = cart.find(item => item.name === name);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({ name, price, quantity: 1 });
    }
    
    updateCart();
}

// Update cart display
function updateCart() {
    const cartItems = document.getElementById('cartItems');
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    cartItems.innerHTML = cart.map(item => `
        <tr>
            <td>${escapeHtml(item.name)}</td>
            <td>
                <div class="input-group input-group-sm">
                    <button class="btn btn-outline-secondary" onclick="updateQuantity('${escapeHtml(item.name)}', -1)">-</button>
                    <input type="text" class="form-control text-center" value="${item.quantity}" 
                           onchange="updateQuantity('${escapeHtml(item.name)}', 0, this.value)">
                    <button class="btn btn-outline-secondary" onclick="updateQuantity('${escapeHtml(item.name)}', 1)">+</button>
                </div>
            </td>
            <td>${currency}${item.price.toFixed(2)}</td>
            <td>${currency}${(item.price * item.quantity).toFixed(2)}</td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="removeFromCart('${escapeHtml(item.name)}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    document.getElementById('subtotal').textContent = `${currency}${subtotal.toFixed(2)}`;
    document.getElementById('total').textContent = `${currency}${subtotal.toFixed(2)}`;
    
    // Enable/disable payment button
    document.getElementById('payButton').disabled = cart.length === 0;
}

// Update item quantity
function updateQuantity(name, change, newValue = null) {
    const item = cart.find(i => i.name === name);
    if (!item) return;
    
    if (newValue !== null) {
        item.quantity = parseInt(newValue) || 1;
    } else {
        item.quantity += change;
    }
    
    if (item.quantity < 1) item.quantity = 1;
    
    updateCart();
}

// Remove item from cart
function removeFromCart(name) {
    cart = cart.filter(item => item.name !== name);
    updateCart();
}

// Reset order
function resetOrder() {
    if (cart.length > 0 && !confirm('Are you sure you want to clear the current order?')) {
        return;
    }
    cart = [];
    updateCart();
}

// Process order
function processOrder() {
    if (cart.length === 0) {
        showAlert('warning', 'Your cart is empty');
        return;
    }

    const orderNumber = 'ORD-' + Date.now();
    const orderTotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const userId = <?= $_SESSION['user_id'] ?? 0 ?>;

    const orderData = {
        order_number: orderNumber,
        order_total: orderTotal,
        order_created_by: userId,
        items: cart.map(item => ({
            product_name: item.name,
            product_qty: item.quantity,
            product_price: item.price
        }))
    };

    fetch('order_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderData)
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.href = `pay.php?order_id=${data.order_id}&order_total=${orderTotal}`;
        } else {
            throw new Error(data.message || 'Failed to create order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error processing order: ' + error.message);
    });
}

// Create bill
function createBill() {
    if (cart.length === 0) {
        showAlert('warning', 'Your cart is empty');
        return;
    }

    const billNumber = 'BILL-' + Date.now();
    const billTotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const userId = <?= $_SESSION['user_id'] ?? 0 ?>;

    const billData = {
        bill_number: billNumber,
        bill_total: billTotal,
        bill_created_by: userId,
        items: cart.map(item => ({
            product_name: item.name,
            product_qty: item.quantity,
            product_price: item.price
        }))
    };

    fetch('bill_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(billData)
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('success', `Bill ${billNumber} created successfully`);
            resetOrder();
        } else {
            throw new Error(data.message || 'Failed to create bill');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error creating bill: ' + error.message);
    });
}

// Check unpaid bills
function checkUnpaidBills() {
    fetch('get_unpaid_bills.php')
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showUnpaidBillsModal(data.unpaidBills);
        } else {
            throw new Error(data.message || 'Failed to load unpaid bills');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error loading unpaid bills: ' + error.message);
    });
}

// Show unpaid bills modal
function showUnpaidBillsModal(bills) {
    const modalContainer = document.getElementById('unpaidBillsModal');
    
    if (bills.length === 0) {
        modalContainer.innerHTML = `
            <div class="modal fade" id="unpaidBillsModalContent" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Unpaid Bills</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>No unpaid bills found.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else {
        modalContainer.innerHTML = `
            <div class="modal fade" id="unpaidBillsModalContent" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Unpaid Bills</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Bill Number</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${bills.map(bill => `
                                            <tr>
                                                <td>${bill.bill_number}</td>
                                                <td>${new Date(bill.bill_created_at).toLocaleString()}</td>
                                                <td>${currency}${parseFloat(bill.bill_total).toFixed(2)}</td>
                                                <td><span class="badge bg-${bill.bill_status === 'Pending' ? 'warning' : 'success'}">${bill.bill_status}</span></td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm" onclick="payUnpaidBill(${bill.bill_id}, ${bill.bill_total})">
                                                        <i class="fas fa-money-bill-wave"></i> Pay
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('unpaidBillsModalContent'));
    modal.show();
}

// Pay unpaid bill
function payUnpaidBill(billId, billTotal) {
    window.location.href = `pay_bill.php?bill_id=${billId}&bill_total=${billTotal}`;
}

// Utility function to escape HTML
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Show alert message
function showAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.prepend(alert);
    
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 5000);
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadCategoryProducts();
    
    // Product search functionality
    document.getElementById('productSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const productName = card.querySelector('.card-title').textContent.toLowerCase();
            card.style.display = productName.includes(searchTerm) ? 'block' : 'none';
        });
    });
});
</script>