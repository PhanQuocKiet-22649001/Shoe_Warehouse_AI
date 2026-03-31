<?php
require_once __DIR__ . '/../../ai_services/VisionService.php';

class ProductController
{
    private $productModel;

    /**
     * Khởi tạo Controller và gọi Model tương ứng
     */
    public function __construct()
    {
        $this->productModel = new ProductModel();
    }

    /**
     * Middleware: Kiểm tra quyền quản trị (MANAGER)
     * Ngăn chặn nhân viên (STAFF) thực hiện các thao tác nhạy cảm
     */
    private function checkManager()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MANAGER') {
            $_SESSION['error'] = "Bạn không có quyền thực hiện chức năng này!";
            header("Location: index.php?page=dashboard");
            exit;
        }
    }

    /**
     * Hiển thị danh sách sản phẩm theo danh mục (Hãng giày)
     * Tự động lồng ghép danh sách biến thể (size, màu) vào từng sản phẩm
     */
    public function showByCategory()
    {
        $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;

        if (!$category_id) {
            header("Location: index.php?page=categories");
            exit;
        }

        $categoryName = $this->productModel->getCategoryName($category_id);
        $products = $this->productModel->getByCategory($category_id);

        if (!empty($products)) {
            foreach ($products as &$pro) {
                $pro['variants'] = $this->productModel->getVariantsByProductId($pro['product_id']);
            }
        }

        return [
            'products' => $products,
            'categoryName' => $categoryName
        ];
    }

    /**
     * Bật/Tắt trạng thái kinh doanh của một sản phẩm
     * Có kiểm tra ràng buộc: Không cho phép bật nếu Hãng mẹ đang bị tắt
     */
    public function toggleStatus()
    {
        $this->checkManager();
        $id = $_POST['product_id'];
        $category_id = $_POST['category_id'];
        $currentStatus = $_POST['current_status'];
        $newStatus = ($currentStatus == 't' || $currentStatus == 1) ? 'f' : 't';

        if ($newStatus === 't') {
            $categoryModel = new CategoryModel();
            $parentCat = $categoryModel->getById($category_id);

            if ($parentCat['status'] == 'f' || $parentCat['status'] == 0) {
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

    /**
     * Xóa mềm sản phẩm (Chuyển trạng thái is_deleted = true)
     * Giữ lại dữ liệu trong DB để đối soát AI và lịch sử
     */
    public function softDelete()
    {
        $this->checkManager();
        $id = $_POST['product_id'];
        $this->productModel->delete($id);
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    /**
     * API: Tìm kiếm sản phẩm nhanh trên thanh Topbar (AJAX)
     */
    public function ajaxSearch()
    {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        if (empty($keyword)) {
            echo json_encode([]);
            exit;
        }

        $results = $this->productModel->searchProducts($keyword);
        header('Content-Type: application/json');
        echo json_encode($results ? $results : []);
        exit;
    }

    public function add()
    {
        if (isset($_POST['add_product'])) {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                header("Location: index.php?page=login");
                exit;
            }

            $db = $this->productModel->getConnection();
            $categoryModel = new CategoryModel();

            pg_query($db, "BEGIN");

            try {
                // --- BƯỚC 1: XỬ LÝ HÃNG (ĐÃ CẬP NHẬT) ---
                // Ưu tiên lấy brand_name từ ô input văn bản vì bồ đã mở khóa cho phép sửa
                $brandName = isset($_POST['brand_name']) ? trim($_POST['brand_name']) : '';

                // Nếu người dùng không nhập tên hãng mới, thử lấy từ category_id ẩn (nếu có)
                if (empty($brandName) && isset($_POST['category_id']) && intval($_POST['category_id']) > 0) {
                    $cat = $categoryModel->getById(intval($_POST['category_id']));
                    $brandName = $cat ? $cat['category_name'] : '';
                }

                if (empty($brandName)) {
                    throw new Exception("Vui lòng nhập tên Hãng sản xuất!");
                }

                // Dùng tên hãng để lấy ID (Hàm này sẽ tự tạo hãng mới nếu chưa tồn tại)
                $category_id = $categoryModel->getCategoryIdByName($brandName, $userId);

                if (!$category_id) {
                    throw new Exception("Không thể xác định hoặc tạo mới Hãng: " . $brandName);
                }
                // ------------------------------------------

                // 2. LẤY THÔNG TIN CÒN LẠI
                $product_name = trim($_POST['product_name']);
                if (empty($product_name)) throw new Exception("Vui lòng nhập tên sản phẩm!");

                $raw_color    = (isset($_POST['color']) && $_POST['color'] === 'new' && !empty($_POST['new_color'])) ? $_POST['new_color'] : $_POST['color'];
                $color        = trim($this->normalizeColor($raw_color));
                $size         = trim($_POST['size']);
                $stock_input  = intval($_POST['stock']);
                $vector_json  = isset($_POST['vector']) ? $_POST['vector'] : null;

                $existingProduct = $this->productModel->findExistingProduct($brandName, $product_name);

                if ($existingProduct) {
                    // --- TRƯỜNG HỢP SẢN PHẨM ĐÃ TỒN TẠI ---
                    $productId = $existingProduct['product_id'];
                    $existingVariant = $this->productModel->findVariant($productId, $size, $color);

                    if ($existingVariant) {
                        $res = $this->productModel->addStock($existingVariant['variant_id'], $stock_input);
                        if (!$res) throw new Exception("Lỗi cộng dồn kho!");
                        $msg = "Sản phẩm đã có. Đã cập nhật số lượng cho hãng $brandName.";
                    } else {
                        $cleanName = $this->removeAccents($product_name);
                        $sku = strtoupper(substr($brandName, 0, 2)) . "-" . strtoupper(substr($cleanName, 0, 3)) . "-" . $size;
                        $res = $this->productModel->createVariant($productId, $size, $color, $stock_input, $sku);
                        if (!$res) throw new Exception("Lỗi tạo biến thể mới!");
                        $msg = "Đã thêm biến thể mới cho $product_name ($brandName).";
                    }
                } else {
                    // --- TRƯỜNG HỢP TẠO MỚI HOÀN TOÀN ---
                    $imageName = (!empty($_POST['temp_image_name']) && $_POST['temp_image_name'] !== 'undefined')
                        ? $_POST['temp_image_name']
                        : 'default_shoe.png';

                    $productId = $this->productModel->create($product_name, $category_id, $imageName, $userId);
                    if (!$productId) throw new Exception("Lỗi tạo sản phẩm mẫu!");

                    // LƯU VECTOR VÀO DATABASE
                    if ($vector_json && $vector_json !== 'undefined' && $vector_json !== 'null') {
                        $vectorArray = json_decode($vector_json, true);
                        if (is_array($vectorArray)) {
                            $resVec = $this->productModel->updateImageEmbedding($productId, $vectorArray);
                            if (!$resVec) throw new Exception("Lỗi lưu định danh AI vào DB!");
                        }
                    }

                    $cleanName = $this->removeAccents($product_name);
                    $sku = strtoupper(substr($brandName, 0, 2)) . "-" . strtoupper(substr($cleanName, 0, 3)) . "-" . $size;
                    $resVar = $this->productModel->createVariant($productId, $size, $color, $stock_input, $sku);
                    if (!$resVar) throw new Exception("Lỗi tạo biến thể mẫu!");

                    $msg = "Khởi tạo sản phẩm mẫu AI thành công cho hãng $brandName ($imageName).";
                }

                pg_query($db, "COMMIT");
                $_SESSION['success'] = $msg;
            } catch (Exception $e) {
                pg_query($db, "ROLLBACK");
                $_SESSION['error'] = "Nhập kho thất bại: " . $e->getMessage();
            }

            header("Location: index.php?page=products&category_id=" . $category_id);
            exit;
        }
    }
    /**
     * Hàm phụ trợ: Chuẩn hóa dữ liệu màu sắc (Anh -> Việt)
     * Tránh tình trạng rác dữ liệu DB do nhập liệu không nhất quán
     */
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
        return $map[$color] ?? ucfirst($color);
    }

    /**
     * Hàm phụ trợ: Loại bỏ dấu tiếng Việt để tạo mã SKU chuẩn
     */
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

    /**
     * API: Quét Batch Nhận diện hình ảnh bằng Local AI (Tối đa 3 ảnh)
     * Trích xuất Vector -> Đo khoảng cách Cosine -> Trả về kết quả phân loại (Match/Confirm/New)
     */
    public function scanWithAI()
    {
        ob_start();
        header('Content-Type: application/json');

        try {
            if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
                throw new Exception("Không tìm thấy tệp tin hình ảnh.");
            }

            $vision = new VisionService();
            $dir = 'assets/img_product/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $results = [];
            $files = $_FILES['images'];
            $fileCount = count($files['name']);
            $limit = min($fileCount, 3);

            for ($i = 0; $i < $limit; $i++) {
                $tmpName = $files['tmp_name'][$i];
                $tempImageName = time() . '_' . uniqid() . '.jpg';
                $tempPath = $dir . $tempImageName;

                if (!move_uploaded_file($tmpName, $tempPath)) {
                    $results[] = ["status" => "error", "message" => "Lỗi lưu file cục bộ."];
                    continue;
                }

                // 1. Gọi AI để lấy Vector
                $aiResponse = $vision->generateVector($tempPath);

                if ($aiResponse['status'] === 'success') {
                    $vectorArray = $aiResponse['vector'];

                    // 2. Gọi hàm mới: Lấy top 3 sản phẩm có độ tương đồng >= 80%
                    $matches = $this->productModel->findTopMatchesByAI($vectorArray, 3);

                    $itemData = [
                        "temp_image" => $tempImageName,
                        "vector"     => $vectorArray,
                        "matches"    => $matches, // Trả về mảng để JS xử lý gợi ý
                        "similarity" => 0,
                        "status"     => "new"
                    ];

                    // 3. Phân loại kịch bản đối soát
                    if (!empty($matches)) {
                        $topMatch = $matches[0];
                        $topScore = (float)$topMatch['similarity_score'];
                        $itemData["similarity"] = round($topScore * 100, 2);

                        if ($topScore >= 0.95) {
                            // KỊCH BẢN 1: TỰ ĐIỀN (>= 95%)
                            $itemData["status"] = "match";
                            // Lấy thêm màu sắc của thằng giống nhất để hiện select
                            $itemData["colors"] = $this->productModel->getColorsByProduct($topMatch['product_id']);
                            // Gán thêm thông tin product để JS tự fill vào form ngay lập tức
                            $itemData["product"] = $topMatch;
                        } else {
                            // KỊCH BẢN 2: HIỆN GỢI Ý (80% - 94%)
                            $itemData["status"] = "confirm";
                        }
                    }
                    // KỊCH BẢN 3: MỚI HOÀN TOÀN (< 80%) -> status mặc định là "new"

                    $results[] = $itemData;
                } else {
                    $results[] = [
                        "status" => "error",
                        "message" => "AI Error: " . substr($aiResponse['message'], 0, 100)
                    ];
                }
            }

            ob_clean();
            echo json_encode(["status" => "success", "data" => $results]);
        } catch (Exception $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        exit;
    }
    /**
     * API: Lấy danh sách màu sắc của một sản phẩm (AJAX)
     */
    public function getColorsAjax()
    {
        ob_start();
        header('Content-Type: application/json');

        try {
            $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
            $colors = ($product_id > 0) ? $this->productModel->getColorsByProduct($product_id) : [];
            ob_clean();
            echo json_encode($colors);
        } catch (Exception $e) {
            ob_clean();
            echo json_encode([]);
        }
        exit;
    }
}
