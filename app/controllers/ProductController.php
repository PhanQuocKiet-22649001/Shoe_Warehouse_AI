<?php
class ProductController
{
    private $productModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
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
        $id = $_POST['product_id'];
        $currentStatus = $_POST['current_status'];
        $newStatus = ($currentStatus == 't' || $currentStatus == 1) ? 'f' : 't';

        $this->productModel->updateStatus($id, $newStatus);
        // Quay lại đúng trang sản phẩm của hãng đó
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Xử lý Xóa mềm
    public function softDelete()
    {
        $id = $_POST['product_id'];
        $this->productModel->delete($id);
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
}
