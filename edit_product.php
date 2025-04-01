<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Fetch category for the dropdown
$categorys = $pdo->query("SELECT category_id, category_name FROM pos_category WHERE category_status = 'Active'")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$product = null;

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: product.php");
    exit;
}

$product_id = $_GET['id'];

// Fetch existing product details
$stmt = $pdo->prepare("SELECT * FROM pos_product WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: product.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];

    $category_id = $_POST['category_id'];
    $product_name = trim($_POST['product_name']);
    $product_price = trim($_POST['product_price']);
    $product_buying_price = trim($_POST['product_buying_price']);
    $product_quantity = trim($_POST['product_quantity']);
    
    // Automatically set status based on quantity
    $product_status = $product_quantity > 0 ? 'Available' : 'Out of Stock';
    
    $destPath = $product['product_image'];

    // Validate fields
    if (empty($category_id)) {
        $errors[] = 'Category is required.';
    }
    if (empty($product_name)) {
        $errors[] = 'Product Name is required.';
    }
    if (empty($product_price)) {
        $errors[] = 'Selling Price is required.';
    }
    if (empty($product_buying_price)) {
        $errors[] = 'Buying Price is required.';
    }
    if ($product_quantity === '') {
        $errors[] = 'Quantity is required.';
    }

    // Handle image upload (optional)
    if (!empty($_FILES['product_image']['name'])) {
        $product_image = $_FILES['product_image'];

        // Define the allowed file types
        $allowedTypes = ['image/jpeg', 'image/png'];

        // Get the uploaded file information
        $fileTmpPath = $product_image['tmp_name'];
        $fileName = $product_image['name'];
        $fileType = $product_image['type'];

        // Validate the file type
        if (in_array($fileType, $allowedTypes)) {
            // Define the upload directory
            $uploadDir = 'uploads/';

            // Generate a unique file name to avoid overwriting
            $uniqueFileName = uniqid('', true) . '-' . basename($fileName);

            // Define the destination path
            $destPath = $uploadDir . $uniqueFileName;

            // Move the uploaded file to the destination directory
            move_uploaded_file($fileTmpPath, $destPath);
        } else {
            $errors[] = "Invalid file type. Only JPG and PNG files are allowed.";
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE pos_product SET category_id = ?, product_name = ?, product_image = ?, product_price = ?, product_buying_price = ?, product_quantity = ?, product_status = ? WHERE product_id = ?");
        $stmt->execute([
            $category_id, 
            $product_name, 
            $destPath, 
            $product_price, 
            $product_buying_price, 
            $product_quantity, 
            $product_status,
            $product_id
        ]);
        header("Location: product.php");
        exit;
    } else {
        $message = '<ul class="list-unstyled">';
        foreach ($errors as $error) {
            $message .= '<li>' . $error . '</li>';
        }
        $message .= '</ul>';
    }
}

include('header.php');
?>

<h1 class="mt-4">Edit Product</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="product.php">Product Management</a></li>
    <li class="breadcrumb-item active">Edit Product</li>
</ol>
<?php
if($message !== ''){
    echo '<div class="alert alert-danger">'.$message.'</div>';
}
?>
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Edit Product</div>
                <div class="card-body">
                    <form method="POST" action="edit_product.php?id=<?php echo $product_id; ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="category_id">Category</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categorys as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" 
                                        <?php echo ($category['category_id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo $category['category_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="product_name">Product Name</label>
                            <input type="text" name="product_name" id="product_name" class="form-control" value="<?php echo htmlspecialchars($product['product_name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="product_buying_price">Buying Price</label>
                            <input type="number" name="product_buying_price" id="product_buying_price" class="form-control" step="0.01" value="<?php echo $product['product_buying_price']; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="product_price">Selling Price</label>
                            <input type="number" name="product_price" id="product_price" class="form-control" step="0.01" value="<?php echo $product['product_price']; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="product_quantity">Quantity</label>
                            <input type="number" name="product_quantity" id="product_quantity" class="form-control" min="0" value="<?php echo $product['product_quantity']; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="product_image">Image</label>
                            <input type="file" name="product_image" accept="image/*" />
                            <?php if (!empty($product['product_image'])): ?>
                                <div class="mt-2">
                                    <img src="<?php echo htmlspecialchars($product['product_image']); ?>" class="img-thumbnail" width="100">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-primary">Update Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include('footer.php');
?>