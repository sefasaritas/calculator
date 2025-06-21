<?php
require_once 'config.php';
requireAdmin();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_product') {
            // Add new product
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $default_price = floatval($_POST['default_price']);
            $default_cost = floatval($_POST['default_cost']);
            $category = trim($_POST['category']);
            
            if (empty($name)) {
                $error = 'Product name is required.';
            } else {
                // Handle image upload
                $image_path = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                    $image_path = uploadProductImage($_FILES['image']);
                    if (!$image_path) {
                        $error = 'Image upload failed. Please check file type and size.';
                    }
                }
                
                if (!$error) {
                    $stmt = $pdo->prepare("INSERT INTO products (name, description, default_price, default_cost, image_path, category) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$name, $description, $default_price, $default_cost, $image_path, $category])) {
                        $message = 'Product added successfully!';
                    } else {
                        $error = 'Failed to add product.';
                    }
                }
            }
        } elseif ($_POST['action'] == 'update_product') {
            // Update existing product
            $id = intval($_POST['product_id']);
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $default_price = floatval($_POST['default_price']);
            $default_cost = floatval($_POST['default_cost']);
            $category = trim($_POST['category']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($name)) {
                $error = 'Product name is required.';
            } else {
                // Handle image upload
                $image_path = $_POST['existing_image']; // Keep existing image by default
                if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                    $new_image_path = uploadProductImage($_FILES['image']);
                    if ($new_image_path) {
                        // Delete old image if exists
                        if ($image_path && file_exists($image_path)) {
                            unlink($image_path);
                        }
                        $image_path = $new_image_path;
                    } else {
                        $error = 'Image upload failed. Please check file type and size.';
                    }
                }
                
                if (!$error) {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, default_price = ?, default_cost = ?, image_path = ?, category = ?, is_active = ? WHERE id = ?");
                    if ($stmt->execute([$name, $description, $default_price, $default_cost, $image_path, $category, $is_active, $id])) {
                        $message = 'Product updated successfully!';
                    } else {
                        $error = 'Failed to update product.';
                    }
                }
            }
        } elseif ($_POST['action'] == 'delete_product') {
            // Delete product
            $id = intval($_POST['product_id']);
            
            // Get image path to delete file
            $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            if ($product) {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                if ($stmt->execute([$id])) {
                    // Delete image file if exists
                    if ($product['image_path'] && file_exists($product['image_path'])) {
                        unlink($product['image_path']);
                    }
                    $message = 'Product deleted successfully!';
                } else {
                    $error = 'Failed to delete product.';
                }
            }
        }
    }
}

// Get all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_product = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Product Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f9fafb;
            line-height: 1.6;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1f2937;
        }

        .navbar-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-link {
            color: #6b7280;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .nav-link.active {
            background: #3b82f6;
            color: white;
        }

        .btn-logout {
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .btn-logout:hover {
            background: #dc2626;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 968px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }

        .products-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #1f2937;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group input[type="file"] {
            padding: 8px 12px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .product-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 3rem;
        }

        .product-info {
            padding: 20px;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .product-description {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .product-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        .product-detail {
            color: #4b5563;
        }

        .product-category {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 12px;
        }

        .product-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 6px;
            margin-top: 8px;
        }

        .current-image {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .empty-state {
            text-align: center;
            color: #6b7280;
            padding: 60px 20px;
            font-style: italic;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">🛠️ Admin Panel</div>
            <div class="navbar-nav">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="admin.php" class="nav-link active">Products</a>
                <span style="color: #6b7280;">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="admin-grid">
            <!-- Add/Edit Product Form -->
            <div class="form-section">
                <h2 class="section-title">
                    <?php echo $edit_product ? '✏️ Edit Product' : '➕ Add New Product'; ?>
                </h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="action" value="update_product">
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_product['image_path']); ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add_product">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Enter product description..."><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="default_price">Default Price ($)</label>
                        <input type="number" id="default_price" name="default_price" step="0.01" value="<?php echo $edit_product['default_price'] ?? ''; ?>" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label for="default_cost">Default Cost ($)</label>
                        <input type="number" id="default_cost" name="default_cost" step="0.01" value="<?php echo $edit_product['default_cost'] ?? ''; ?>" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($edit_product['category'] ?? ''); ?>" placeholder="e.g., Electronics, Food, Clothing">
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <?php if ($edit_product && $edit_product['image_path']): ?>
                            <span class="current-image">Current image:</span>
                            <img src="<?php echo htmlspecialchars($edit_product['image_path']); ?>" alt="Current product image" class="image-preview">
                        <?php endif; ?>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small style="color: #6b7280; font-size: 0.8rem;">Max size: 5MB. Supported formats: JPG, PNG, GIF, WebP</small>
                    </div>

                    <?php if ($edit_product): ?>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_active" name="is_active" <?php echo $edit_product['is_active'] ? 'checked' : ''; ?>>
                                <label for="is_active">Active (visible to users)</label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_product ? '💾 Update Product' : '➕ Add Product'; ?>
                        </button>
                        <?php if ($edit_product): ?>
                            <a href="admin.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Products List -->
            <div class="products-section">
                <h2 class="section-title">📦 Products (<?php echo count($products); ?>)</h2>
                
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <p>No products added yet.<br>Add your first product to get started!</p>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <?php if ($product['image_path'] && file_exists($product['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <?php else: ?>
                                    <div class="product-image">📷</div>
                                <?php endif; ?>
                                
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    
                                    <?php if ($product['category']): ?>
                                        <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="product-status <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </div>
                                    
                                    <?php if ($product['description']): ?>
                                        <div class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?><?php echo strlen($product['description']) > 100 ? '...' : ''; ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="product-details">
                                        <div class="product-detail">
                                            <strong>Price:</strong> $<?php echo number_format($product['default_price'], 2); ?>
                                        </div>
                                        <div class="product-detail">
                                            <strong>Cost:</strong> $<?php echo number_format($product['default_cost'], 2); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <a href="admin.php?edit=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>