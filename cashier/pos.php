<?php
include "../config/db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];

// Fetch gym settings
$settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
if (!$settings) {
    // Insert default settings if not exists
    $conn->query("INSERT INTO gym_settings (gym_name, logo_path, background_path) VALUES ('Gym Management System', 'gym logo.jpg', 'gym background.jpg')");
    $settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
}

// Handle add product
if (isset($_POST['add_product'])) {
    header('Content-Type: application/json');

    $name           = trim($_POST['name']);
    $category       = strtolower(trim($_POST['category']));
    $price          = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $errors         = [];

    // Handle optional product image upload
    $image_path = null;
    if (isset($_FILES['product_image']) && !empty($_FILES['product_image']['name']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/products/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); chmod($upload_dir, 0777); }

        $ext     = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid image format. Use JPG, PNG, GIF, or WEBP.";
        } elseif ($_FILES['product_image']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Image must be under 2MB.";
        } else {
            $new_filename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $upload_dir . $new_filename;
            if ((move_uploaded_file($_FILES['product_image']['tmp_name'], $dest) || copy($_FILES['product_image']['tmp_name'], $dest)) && file_exists($dest)) {
                $image_path = 'uploads/products/' . $new_filename;
            } else {
                $errors[] = "Failed to save image.";
            }
        }
    }

    if (empty($name))   $errors[] = "Product name is required.";
    if ($price <= 0)    $errors[] = "Price must be greater than 0.";
    if ($stock_quantity < 0) $errors[] = "Stock quantity cannot be negative.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO pos_items (name, category, price, stock_quantity, image) VALUES (?, ?, ?, ?, ?)");
        $img_val = $image_path ?? null;
        $stmt->bind_param("ssdis", $name, $category, $price, $stock_quantity, $img_val);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    }
    exit();
}

// Handle edit product
if (isset($_POST['edit_product'])) {
    header('Content-Type: application/json');

    $id             = intval($_POST['id']);
    $name           = trim($_POST['name']);
    $category       = strtolower(trim($_POST['category']));
    $price          = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $errors         = [];

    // Handle optional product image upload
    $image_path = null;
    if (!empty($_FILES['product_image']['name']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/products/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); chmod($upload_dir, 0777); }

        $ext     = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid image format. Use JPG, PNG, GIF, or WEBP.";
        } elseif ($_FILES['product_image']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Image must be under 2MB.";
        } else {
            $new_filename = 'product_' . $id . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $new_filename;
            if ((move_uploaded_file($_FILES['product_image']['tmp_name'], $dest) || copy($_FILES['product_image']['tmp_name'], $dest)) && file_exists($dest)) {
                $image_path = 'uploads/products/' . $new_filename;
            } else {
                $errors[] = "Failed to save image.";
            }
        }
    }

    // Determine final image value
    $remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';
    if (!$remove_image && $image_path === null && isset($_POST['current_image']) && $_POST['current_image'] !== '' && $_POST['current_image'] !== '0') {
        $image_path = $_POST['current_image'];
    }

    if (empty($name))   $errors[] = "Product name is required.";
    if ($price <= 0)    $errors[] = "Price must be greater than 0.";
    if ($stock_quantity < 0) $errors[] = "Stock quantity cannot be negative.";

    if (empty($errors)) {
        if ($remove_image) {
            $null_val = null;
            $stmt = $conn->prepare("UPDATE pos_items SET name = ?, category = ?, price = ?, stock_quantity = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssdisi", $name, $category, $price, $stock_quantity, $null_val, $id);
        } elseif ($image_path !== null) {
            $stmt = $conn->prepare("UPDATE pos_items SET name = ?, category = ?, price = ?, stock_quantity = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssdisi", $name, $category, $price, $stock_quantity, $image_path, $id);
        } else {
            $stmt = $conn->prepare("UPDATE pos_items SET name = ?, category = ?, price = ?, stock_quantity = ? WHERE id = ?");
            $stmt->bind_param("ssdii", $name, $category, $price, $stock_quantity, $id);
        }
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update product: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    }
    exit();
}





// Handle delete product
if (isset($_POST['delete_product'])) {
    header('Content-Type: application/json');
    $id = intval($_POST['id']);
    if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid product ID.']); exit(); }
    $stmt = $conn->prepare("UPDATE pos_items SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting product: ' . $stmt->error]);
    }
    $stmt->close();
    exit();
}

// Handle process sale
if (isset($_POST['process_sale'])) {
    $cart_items = isset($_POST['cart_items']) ? json_decode($_POST['cart_items'], true) : [];
    $total_amount = floatval($_POST['total_amount']);
    $payment_method = $_POST['payment_method'];
    $reference_no = isset($_POST['reference_no']) ? trim($_POST['reference_no']) : null;

    $member_id = isset($_POST['member_id']) && !empty($_POST['member_id']) ? intval($_POST['member_id']) : null;

    // Validation
    $errors = [];

    if (empty($cart_items)) {
        $errors[] = "No items in cart.";
    }

    if ($total_amount <= 0) {
        $errors[] = "Invalid total amount.";
    }

    if (empty($payment_method)) {
        $errors[] = "Payment method is required.";
    }

    // Validate reference number for non-cash payments
    if ($payment_method !== 'cash' && empty($reference_no)) {
        $errors[] = "Reference number is required for " . ucfirst($payment_method) . " payments.";
    }

    // Check stock availability
    if (!empty($cart_items)) {
        foreach ($cart_items as $item) {
            $item_id = intval($item['id']);
            $quantity = intval($item['quantity']);

            // Get current stock
            $stock_check = $conn->prepare("SELECT stock_quantity, name FROM pos_items WHERE id = ? AND is_active = 1");
            $stock_check->bind_param("i", $item_id);
            $stock_check->execute();
            $stock_result = $stock_check->get_result();
            $stock_data = $stock_result->fetch_assoc();
            $stock_check->close();

            if (!$stock_data) {
                $errors[] = "Item no longer available.";
            } elseif ($stock_data['stock_quantity'] < $quantity) {
                $errors[] = "Insufficient stock for {$stock_data['name']}. Available: {$stock_data['stock_quantity']}";
            }
        }
    }

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert sale record
            $sale_stmt = $conn->prepare("INSERT INTO pos_sales (total_amount, payment_method, reference_no, member_id, created_by) VALUES (?, ?, ?, ?, ?)");
            $sale_stmt->bind_param("dssii", $total_amount, $payment_method, $reference_no, $member_id, $user['id']);
            $sale_stmt->execute();
            $sale_id = $conn->insert_id;
            $sale_stmt->close();

            // Insert sale items and update stock
            foreach ($cart_items as $item) {
                $item_id = intval($item['id']);
                $quantity = intval($item['quantity']);
                $unit_price = floatval($item['price']);
                $item_total = $unit_price * $quantity;

                // Insert sale item
                $item_stmt = $conn->prepare("INSERT INTO pos_sale_items (sale_id, item_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                $item_stmt->bind_param("iiidd", $sale_id, $item_id, $quantity, $unit_price, $item_total);
                $item_stmt->execute();
                $item_stmt->close();

                // Update stock
                $stock_stmt = $conn->prepare("UPDATE pos_items SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stock_stmt->bind_param("ii", $quantity, $item_id);
                $stock_stmt->execute();
                $stock_stmt->close();
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Sale processed successfully! Sale ID: ' . $sale_id]);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error processing sale: ' . $e->getMessage()]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => implode("<br>", $errors)]);
        exit();
    }
}

// Handle checkout
if (isset($_POST['checkout'])) {
    $cart_items = json_decode($_POST['cart_items'], true);
    $total_amount = floatval($_POST['total_amount']);
    $payment_method = $_POST['payment_method'];
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $member_id = !empty($_POST['member_id']) ? intval($_POST['member_id']) : null;
    $reference_no = trim($_POST['reference_no']);

    if (empty($cart_items) || $total_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart data.']);
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert sale record
        $stmt = $conn->prepare("INSERT INTO pos_sales (total_amount, payment_method, reference_no, customer_name, customer_phone, member_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("dssssii", $total_amount, $payment_method, $reference_no, $customer_name, $customer_phone, $member_id, $user['id']);
        $stmt->execute();
        $sale_id = $stmt->insert_id;
        $stmt->close();

        // Insert sale items and update stock
        foreach ($cart_items as $item) {
            $item_id = intval($item['id']);
            $quantity = intval($item['quantity']);
            $unit_price = floatval($item['price']);
            $total_price = $unit_price * $quantity;

            // Insert sale item
            $stmt = $conn->prepare("INSERT INTO pos_sale_items (sale_id, item_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $sale_id, $item_id, $quantity, $unit_price, $total_price);
            $stmt->execute();
            $stmt->close();

            // Update stock
            $stmt = $conn->prepare("UPDATE pos_items SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $stmt->bind_param("ii", $quantity, $item_id);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Sale completed successfully! Cart cleared.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to process sale.']);
    }
    exit();
}

// Fetch POS items
// Ensure upload directory exists
$upload_dir_check = __DIR__ . '/../uploads/products/';
if (!is_dir($upload_dir_check)) mkdir($upload_dir_check, 0755, true);

$pos_items = $conn->query("SELECT * FROM pos_items WHERE is_active = 1 ORDER BY category, name");

// Fetch members for dropdown
$members = $conn->query("SELECT id, fullname FROM members WHERE status = 'ACTIVE' ORDER BY fullname");

// Fetch today's sales and total
$recent_sales = $conn->query("
    SELECT ps.*, u.fullname as cashier_name,
           COUNT(psi.id) as item_count,
           GROUP_CONCAT(CONCAT(pi.name, ' (', psi.quantity, ')') SEPARATOR ', ') as items_sold
    FROM pos_sales ps
    LEFT JOIN users u ON ps.created_by = u.id
    LEFT JOIN pos_sale_items psi ON ps.id = psi.sale_id
    LEFT JOIN pos_items pi ON psi.item_id = pi.id
    WHERE DATE(ps.created_at) = CURDATE()
    GROUP BY ps.id
    ORDER BY ps.created_at DESC
");

// Calculate today's total sales
$today_total = 0;
$today_sales_count = 0;
if ($recent_sales && $recent_sales->num_rows > 0) {
    $recent_sales->data_seek(0); // Reset pointer
    while ($sale = $recent_sales->fetch_assoc()) {
        $today_total += $sale['total_amount'];
        $today_sales_count++;
    }
    $recent_sales->data_seek(0); // Reset for later use
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - Gym Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/style.css?v=20260630f" rel="stylesheet">
    <style>
        .pos-item {
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .pos-item:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.2);
        }
        .pos-item.selected {
            border-color: #28a745;
            background-color: #f8fff9;
        }
        .product-actions {
            flex-wrap: nowrap;
        }
        .product-action-btn {
            flex: 1 1 auto;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 4px 6px;
            font-size: .72rem;
        }
        .product-action-btn-icon {
            flex: 0 0 32px;
            padding: 4px 0;
        }
        /* Dedicated class for the confirm modal's footer buttons.
           (Bootstrap's own .flex-grow-1 utility is hijacked elsewhere on this
           page for sidebar layout, so it can't be reused here.) */
        .confirm-modal-btn {
            flex: 1 1 0%;
        }
        /* Edit Product modal: column next to the image preview holding the
           file input + Remove Image button. Same reasoning as above —
           Bootstrap's .flex-grow-1 is hijacked on this page, so don't use it. */
        .edit-image-fields {
            flex: 1 1 auto;
            min-width: 0;
        }
        .cart-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 8px;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: #007bff;
            color: white;
            cursor: pointer;
        }
        .quantity-btn:hover {
            background: #0056b3;
        }
        .quantity-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .toast {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 12px 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }
        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }
        .toast.success {
            border-left: 4px solid #28a745;
        }
        .toast.warning {
            border-left: 4px solid #ffc107;
        }
        .toast.danger {
            border-left: 4px solid #dc3545;
        }
        .cart-item {
            animation: slideIn 0.3s ease-out;
        }
        .cart-item.removing {
            animation: slideOut 0.3s ease-out forwards;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateX(0);
                max-height: 100px;
            }
            to {
                opacity: 0;
                transform: translateX(20px);
                max-height: 0;
                margin-bottom: 0;
                padding: 0;
            }
        }
        .today-sales-total:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-<?php echo htmlspecialchars($settings['sidebar_theme']); ?> <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?> vh-100" style="width: 250px;">
            <div class="p-3">
                <div class="text-center mb-4">
                    <img src="../<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Gym Logo" class="rounded-circle mb-2" style="width: 80px; height: 80px;">
                    <h5 class="fw-bold"><?php echo htmlspecialchars($settings['gym_name']); ?></h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i><span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="members.php">
                            <i class="fas fa-users me-2"></i><span>Members</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?> active" href="pos.php">
                            <i class="fas fa-cash-register me-2"></i><span>Point of Sale</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="attendance.php">
                            <i class="fas fa-calendar-check me-2"></i><span>Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="../admin/website_settings.php">
                            <i class="fas fa-globe me-2"></i><span>Website</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="settings.php">
                            <i class="fas fa-cog me-2"></i><span>Settings</span>
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i><span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <!-- Top Bar -->
            <nav class="navbar navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-outline-secondary me-3" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand mb-0 h1">Point of Sale - <?php echo htmlspecialchars($user['fullname']); ?> (Cashier)</span>
                </div>
            </nav>

            <div class="container-fluid mt-4">
                <div class="row">
                    <!-- Products Section -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Available Items</h5>
                                <button type="button" class="btn btn-primary btn-sm" id="addProductBtn">
                                    <i class="fas fa-plus me-1"></i>Add Product
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row" id="productsGrid">
                                    <?php while ($item = $pos_items->fetch_assoc()): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="card pos-item h-100" style="cursor:pointer;transition:transform .15s,box-shadow .15s;" data-item-id="<?php echo $item['id']; ?>" data-item-name="<?php echo htmlspecialchars($item['name']); ?>" data-item-price="<?php echo $item['price']; ?>" data-stock="<?php echo $item['stock_quantity']; ?>">
                                            <?php if (!empty($item['image']) && $item['image'] !== '0'): ?>
                                            <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="card-img-top" style="height:110px;object-fit:cover;border-radius:8px 8px 0 0;">
                                            <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center bg-light" style="height:110px;border-radius:8px 8px 0 0;">
                                                <i class="fas fa-box text-muted" style="font-size:2.5rem;"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div class="card-body text-center p-2">
                                                <h6 class="card-title mb-1 fw-bold" style="font-size:.85rem;line-height:1.2;"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <p class="card-text text-primary fw-bold mb-1" style="font-size:.95rem;">₱<?php echo number_format($item['price'], 2); ?></p>
                                                <small class="text-muted d-block mb-1">Stock: <?php echo $item['stock_quantity']; ?></small>
                                                <span class="badge bg-secondary mb-2" style="font-size:.7rem;"><?php echo ucfirst($item['category']); ?></span>
                                                <div class="d-flex gap-1 product-actions">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-product-btn product-action-btn"
                                                        data-item-id="<?php echo $item['id']; ?>"
                                                        data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                        data-item-price="<?php echo $item['price']; ?>"
                                                        data-item-category="<?php echo $item['category']; ?>"
                                                        data-item-stock="<?php echo $item['stock_quantity']; ?>"
                                                        data-item-image="<?php echo htmlspecialchars($item['image'] ?? ''); ?>">
                                                        <i class="fas fa-edit"></i><span>Edit</span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-product-btn product-action-btn product-action-btn-icon"
                                                        data-item-id="<?php echo $item['id']; ?>"
                                                        data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                        title="Delete product">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Sales Total -->
                        <div class="card mt-3 today-sales-total" style="cursor: pointer; transition: all 0.3s ease;">
                            <div class="card-body text-center">
                                <h6 class="card-title text-muted mb-1">Today's Sales Total</h6>
                                <h3 class="card-text text-success fw-bold mb-0">₱<?php echo number_format($today_total, 2); ?></h3>
                                <small class="text-muted"><?php echo $today_sales_count; ?> transaction<?php echo $today_sales_count !== 1 ? 's' : ''; ?> today</small>
                            </div>
                        </div>
                    </div>

                    <!-- Cart and Checkout Section -->
                    <div class="col-md-4">
                        <!-- Shopping Cart -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Shopping Cart</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="saleForm">
                                    <div id="cartItems" class="mb-3" style="max-height: 300px; overflow-y: auto;">
                                        <!-- Cart items will be added here -->
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Total Amount</label>
                                        <input type="text" class="form-control form-control-lg text-center fw-bold" id="totalAmount" value="₱0.00" readonly>
                                        <input type="hidden" name="total_amount" id="totalAmountHidden" value="0">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select class="form-control" name="payment_method" id="paymentMethod" required>
                                            <option value="cash">Cash</option>
                                            <option value="gcash">GCash</option>
                                            <option value="maya">Maya</option>
                                            <option value="card">Card</option>
                                            <option value="bank">Bank Transfer</option>
                                        </select>
                                    </div>

                        <div class="mb-3" id="referenceField" style="display: none;">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_no" placeholder="Enter reference number" required>
                        </div>



                                    <input type="hidden" name="cart_items" id="cartItemsHidden">

                                    <div class="d-grid gap-2">
                                        <button type="submit" name="process_sale" class="btn btn-success btn-lg" id="processSaleBtn" disabled>
                                            <i class="fas fa-check me-2"></i>Process Sale
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="clearCartBtn">
                                            <i class="fas fa-trash me-2"></i>Clear Cart
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Today's Sales -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Today's Sales</h5>
                            </div>
                            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                <div id="recentSalesContent">
                                    <?php if ($recent_sales && $recent_sales->num_rows > 0): ?>
                                        <?php while ($sale = $recent_sales->fetch_assoc()): ?>
                                        <div class="border-bottom pb-2 mb-2 sale-item">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted"><?php echo date('M d, H:i', strtotime($sale['created_at'])); ?></small>
                                                <strong>₱<?php echo number_format($sale['total_amount'], 2); ?></strong>
                                            </div>
                                            <small><?php echo htmlspecialchars($sale['items_sold']); ?></small>
                                            <br>
                                            <small class="text-muted">by <?php echo htmlspecialchars($sale['cashier_name']); ?></small>
                                        </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No recent sales</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addProductForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-control" name="category" required>
                                <option value="beverage">Beverage</option>
                                <option value="snack">Snack</option>
                                <option value="supplement">Supplement</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Product Image / Logo</label>
                            <input type="file" class="form-control" id="productImage" accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-text">JPG, PNG, GIF or WEBP &bull; Max 2MB</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Initial Stock</label>
                            <input type="number" class="form-control" name="stock_quantity" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editProductForm">
                    <input type="hidden" id="editProductId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Product Image / Logo</label>
                            <div class="d-flex align-items-start gap-3">
                                <div id="editProductImagePreview" style="width:90px;height:90px;border:2px dashed #dee2e6;border-radius:10px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f8f9fa;flex-shrink:0;">
                                    <i class="fas fa-image text-muted" style="font-size:2rem;"></i>
                                </div>
                                <div class="edit-image-fields">
                                    <input type="file" class="form-control" id="editProductImage" accept="image/jpeg,image/png,image/gif,image/webp" style="font-size:.85rem;">
                                    <div class="form-text">JPG, PNG, GIF or WEBP &bull; Max 2MB</div>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-1" id="clearProductImage" style="display:none;">
                                        <i class="fas fa-trash-alt me-1"></i>Remove Image
                                    </button>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="editProductName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-control" id="editProductCategory" required>
                                <option value="beverage">Beverage</option>
                                <option value="snack">Snack</option>
                                <option value="supplement">Supplement</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" class="form-control" id="editProductPrice" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" id="editProductStock" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reusable Confirm Action Modal (Delete Product / Remove Image) -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
                <div class="modal-body text-center p-4 pt-5">
                    <div id="confirmActionIcon" class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width:64px;height:64px;background:#fdecea;">
                        <i class="fas fa-trash-alt" style="font-size:1.6rem;color:#dc3545;"></i>
                    </div>
                    <h5 id="confirmActionTitle" class="fw-bold mb-2">Delete Product?</h5>
                    <p id="confirmActionMessage" class="text-muted mb-4" style="font-size:.9rem;">This action cannot be undone.</p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light confirm-modal-btn" style="border-radius:10px;" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmActionBtn" class="btn btn-danger confirm-modal-btn" style="border-radius:10px;">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Checkout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="checkoutForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-control" name="payment_method" id="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="maya">Maya</option>
                                <option value="card">Card</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3" id="modalReferenceField" style="display: none;">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_no" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Customer Name (Optional)</label>
                            <input type="text" class="form-control" name="customer_name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Customer Phone (Optional)</label>
                            <input type="text" class="form-control" name="customer_phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Member (Optional)</label>
                            <select class="form-control" name="member_id" id="memberSelect">
                                <option value="">Select Member</option>
                                <?php while ($member = $members->fetch_assoc()): ?>
                                    <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['fullname']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total Amount:</strong>
                            <strong id="checkoutTotal">₱0.00</strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Complete Sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="toast" class="toast align-items-center text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/toast.js"></script>
    <script>
        // Reusable confirm-action modal (replaces native confirm() for delete/remove actions)
        function showConfirmModal(opts) {
            const titleEl   = document.getElementById('confirmActionTitle');
            const msgEl     = document.getElementById('confirmActionMessage');
            const iconWrap  = document.getElementById('confirmActionIcon');
            const oldBtn    = document.getElementById('confirmActionBtn');

            titleEl.textContent = opts.title || 'Are you sure?';
            msgEl.textContent   = opts.message || 'This action cannot be undone.';
            iconWrap.style.background = opts.iconBg || '#fdecea';
            iconWrap.innerHTML = `<i class="fas ${opts.icon || 'fa-trash-alt'}" style="font-size:1.6rem;color:${opts.iconColor || '#dc3545'};"></i>`;

            // Clone the confirm button to wipe any previously-attached listeners
            const newBtn = oldBtn.cloneNode(true);
            newBtn.textContent = opts.confirmLabel || 'Confirm';
            newBtn.className = 'btn confirm-modal-btn ' + (opts.confirmClass || 'btn-danger');
            newBtn.style.borderRadius = '10px';
            oldBtn.parentNode.replaceChild(newBtn, oldBtn);

            const modalEl = document.getElementById('confirmActionModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            newBtn.addEventListener('click', function() {
                modal.hide();
                if (typeof opts.onConfirm === 'function') opts.onConfirm();
            });
            modal.show();
        }

        // Cart management
        let cart = [];
        let cartTotal = 0;

        // Initialize cart from localStorage if exists
        document.addEventListener('DOMContentLoaded', function() {
            const savedCart = localStorage.getItem('pos_cart');
            if (savedCart) {
                cart = JSON.parse(savedCart);
                updateCartDisplay();
            }

            // Load members for dropdown
            loadMembers();



            // Setup payment method change handler for main form
            document.getElementById('paymentMethod').addEventListener('change', function() {
                const referenceField = document.getElementById('referenceField');
                if (this.value !== 'cash') {
                    referenceField.style.display = 'block';
                    referenceField.querySelector('label').innerHTML = 'Reference Number *';
                    referenceField.querySelector('input').setAttribute('required', 'required');
                } else {
                    referenceField.style.display = 'none';
                    referenceField.querySelector('input').removeAttribute('required');
                }
            });

            // Trigger change event on page load for main form
            document.getElementById('paymentMethod').dispatchEvent(new Event('change'));

            // Setup add product button
            document.getElementById('addProductBtn').addEventListener('click', function() {
                showAddProductModal();
            });

        });

        // Product click handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.pos-item')) {
                const itemCard = e.target.closest('.pos-item');
                const itemId = parseInt(itemCard.dataset.itemId);
                const itemName = itemCard.dataset.itemName;
                const itemPrice = parseFloat(itemCard.dataset.itemPrice);
                const stock = parseInt(itemCard.dataset.stock);

                addToCart(itemId, itemName, itemPrice, stock);
            }
        });

        function addToCart(id, name, price, stock) {
            if (stock <= 0) {
                showToast('This item is out of stock!', 'danger');
                return;
            }

            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                if (existingItem.quantity >= stock) {
                    showToast('Not enough stock available!', 'warning');
                    return;
                }
                existingItem.quantity++;
            } else {
                cart.push({ id, name, price, quantity: 1, stock });
            }

            updateCartDisplay();
            showToast('Item added to cart!', 'success');
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            updateCartDisplay();
        }

        function updateQuantity(id, change) {
            const item = cart.find(item => item.id === id);
            if (item) {
                const newQuantity = item.quantity + change;
                if (newQuantity <= 0) {
                    removeFromCart(id);
                    return;
                }
                if (newQuantity > item.stock) {
                    showToast('Not enough stock available!', 'warning');
                    return;
                }
                item.quantity = newQuantity;
                updateCartDisplay();
            }
        }

        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            const totalAmountEl = document.getElementById('totalAmount');
            const totalAmountHidden = document.getElementById('totalAmountHidden');
            const processSaleBtn = document.getElementById('processSaleBtn');

            cartItems.innerHTML = '';
            cartTotal = 0;

            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="text-muted text-center">Cart is empty</p>';
                processSaleBtn.disabled = true;
            } else {
                cart.forEach((item, index) => {
                    const itemTotal = item.price * item.quantity;
                    cartTotal += itemTotal;

                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'cart-item';
                    itemDiv.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${item.name}</strong><br>
                                <small class="text-muted">₱${item.price.toFixed(2)} each</small>
                            </div>
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(${index}, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="fw-bold">${item.quantity}</span>
                                <button type="button" class="quantity-btn" onclick="changeQuantity(${index}, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeFromCart(${index})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="text-end mt-1">
                            <strong>₱${itemTotal.toFixed(2)}</strong>
                        </div>
                    `;
                    cartItems.appendChild(itemDiv);
                });
                processSaleBtn.disabled = false;
            }

            totalAmountEl.value = `₱${cartTotal.toFixed(2)}`;
            if (totalAmountHidden) totalAmountHidden.value = cartTotal.toFixed(2);
        }

        function changeQuantity(index, change) {
            const item = cart[index];
            const newQuantity = item.quantity + change;

            if (newQuantity <= 0) {
                removeFromCart(index);
                return;
            }

            // Check stock limit
            if (newQuantity > item.stock) {
                showToast('Not enough stock available!', 'warning');
                return;
            }

            item.quantity = newQuantity;
            updateCartDisplay();
            saveCart();
        }

        function removeFromCart(index) {
            const cartItems = document.getElementById('cartItems');
            const itemDiv = cartItems.children[index];

            if (itemDiv) {
                itemDiv.classList.add('removing');
                setTimeout(() => {
                    cart.splice(index, 1);
                    updateCartDisplay();
                    saveCart();
                }, 300);
            } else {
                cart.splice(index, 1);
                updateCartDisplay();
                saveCart();
            }
        }

        function saveCart() {
            localStorage.setItem('pos_cart', JSON.stringify(cart));
        }

        function showCheckoutModal() {
            document.getElementById('checkoutTotal').textContent = `₱${cartTotal.toFixed(2)}`;
            const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
            modal.show();
        }

        function showAddProductModal() {
            const modal = new bootstrap.Modal(document.getElementById('addProductModal'));
            modal.show();
        }

        function showEditProductModal(btn) {
            const productImage = btn.dataset.itemImage || '';

            document.getElementById('editProductId').value       = btn.dataset.itemId;
            document.getElementById('editProductName').value     = btn.dataset.itemName;
            document.getElementById('editProductPrice').value    = btn.dataset.itemPrice;
            document.getElementById('editProductCategory').value = btn.dataset.itemCategory;
            document.getElementById('editProductStock').value    = btn.dataset.itemStock;
            document.getElementById('editProductImage').value    = '';

            const preview  = document.getElementById('editProductImagePreview');
            const clearBtn = document.getElementById('clearProductImage');
            preview.dataset.removeImage = '0'; // always reset on open
            if (productImage && productImage !== '0') {
                preview.innerHTML = `<img src="../${productImage}" style="width:100%;height:100%;object-fit:cover;">`;
                preview.dataset.currentImage = productImage;
                clearBtn.style.display = 'inline-block';
            } else {
                preview.innerHTML = '<i class="fas fa-image text-muted" style="font-size:2rem;"></i>';
                preview.dataset.currentImage = '';
                clearBtn.style.display = 'none';
            }

            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();
        }

        // Wire up Edit Product buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-product-btn')) {
                e.stopPropagation();
                showEditProductModal(e.target.closest('.edit-product-btn'));
            }
        });



        function loadMembers() {
            // Load members for dropdown
            fetch('../admin/get_members.php')
                .then(response => response.json())
                .then(data => {
                    const memberSelect = document.getElementById('memberSelect');
                    if (memberSelect) {
                        // Clear existing options except the first one
                        memberSelect.innerHTML = '<option value="">Select Member</option>';
                        data.forEach(member => {
                            const option = document.createElement('option');
                            option.value = member.id;
                            option.textContent = `${member.fullname} (${member.member_code})`;
                            memberSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading members:', error));
        }

        // Event listeners
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('add_product', '1');
            formData.append('name', this.name.value);
            formData.append('category', this.category.value);
            formData.append('price', this.price.value);
            formData.append('stock_quantity', this.stock_quantity.value);

            // Append image if selected
            const imgFile = document.getElementById('productImage')?.files?.[0];
            if (imgFile) formData.append('product_image', imgFile);

            fetch('pos.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                    modal.hide();
                    this.reset();
                    document.getElementById('productImage').value = '';
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while adding the product.', 'danger');
            });
        });

        // Edit product image preview
        document.getElementById('editProductImage').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('editProductImagePreview');
            const clearBtn = document.getElementById('clearProductImage');
            if (file) {
                const reader = new FileReader();
                reader.onload = e => { preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`; };
                reader.readAsDataURL(file);
                clearBtn.style.display = 'inline-block';
                preview.dataset.removeImage = '0'; // new file chosen — cancel any pending removal
            }
        });

        document.getElementById('clearProductImage').addEventListener('click', function() {
            const clearBtn = this;
            showConfirmModal({
                title: 'Remove Image?',
                message: 'This will remove the current product image. You can upload a new one anytime.',
                confirmLabel: 'Remove',
                confirmClass: 'btn-danger',
                icon: 'fa-image',
                iconBg: '#fdecea',
                iconColor: '#dc3545',
                onConfirm: function() {
                    document.getElementById('editProductImage').value = '';
                    const preview = document.getElementById('editProductImagePreview');
                    preview.innerHTML = '<i class="fas fa-image text-muted" style="font-size:2rem;"></i>';
                    preview.dataset.currentImage = '';
                    preview.dataset.removeImage = '1'; // tell PHP to NULL the image column
                    clearBtn.style.display = 'none';
                }
            });
        });

        // Delete product handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-product-btn')) {
                e.stopPropagation();
                const btn  = e.target.closest('.delete-product-btn');
                const id   = btn.dataset.itemId;
                const name = btn.dataset.itemName;

                showConfirmModal({
                    title: 'Delete Product?',
                    message: `Delete "${name}"? This will hide it from the POS. Sales history will be kept.`,
                    confirmLabel: 'Delete',
                    confirmClass: 'btn-danger',
                    icon: 'fa-trash-alt',
                    iconBg: '#fdecea',
                    iconColor: '#dc3545',
                    onConfirm: function() {
                        const fd = new FormData();
                        fd.append('delete_product', '1');
                        fd.append('id', id);

                        fetch('pos.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message, 'success');
                                const card = btn.closest('.col-md-3');
                                if (card) card.remove();
                            } else {
                                showToast(data.message, 'danger');
                            }
                        })
                        .catch(() => showToast('Error deleting product.', 'danger'));
                    }
                });
            }
        });

        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('edit_product', '1');
            formData.append('id', document.getElementById('editProductId').value);
            formData.append('name', document.getElementById('editProductName').value);
            formData.append('category', document.getElementById('editProductCategory').value);
            formData.append('price', document.getElementById('editProductPrice').value);
            formData.append('stock_quantity', document.getElementById('editProductStock').value);

            // Send remove flag + current image path
            const preview    = document.getElementById('editProductImagePreview');
            const currentImg = preview.dataset.currentImage || '';
            const removeImg  = preview.dataset.removeImage  || '0';
            formData.append('current_image', currentImg);
            formData.append('remove_image',  removeImg);

            const imageFile = document.getElementById('editProductImage').files[0];
            if (imageFile) formData.append('product_image', imageFile);

            fetch('pos.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editProductModal'));
                    modal.hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while updating the product.', 'danger');
            });
        });

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('checkout', '1');
            formData.append('cart_items', JSON.stringify(cart));
            formData.append('total_amount', cartTotal);
            formData.append('payment_method', this.payment_method.value);
            formData.append('customer_name', this.customer_name.value);
            formData.append('customer_phone', this.customer_phone.value);
            formData.append('member_id', this.member_id.value);
            formData.append('reference_no', this.reference_no.value);

            fetch('pos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
                    modal.hide();
                    this.reset();
                    cart = [];
                    updateCartDisplay();
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred during checkout.', 'danger');
            });
        });

        document.getElementById('payment_method').addEventListener('change', function() {
            const referenceField = document.getElementById('modalReferenceField');
            if (this.value !== 'cash') {
                referenceField.style.display = 'block';
                referenceField.querySelector('label').innerHTML = 'Reference Number *';
                referenceField.querySelector('input').setAttribute('required', 'required');
            } else {
                referenceField.style.display = 'none';
                referenceField.querySelector('input').removeAttribute('required');
            }
        });

        // Clear cart button
        document.getElementById('clearCartBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear the cart?')) {
                cart = [];
                updateCartDisplay();
                saveCart();
                showToast('Cart cleared!', 'info');
            }
        });



        // Process sale form submission
        document.getElementById('saleForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('process_sale', '1');
            formData.append('cart_items', JSON.stringify(cart));

            fetch('pos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    cart = [];
                    updateCartDisplay();
                    saveCart();
                    // Reload page immediately to show new sales
                    location.reload();
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred during sale processing.', 'danger');
            });
        });

    </script>
    <script src="../assets/sidebar.js"></script>
</body>
</html>
