<?php
class ProductController
{
    private $productModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
    }

    // Kiểm tra quyền MANAGER
    private function checkManager()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MANAGER') {
            $_SESSION['error'] = "Bạn không có quyền thực hiện chức năng này!";
            header("Location: index.php?page=dashboard");
            exit;
        }
    }

    public function showByCategory()
    {
        // 1. Lấy ID danh mục từ URL
        $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;

        if (!$category_id) {
            header("Location: index.php?page=categories");
            exit;
        }

        // 2. Lấy tên hãng và danh sách sản phẩm chính
        $categoryName = $this->productModel->getCategoryName($category_id);
        $products = $this->productModel->getByCategory($category_id);

        // 3. HÀM MỚI/LOGIC MỚI: Đổ dữ liệu biến thể vào từng sản phẩm
        if (!empty($products)) {
            // Lưu ý dấu & trước $pro để thay đổi trực tiếp giá trị trong mảng $products
            foreach ($products as &$pro) {
                $pro['variants'] = $this->productModel->getVariantsByProductId($pro['product_id']);
            }
        }

        // 4. Trả về kết quả cho Router (index.php) để render ra View
        return [
            'products' => $products,
            'categoryName' => $categoryName
        ];
    }

    // Xử lý Bật/Tắt trạng thái giày
    public function toggleStatus()
    {
        $this->checkManager();
        $id = $_POST['product_id'];
        $category_id = $_POST['category_id'];
        $currentStatus = $_POST['current_status'];
        $newStatus = ($currentStatus == 't' || $currentStatus == 1) ? 'f' : 't';

        // KIỂM TRA: Nếu bồ muốn BẬT sản phẩm lẻ lên
        if ($newStatus === 't') {
            $categoryModel = new CategoryModel();
            $parentCat = $categoryModel->getById($category_id);

            if ($parentCat['status'] == 'f' || $parentCat['status'] == 0) {
                // Đổi thành biến này để Javascript nhận diện hiện Alert trình duyệt
                $_SESSION['browser_alert'] = "Không thể bật! Hãng giày này đang ngừng kinh doanh. Bạn phải bật hãng trước nhé!";
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }
        }

        if ($this->productModel->updateStatus($id, $newStatus)) {
            $_SESSION['success'] = "Cập nhật trạng thái thành công!";
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Xử lý Xóa mềm
    public function softDelete()
    {
        $this->checkManager();
        $id = $_POST['product_id'];
        $this->productModel->delete($id);
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // tìm kiếm sản phẩm
    public function ajaxSearch()
    {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        if (empty($keyword)) {
            echo json_encode([]);
            exit;
        }

        $results = $this->productModel->searchProducts($keyword);

        // Trả về JSON để Javascript xử lý
        header('Content-Type: application/json');
        echo json_encode($results ? $results : []);
        exit;
    }


    // thêm sản phẩm mới do nhân viên thực hiện
    public function add()
    {
        if (isset($_POST['add_product'])) {
            // 0. Lấy ID người dùng đang đăng nhập (Người thực hiện thêm sản phẩm)
            $userId = $_SESSION['user_id'] ?? null;

            $category_id = $_POST['category_id'];
            $product_name = trim($_POST['product_name']);
            $color = trim($_POST['color']);
            $size = $_POST['size'];
            $stock = $_POST['stock'];

            // 1. Xử lý Upload Ảnh (Giữ nguyên logic của bồ)
            $imageName = 'default_shoe.png';
            if (!empty($_FILES['product_image']['name'])) {
                $imageName = time() . '_' . $_FILES['product_image']['name'];
                move_uploaded_file($_FILES['product_image']['tmp_name'], 'assets/img_product/' . $imageName);
            }

            // 2. TẠO MÃ SKU (Định dạng: HÃNG-TÊN-MÀU-SIZE)
            $categoryModel = new CategoryModel();
            $cat = $categoryModel->getById($category_id);

            // Viết tắt hãng (2 chữ đầu): Nike -> NK
            $brandPart = strtoupper(substr($cat['category_name'], 0, 2));

            // Viết tắt tên sản phẩm (Chữ cái đầu mỗi từ): Sacai Waffle -> SW
            $words = explode(" ", $product_name);
            $namePart = "";
            foreach ($words as $w) {
                if (!empty($w)) $namePart .= strtoupper(substr($w, 0, 1));
            }

            // Viết tắt màu (3 chữ đầu): Grey -> GRE
            $colorPart = strtoupper(substr($color, 0, 3));

            // CHỐT SKU: NK-SW-GRE-42
            $sku = $brandPart . "-" . $namePart . "-" . $colorPart . "-" . $size;

            // 3. Ghi vào Database
            // Lưu ý: Nhớ truyền thêm $userId vào hàm create ở Model nhé
            $productId = $this->productModel->create($product_name, $category_id, $imageName, $userId);

            if ($productId) {
                // Thêm biến thể tương ứng
                $this->productModel->createVariant($productId, $size, $color, $stock, $sku);

                $_SESSION['success'] = "Nhập kho thành công!";
            } else {
                $_SESSION['error'] = "Không thể tạo sản phẩm mới.";
            }

            header("Location: index.php?page=products&category_id=" . $category_id);
            exit;
        }
    }
}
