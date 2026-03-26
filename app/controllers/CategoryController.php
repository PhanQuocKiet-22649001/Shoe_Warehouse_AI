<?php
require_once __DIR__ . '/../models/CategoryModel.php';

class CategoryController
{
    private $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    // Hiển thị danh sách hãng
    public function loadCategories()
    {
        return $this->categoryModel->getAll();
    }

    // Kiểm tra quyền MANAGER
    private function checkManager()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MANAGER') {
            $_SESSION['error'] = "Bồ không có quyền thực hiện chức năng này!";
            header("Location: index.php?page=dashboard");
            exit;
        }
    }

    /**
     * Xử lý thêm hãng mới kèm Logo
     */
    public function addBrand()
    {
        $this->checkManager();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['category_name']);

            $logo_name = 'default_brand.jpg';

            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                $file_tmp = $_FILES['logo']['tmp_name'];
                $file_name = $_FILES['logo']['name'];
                $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($extension, $allowed_extensions)) {
                    // Tạo tên file duy nhất
                    $new_logo_name = strtolower(str_replace(' ', '_', $name)) . '_' . time() . '.' . $extension;
                    $target_path = "assets/img_logo/" . $new_logo_name;

                    if (move_uploaded_file($file_tmp, $target_path)) {
                        // Nếu upload thành công, cập nhật lại biến $logo_name để lưu vào DB
                        $logo_name = $new_logo_name;
                    } else {
                        $_SESSION['error'] = "Không thể lưu file ảnh vào thư mục!";
                        header("Location: index.php?page=categories");
                        exit;
                    }
                } else {
                    $_SESSION['error'] = "Chỉ chấp nhận định dạng JPG, PNG hoặc WEBP!";
                    header("Location: index.php?page=categories");
                    exit;
                }
            }

            if (!empty($name)) {
                if ($this->categoryModel->create($name, $logo_name)) {
                    $_SESSION['success'] = "Đã thêm hãng $name thành công!";
                } else {
                    $_SESSION['error'] = "Lỗi Database rồi bồ ơi!";
                }
            }

            header("Location: index.php?page=categories");
            exit;
        }
    }

    /**
     * Xử lý xóa hãng (Soft Delete)
     */
    public function delete($id)
    {
        $this->checkManager();
        if ($this->categoryModel->delete($id)) {
            $_SESSION['success'] = "Đã dọn dẹp hãng giày thành công!";
        } else {
            $_SESSION['error'] = "Xóa không được bồ ơi, kiểm tra lại Database xem!";
        }
        header("Location: index.php?page=categories");
        exit;
    }

    // bật tắt trạng thái kinh doanh
    public function toggleStatus() {
    $this->checkManager();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['category_id'];
        // Lấy trạng thái hiện tại từ form gửi lên
        $current = $_POST['current_status']; 

        // Đảo ngược trạng thái: Nếu đang 't' (true) thì đổi thành 'f' (false) và ngược lại
        $newStatus = ($current == 't' || $current == 1) ? 'f' : 't';

        if ($this->categoryModel->updateStatus($id, $newStatus)) {
            $_SESSION['success'] = "Đã cập nhật trạng thái hãng giày!";
        } else {
            $_SESSION['error'] = "Cập nhật trạng thái thất bại!";
        }

        header("Location: index.php?page=categories");
        exit;
    }
}
}
