<?php  
require_once 'db_connect.php'; 
require_once 'auth_function.php';  
checkAdminLogin();  
$confData = getConfigData($pdo);  
include('header.php'); 
?>  
<h1 class="mt-4">Product Management</h1> 
<ol class="breadcrumb mb-4">     
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>     
    <li class="breadcrumb-item active">Product Management</li> 
</ol>  
<div class="card">     
    <div class="card-header">         
        <div class="row">             
            <div class="col col-md-6"><b>Product List</b></div>             
            <div class="col col-md-6">                 
                <a href="add_product.php" class="btn btn-success btn-sm float-end">Add</a>             
            </div>         
        </div>     
    </div>     
    <div class="card-body">         
        <table id="productTable" class="table table-bordered">             
            <thead>                 
                <tr>                     
                    <th>ID</th>                     
                    <th>Category</th>                     
                    <th>Product Name</th>                     
                    <th>Buying Price</th>
                    <th>Selling Price</th>
                    <th>Quantity</th>
                    <th>Status</th>                     
                    <th>Image</th>                     
                    <th>Action</th>                 
                </tr>             
            </thead>         
        </table>     
    </div> 
</div>  
<?php include('footer.php'); ?>  

<!-- Modal for Delete Confirmation -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProductModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this product?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script> 
$(document).ready(function() {     
    let productToDelete = null;

    $('#productTable').DataTable({         
        "processing": true,         
        "serverSide": true,         
        "ajax": {             
            "url": "product_ajax.php",             
            "type": "GET"         
        },         
        "columns": [             
            { "data": "product_id" },             
            { "data": "category_name" },             
            { "data": "product_name" },             
            {                 
                "data" : null,                 
                "render" : function(data, type, row){                     
                    return `<?php echo $confData['currency']; ?>${row.product_buying_price}`;                 
                }             
            },
            {                 
                "data" : null,                 
                "render" : function(data, type, row){                     
                    return `<?php echo $confData['currency']; ?>${row.product_price}`;                 
                }             
            },
            { 
                "data": "product_quantity" 
            },
            {                  
                "data" : null,                 
                "render" : function(data, type, row){                     
                    if(row.product_status === 'Available'){                        
                        return `<span class="badge bg-success">Available</span>`;                     
                    }                     
                    if(row.product_status === 'Out of Stock'){                        
                        return `<span class="badge bg-danger">Out of Stock</span>`;                     
                    }                 
                }              
            },             
            {                 
                "data" : null,                 
                "render" : function(data, type, row) {
                    const imageUrl = row.product_image && row.product_image.trim() ? row.product_image : '/img/default-product.png';
                    console.log('Loading image:', imageUrl); // Debugging: Log the image URL
                    return `<img src="${imageUrl}" class="rounded-circle" width="40" onerror="this.src='/img/default-product.png'; console.error('Failed to load image:', this.src);" />`;
                }             
            },             
            {                 
                "data" : null,                 
                "render" : function(data, type, row){                     
                    return `                     
                    <div class="text-center">                         
                        <a href="edit_product.php?id=${row.product_id}" class="btn btn-warning btn-sm me-1">Edit</a>
                        <button class="btn btn-danger btn-sm delete-product" data-id="${row.product_id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;                 
                }             
            }         

                ]     
});      

    // Delete Product Handler
    $(document).on('click', '.delete-product', function() {
        productToDelete = $(this).data('id');
        $('#deleteProductModal').modal('show');
    });

    // Confirm Delete Button
    $('#confirmDeleteBtn').on('click', function() {
        if (productToDelete) {
            $.ajax({
                url: 'delete_product.php',
                method: 'POST',
                data: { product_id: productToDelete },
                success: function(response) {
                    if (response === 'success') {
                        $('#productTable').DataTable().ajax.reload();
                        $('#deleteProductModal').modal('hide');
                        // Optional: Show a success toast/alert
                        alert('Product deleted successfully');
                    } else {
                        // Optional: Show an error toast/alert
                        alert('Failed to delete product');
                    }
                },
                error: function() {
                    alert('Error occurred while deleting product');
                }
            });
        }
    });
}); 
</script>