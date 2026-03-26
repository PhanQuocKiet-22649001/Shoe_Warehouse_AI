<?php
session_start();

// 1. Gọi Database & Models trước (Sửa lỗi "Class not found")
require_once '../config/database.php';
require_once '../app/models/UserModel.php';
require_once '../app/models/CategoryModel.php';
require_once '../app/models/ProductModel.php';

// 2. Gọi Controllers
require_once '../app/controllers/AuthController.php';
require_once '../app/controllers/UserController.php';
require_once '../app/controllers/CategoryController.php';
require_once '../app/controllers/ProductController.php';

// 3. Khởi tạo Controllers
$authController = new AuthController();
$userController = new UserController();
$categoryController = new CategoryController();
$productController = new ProductController();

// ===== XỬ LÝ LOGIN =====
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SESSION['user_id'])) {
    $error = $authController->handleLogin();
}

// ===== KIỂM TRA ĐĂNG NHẬP =====
if (!isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../app/views/pages/login.php';
    exit;
}

// ===== XỬ LÝ LOGOUT =====
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

if ($page === 'search_ajax') {
    $productController->ajaxSearch();
    exit; // Dừng chương trình ngay, không cho chạy xuống phần HTML bên dưới
}

// ===== 4. XỬ LÝ CÁC YÊU CẦU POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['btn_update_profile'])) {
        $userController->UpdateProfile();
    }
    // Xử lý trang Nhân viên
    elseif ($page === 'employees') {
        if (isset($_POST['add_user'])) {
            $userController->add();
        } elseif (isset($_POST['delete'])) {
            $userController->delete($_POST['user_id']);
        } elseif (isset($_POST['update'])) {
            $userController->update();
        }
    }
    // Xử lý trang Danh mục hãng giày
    elseif ($page === 'categories') {
        if (isset($_POST['add_category'])) {
            $categoryController->addBrand();
        } elseif (isset($_POST['delete'])) {
            $categoryController->delete($_POST['category_id']);
        } elseif (isset($_POST['toggle_status'])) {
            $categoryController->toggleStatus();
        }
    }
    // Thêm case xử lý cho trang Sản phẩm
    elseif ($page === 'products') {
        if (isset($_POST['toggle_status'])) {
            $productController->toggleStatus();
        } elseif (isset($_POST['delete_product'])) {
            $productController->softDelete();
        } elseif (isset($_POST['add_product'])) {
            $productController->add();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/categories.css">
</head>

<body class="m-0 p-0">

    <div class="app">
        <?php require_once __DIR__ . '/../app/views/layouts/sidebar.php'; ?>

        <div class="main">
            <?php include __DIR__ . '/../app/views/layouts/header.php'; ?>

            <div class="content">
                <div class="page-body">
                    <?php
                    switch ($page) {

                        case 'employees':
                            if ($_SESSION['role'] !== 'MANAGER') {
                                header("Location: index.php?page=dashboard");
                                exit;
                            }
                            $users = $userController->loadEmployees();
                            require_once __DIR__ . '/../app/views/pages/employees.php';
                            break;

                        case 'categories':
                            // Lấy danh sách hãng giày để hiển thị
                            $categories = $categoryController->loadCategories();
                            require_once __DIR__ . '/../app/views/pages/categories.php';
                            break;

                        case 'dashboard':
                        default:
                            require_once __DIR__ . '/../app/views/pages/dashboard.php';
                            break;

                        case 'products':
                            $data = $productController->showByCategory();
                            $products = $data['products'];
                            $categoryName = $data['categoryName'];
                            require_once __DIR__ . '/../app/views/pages/products.php';
                            break;
                    }
                    ?>
                </div>
            </div>
            <?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>