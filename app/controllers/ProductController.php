<?php
require_once __DIR__ . '/../../ai_services/VisionService.php';

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
    // app/controllers/ProductController.php

    public function add()
    {
        if (isset($_POST['add_product'])) {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                header("Location: index.php?page=login");
                exit;
            }

            $category_id  = intval($_POST['category_id']);
            $product_name = trim($_POST['product_name']);
            $color        = trim($_POST['color']);
            $size         = trim($_POST['size']);
            $stock_input  = intval($_POST['stock']);

            // Lấy tên hãng để đối soát
            $categoryModel = new CategoryModel();
            $cat = $categoryModel->getById($category_id);
            $brandName = $cat['category_name'];

            // --- BƯỚC 1: ĐỐI SOÁT SẢN PHẨM (CHA) ---
            $existingProduct = $this->productModel->findExistingProduct($brandName, $product_name);

            if ($existingProduct) {
                $productId = $existingProduct['product_id'];

                // --- BƯỚC 2: ĐỐI SOÁT BIẾN THỂ (SIZE + MÀU) ---
                $existingVariant = $this->productModel->findVariant($productId, $size, $color);

                if ($existingVariant) {
                    // TRƯỜNG HỢP 1: Trùng sạch -> Cập nhật cộng dồn số lượng
                    $this->productModel->addStock($existingVariant['variant_id'], $stock_input);
                    $_SESSION['success'] = "✨ Sản phẩm đã có! Đã cộng dồn {$stock_input} đôi vào kho.";
                } else {
                    // TRƯỜNG HỢP 2: Giày đã có nhưng khác Size/Màu -> Tạo biến thể mới
                    $cleanName = $this->removeAccents($product_name);
                    $sku = strtoupper(substr($brandName, 0, 2)) . "-" . strtoupper(substr($cleanName, 0, 3)) . "-" . $size;

                    $this->productModel->createVariant($productId, $size, $color, $stock_input, $sku);
                    $_SESSION['success'] = "👟 Đã thêm biến thể mới (Size $size - $color) cho đôi $product_name.";
                }
            } else {
                // --- BƯỚC 3: SẢN PHẨM MỚI HOÀN TOÀN ---
                $imageName = !empty($_POST['temp_image_name']) ? $_POST['temp_image_name'] : 'default_shoe.png';
                $productId = $this->productModel->create($product_name, $category_id, $imageName, $userId);

                if ($productId) {
                    $cleanName = $this->removeAccents($product_name);
                    $sku = strtoupper(substr($brandName, 0, 2)) . "-" . strtoupper(substr($cleanName, 0, 3)) . "-" . $size;

                    $this->productModel->createVariant($productId, $size, $color, $stock_input, $sku);
                    $_SESSION['success'] = "🆕 Đã nhập kho sản phẩm mới thành công!";
                }
            }

            header("Location: index.php?page=products&category_id=" . $category_id);
            exit;
        }
    }

    // Hàm chuẩn hóa màu sắc Đỏ -> Red để DB không bị loạn
    private function normalizeColor($color)
    {
        $color = strtolower(trim($color));
        $map = [
            'black' => 'Đen',
            'white' => 'Trắng',
            'red'   => 'Đỏ',
            'blue'  => 'Xanh dương',
            'grey'  => 'Xám',
            'gray'  => 'Xám',
            'green' => 'Xanh lá',
            'yellow' => 'Vàng',
            'pink'  => 'Hồng',
            'brown' => 'Nâu',
            'orange' => 'Cam'
        ];

        // Nếu tìm thấy trong từ điển thì dịch, không thì viết hoa chữ cái đầu
        return $map[$color] ?? ucfirst($color);
    }
    // Hàm phụ để khử dấu - Bồ dán cái này xuống DƯỚI hàm add() nhưng vẫn TRONG class nhé
    private function removeAccents($str)
    {
        $accents = array(
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        );
        foreach ($accents as $non_accent => $accent) {
            $str = preg_replace("/($accent)/i", $non_accent, $str);
        }
        return $str;
    }
    // quét ảnh AI
    // app/controllers/ProductController.php

    public function scanWithAI()
    {
        // 1. Chặn mọi output lạ lọt vào JSON (Fix triệt để lỗi Code 200)
        ob_start();
        header('Content-Type: application/json');

        try {
            if (!isset($_FILES['image'])) {
                throw new Exception("Không tìm thấy file ảnh gửi lên.");
            }

            $vision = new VisionService();
            $dir = 'assets/img_product/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $tempImageName = time() . '_' . uniqid() . '.jpg';
            $tempPath = $dir . $tempImageName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $tempPath)) {
                throw new Exception("Lỗi lưu file vào thư mục assets.");
            }

            // 2. Gọi AI lấy kết quả
            $aiResponse = $vision->analyzeProductImage($tempPath);

            // --- BƯỚC QUAN TRỌNG: BÓC TÁCH JSON SẠCH SẼ ---
            $ai = null;
            if (is_array($aiResponse)) {
                $ai = $aiResponse;
            } else {
                // Nếu AI trả về chuỗi, tìm và chỉ lấy phần nằm trong ngoặc nhọn { ... }
                // Xóa bỏ mọi Markdown (```json) hoặc câu chữ thừa
                if (preg_match('/\{.*\}/s', $aiResponse, $matches)) {
                    $ai = json_decode($matches[0], true);
                } else {
                    $ai = json_decode($aiResponse, true);
                }
            }

            // 3. Xử lý dữ liệu
            if ($ai && ($ai['status'] ?? '') === 'success') {

                // Làm sạch Brand & Model (Xóa sạch mọi dấu ngoặc kép, ký tự lạ)
                $cleanBrand = preg_replace('/[^\p{L}0-9\s]/u', '', $ai['brand'] ?? 'Unknown');
                $cleanModel = preg_replace('/[^\p{L}0-9\s]/u', '', $ai['model'] ?? 'Shoe');

                // Xóa khoảng trắng thừa
                $detectedBrand = trim(preg_replace('/\s+/', ' ', $cleanBrand));
                $detectedModel = trim(preg_replace('/\s+/', ' ', $cleanModel));

                // NHẬN DIỆN MÀU: Lấy từ AI và đưa qua hàm chuẩn hóa
                $rawColor = $ai['color'] ?? 'Trắng'; // Nếu AI quên màu, mặc định là Trắng
                $color = $this->normalizeColor($rawColor);

                // Đối soát DB
                $match = $this->productModel->findExistingProduct($detectedBrand, $detectedModel);

                // Xóa bộ đệm ngay trước khi xuất JSON để đảm bảo 100% sạch
                ob_clean();

                if ($match) {
                    echo json_encode([
                        "status" => "exists",
                        "product" => $match,
                        "detected_color" => $color,
                        "temp_image" => $tempImageName
                    ]);
                } else {
                    echo json_encode([
                        "status" => "new",
                        "suggestion" => [
                            "brand" => $detectedBrand,
                            "model" => $detectedModel,
                            "color" => $color
                        ],
                        "temp_image" => $tempImageName
                    ]);
                }
            } else {
                throw new Exception($ai['message'] ?? "AI trả về dữ liệu không hợp lệ: " . json_encode($aiResponse));
            }
        } catch (Exception $e) {
            ob_clean();
            echo json_encode(["error" => $e->getMessage()]);
        }
        exit;
    }


    // trả về danh sách màu
    public function getColorsAjax()
    {
        ob_start(); // Bắt đầu gom rác
        header('Content-Type: application/json');

        try {
            $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

            if ($product_id > 0) {
                $colors = $this->productModel->getColorsByProduct($product_id);
            } else {
                $colors = [];
            }

            ob_clean();
            echo json_encode($colors);
        } catch (Exception $e) {
            ob_clean();
            echo json_encode([]);
        }
        exit;
    }
}
