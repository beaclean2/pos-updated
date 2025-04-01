
<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminOrUserLogin();
$categorySql = "SELECT COUNT(*) FROM pos_category WHERE category_status = 'Active'";
$productSql = "SELECT COUNT(*) FROM pos_product";
$userSql = "SELECT COUNT(*) FROM pos_user";
if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'User'){
    $orderSql = "SELECT SUM(order_total) FROM pos_order WHERE order_created_by = '".$_SESSION['user_id']."'";
} else {
    $orderSql = "SELECT SUM(order_total) FROM pos_order";
}

$stmt = $pdo->prepare($categorySql);
$stmt->execute();
$total_category = $stmt->fetchColumn();

// Count total in-process tasks
$stmt = $pdo->prepare($productSql);
$stmt->execute();
$total_product = $stmt->fetchColumn();

// Count total completed tasks
$stmt = $pdo->prepare($userSql);
$stmt->execute();
$total_user = $stmt->fetchColumn();

// Count total delayed tasks
$stmt = $pdo->prepare($orderSql);
$stmt->execute();
$total_sales = $stmt->fetchColumn();

$confData = getConfigData($pdo);

include('header.php');
?>
<style>
    .circle {
        display: inline-block;
        width: 100px;
        height: 100px;
        line-height: 100px;
        border-radius: 50%;
        background-color: white;
        color: black;
        text-align: center;
        font-size: 36px;
        font-weight: bold;
        margin-bottom:16px;
    }
</style>
<h1 class="mt-4">Dashboard</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item active">Dashboard</li>
</ol>
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body text-center">
                <div class="circle"><?php echo $total_category; ?></div><br />
                <b>Total Category</b>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body text-center">
                <div class="circle"><?php echo $total_product; ?></div><br />
                <b>Total Product</b>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
            <div class="card-body text-center">
                <div class="circle"><?php echo $total_user; ?></div><br />
                <b>Total User</b>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-danger text-white mb-4">
            <div class="card-body text-center">
                <div class="circle"><?php echo $confData['currency'] . floatval($total_sales); ?></div><br />
                <b>Total Sales</b>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><b>Last 7 Days Order Data</b></div>
            <div class="card-body" style="max-height:400px;">
                <canvas id="orderBarChart" style="height:400px; width:100%"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><b>Order Data Category Wise</b></div>
            <div class="card-body" style="max-height:400px;" align="center">
                <canvas id="categoryChart" style="width:100%"></canvas>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
include('footer.php');
?>

<script>
        let cur = "<?php echo $confData['currency']; ?>";
        // Fetch data from PHP script
        fetch('get_order_data.php')
            .then(response => response.json())
            .then(data => {
                // Prepare Chart.js data
                const ctx = document.getElementById('orderBarChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.dates,
                        datasets: [{
                            label: `Order Value (${cur})`,
                            data: data.totals,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Order Value'
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching data:', error));
            
        fetch('get_category_data.php')
            .then(response => response.json())
            .then(data => {
                // Prepare Chart.js data
                const ctx = document.getElementById('categoryChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data.categories,
                        datasets: [{
                            label: 'Order Value by Category',
                            data: data.totals,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 206, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(153, 102, 255, 0.2)',
                                'rgba(255, 159, 64, 0.2)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return tooltipItem.label + ': ' + cur + '' + tooltipItem.raw.toFixed(2);
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching data:', error));
    
    </script>

<script>
$(document).ready(function() {
    /*$('#taskTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "task_ajax.php",
            "type": "GET"
        },
        "columns": [
            { "data": "task_id" },
            { "data": "department_name" },
            {
                "data" : null,
                "render" : function(data, type, row){
                    return `<img src="${row.user_image}" class="rounded-circle" width="40" /> ${row.user_first_name} ${row.user_last_name}`;
                }
            },            
            { "data": "task_title" },
            { "data": "task_assign_date" },
            { "data": "task_end_date" },
            { 
                "data" : null,
                "render" : function(data, type, row){
                    if(row.task_status === 'Pending'){
                        return `<span class="badge bg-primary">Pending</span>`;
                    }
                    if(row.task_status === 'Viewed'){
                        return `<span class="badge bg-info">Viewed</span>`;
                    }
                    if(row.task_status === 'In Progress'){
                        return `<span class="badge bg-warning">In Progress</span>`;
                    }
                    if(row.task_status === 'Completed'){
                        return `<span class="badge bg-success">Completed</span>`;
                    }
                    if(row.task_status === 'Delayed'){
                        return `<span class="badge bg-danger">Delayed</span>`;
                    }
                } 
            },
            {
                "data" : null,
                "render" : function(data, type, row){
                    let btn = `<a href="view_task.php?id=${row.task_id}" class="btn btn-primary btn-sm">View</a>&nbsp;`;
                    <?php
                    if(isset($_SESSION["admin_logged_in"])){
                    ?>
                    if(row.task_status === 'Pending' || row.task_status === 'Viewed'){
                        btn += `<a href="edit_task.php?id=${row.task_id}" class="btn btn-warning btn-sm">Edit</a>&nbsp;`;
                        btn += `<button type="button" class="btn btn-danger btn-sm btn-delete" data-id="${row.task_id}">Delete</button>`;
                    }
                    <?php
                    }
                    ?>
                    return `
                    <div class="text-center">
                        ${btn}
                    </div>
                    `;
                }
            }
        ]
    });*/

    $(document).on('click', '.btn-delete', function() {
        if(confirm("Are you sure you want to remove this task?")){
            let id = $(this).data('id');
            window.location.href = 'task.php?id=' + id + '&action=delete';
        }
    });
});
</script>