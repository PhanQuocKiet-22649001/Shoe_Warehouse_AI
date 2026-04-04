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
     * Middleware: Kiểm tra quyền Nhân viên (STAFF)
     */
    private function checkStaff()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STAFF') {
            $_SESSION['error'] = "Chỉ nhân viên (STAFF) mới có quyền xuất kho!";
            header("Location: " . $_SERVER['HTTP_REFERER']);
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
        $this->checkStaff();
        if (isset($_POST['add_product'])) {
            // --- BẮT ĐẦU: Dọn rác để trả về JSON sạch ---
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');

            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo json_encode(["status" => "error", "message" => "Hết phiên đăng nhập!"]);
                exit;
            }

            $db = $this->productModel->getConnection();
            $categoryModel = new CategoryModel();

            pg_query($db, "BEGIN");

            try {
                // --- BƯỚC 1: XỬ LÝ HÃNG (GIỮ NGUYÊN LOGIC CŨ) ---
                $brandName = isset($_POST['brand_name']) ? trim($_POST['brand_name']) : '';

                if (empty($brandName) && isset($_POST['category_id']) && intval($_POST['category_id']) > 0) {
                    $cat = $categoryModel->getById(intval($_POST['category_id']));
                    $brandName = $cat ? $cat['category_name'] : '';
                }

                if (empty($brandName)) {
                    throw new Exception("Vui lòng nhập tên Hãng sản xuất!");
                }

                $category_id = $categoryModel->getCategoryIdByName($brandName, $userId);

                if (!$category_id) {
                    throw new Exception("Không thể xác định hoặc tạo mới Hãng: " . $brandName);
                }

                // --- BƯỚC 2: LẤY THÔNG TIN CÒN LẠI (GIỮ NGUYÊN LOGIC CŨ) ---
                $product_name = trim($_POST['product_name']);
                if (empty($product_name)) throw new Exception("Vui lòng nhập tên sản phẩm!");

                $raw_color    = (isset($_POST['color']) && $_POST['color'] === 'new' && !empty($_POST['new_color'])) ? $_POST['new_color'] : $_POST['color'];
                $color        = trim($this->normalizeColor($raw_color));;
                $size         = trim($_POST['size']);
                $stock_input  = intval($_POST['stock']);
                $vector_json  = isset($_POST['vector']) ? $_POST['vector'] : null;

                // --- ĐOẠN THAY ĐỔI CÔNG THỨC SKU THEO YÊU CẦU MỚI ---
                $colorCode = $this->getColorCode($color);
                $cleanBrand = $this->removeAccents($brandName);
                $cleanName = $this->removeAccents($product_name);

                // Lấy chữ cái đầu của từng từ (Ví dụ: Nike Blazer Phantom Low -> NBPL)
                $fullName = $cleanBrand . ' ' . $cleanName;
                $words = explode(' ', $fullName);
                $initials = '';
                foreach ($words as $w) {
                    if (!empty($w)) $initials .= strtoupper(substr($w, 0, 1));
                }

                // Kết quả: 2 chữ đầu Brand - Initials - Màu - Size (Ví dụ: NI-NBPL-WHI-40)
                $sku = strtoupper(substr($brandName, 0, 2)) . "-" . $initials . "-" . $colorCode . "-" . $size;
                // --- KẾT THÚC ĐOẠN THAY ĐỔI SKU ---

                $existingProduct = $this->productModel->findExistingProduct($brandName, $product_name);

                $hanoiTime = $this->getHanoiTime();
                if ($existingProduct) {
                    $productId = $existingProduct['product_id'];
                    $existingVariant = $this->productModel->findVariant($productId, $size, $color);

                    if ($existingVariant) {
                        // NHÁNH 1: CỘNG DỒN TỒN KHO
                        $res = $this->productModel->addStock($existingVariant['variant_id'], $stock_input);
                        if (!$res) throw new Exception("Lỗi cộng dồn kho!");

                        
                        $this->productModel->logTransaction('IMPORT', $existingVariant['variant_id'], $stock_input, $userId, $hanoiTime);
                        $msg = "Sản phẩm đã có. Đã cập nhật số lượng cho hãng $brandName.";
                    } else {
                        // NHÁNH 2: TẠO BIẾN THỂ MỚI
                        $res = $this->productModel->createVariant($productId, $size, $color, $stock_input, $sku);
                        if (!$res) throw new Exception("Lỗi tạo biến thể mới! Có thể SKU $sku bị trùng.");

                        $justCreated = $this->productModel->findVariant($productId, $size, $color);
                        if ($justCreated) {
                            $this->productModel->logTransaction('IMPORT', $justCreated['variant_id'], $stock_input, $userId, $hanoiTime);
                        }
                        $msg = "Đã thêm biến thể mới cho $product_name ($brandName).";
                    }
                } else {
                    // NHÁNH 3: TẠO MỚI HOÀN TOÀN
                    $imageName = (!empty($_POST['temp_image_name']) && $_POST['temp_image_name'] !== 'undefined')
                        ? $_POST['temp_image_name']
                        : 'default_shoe.png';

                    if ($imageName !== 'default_shoe.png') {
                        $sourcePath = "assets/img_temp/" . $imageName;
                        $destinationPath = "assets/img_product/" . $imageName;
                        if (file_exists($sourcePath)) {
                            if (!rename($sourcePath, $destinationPath)) {
                                throw new Exception("Lỗi hệ thống: Không thể lưu file ảnh chính thức.");
                            }
                        }
                    }

                    $productId = $this->productModel->create($product_name, $category_id, $imageName, $userId);
                    if (!$productId) throw new Exception("Lỗi tạo sản phẩm mẫu!");

                    if ($vector_json && $vector_json !== 'undefined' && $vector_json !== 'null') {
                        $vectorArray = json_decode($vector_json, true);
                        if (is_array($vectorArray)) {
                            $this->productModel->updateImageEmbedding($productId, $vectorArray);
                        }
                    }

                    $resVar = $this->productModel->createVariant($productId, $size, $color, $stock_input, $sku);
                    if (!$resVar) throw new Exception("Lỗi tạo biến thể mẫu!");

                    $justCreated = $this->productModel->findVariant($productId, $size, $color);
                    if ($justCreated) {
                        $this->productModel->logTransaction('IMPORT', $justCreated['variant_id'], $stock_input, $userId, $hanoiTime);
                    }

                    $msg = "Khởi tạo sản phẩm mẫu AI thành công cho hãng $brandName.";
                }

                pg_query($db, "COMMIT");
                echo json_encode(["status" => "success", "message" => $msg]);
                exit;
            } catch (Exception $e) {
                pg_query($db, "ROLLBACK");
                echo json_encode(["status" => "error", "message" => $e->getMessage()]);
                exit;
            }
        }
    }

    // Bổ sung hàm dịch màu để làm SKU chuyên nghiệp hơn
    private function getColorCode($vnColor)
    {
        $map = [
            'Trắng' => 'WHI',
            'Đen' => 'BLA',
            'Đỏ' => 'RED',
            'Xanh dương' => 'BLU',
            'Xám' => 'GRE',
            'Xanh lá' => 'GRN',
            'Vàng' => 'YEL',
            'Hồng' => 'PIN',
            'Nâu' => 'BRO',
            'Cam' => 'ORA'
        ];
        return $map[$vnColor] ?? strtoupper(substr($this->removeAccents($vnColor), 0, 3));
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
            $dir = 'assets/img_temp/';
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


    /**
     * Xử lý xuất kho sản phẩm (Trừ số lượng)
     */
    public function exportStock()
    {
        $this->checkStaff();
        if (isset($_POST['export_stock'])) {
            $variant_id = $_POST['variant_id'];
            $quantity = intval($_POST['quantity']);
            $user_id = $_SESSION['user_id']; // Lấy ID của nhân viên đang đăng nhập
            $category_id = $_POST['category_id'];

            $db = $this->productModel->getConnection();
            pg_query($db, "BEGIN"); // Bắt đầu giao dịch

            try {
                // 1. Trừ số lượng tồn kho
                $res1 = $this->productModel->removeStock($variant_id, $quantity);
                if (!$res1 || pg_affected_rows($res1) == 0) throw new Exception("Không đủ tồn kho!");

                // 2. Ghi nhật ký xuất kho
                $hanoiTime = $this->getHanoiTime();
                $res2 = $this->productModel->logTransaction('EXPORT', $variant_id, $quantity, $user_id, $hanoiTime);
                if (!$res2) throw new Exception("Lỗi ghi nhật ký giao dịch!");

                pg_query($db, "COMMIT"); // Thành công hết thì lưu lại
                $_SESSION['success'] = "Xuất kho thành công và đã lưu nhật ký!";
            } catch (Exception $e) {
                pg_query($db, "ROLLBACK"); // Có lỗi thì hủy hết các lệnh trên
                $_SESSION['error'] = "Lỗi: " . $e->getMessage();
            }

            header("Location: index.php?page=products&category_id=" . $category_id);
            exit;
        }
    }


    /**
     * Bật/Tắt trạng thái biến thể (Chỉ dành cho MANAGER)
     */
    public function toggleVariantStatus()
    {
        $this->checkManager(); // Chặn STAFF bằng middleware có sẵn của bạn

        if (isset($_POST['variant_id']) && isset($_POST['current_status'])) {
            $variant_id = $_POST['variant_id'];

            // Lấy status hiện tại và đảo ngược lại
            $currentStatus = $_POST['current_status'];
            $newStatus = ($currentStatus == 1 || $currentStatus == 't') ? 'false' : 'true';

            if ($this->productModel->updateVariantStatus($variant_id, $newStatus)) {
                $_SESSION['success'] = "Đã thay đổi trạng thái biến thể!";
            } else {
                $_SESSION['error'] = "Lỗi khi cập nhật trạng thái!";
            }
        }

        // Quay lại trang trước đó (để giữ nguyên modal hoặc trang hiện tại)
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    /**
     * Xóa mềm biến thể (Chỉ dành cho MANAGER)
     */
    public function deleteVariant()
    {
        $this->checkManager(); // Chặn STAFF

        if (isset($_POST['variant_id'])) {
            $variant_id = $_POST['variant_id'];

            if ($this->productModel->softDeleteVariant($variant_id)) {
                $_SESSION['success'] = "Đã xóa biến thể thành công!";
            } else {
                $_SESSION['error'] = "Lỗi khi xóa biến thể!";
            }
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }


    private function getHanoiTime()
    {
        $url = "https://www.google.com";
        $headers = @get_headers($url, 1);

        if (isset($headers['Date'])) {
            $dateStr = is_array($headers['Date']) ? $headers['Date'][0] : $headers['Date'];

            // Tạo đối tượng thời gian từ GMT
            $date = new DateTime($dateStr, new DateTimeZone('GMT'));

            // Chuyển sang múi giờ Việt Nam (Hà Nội)
            $date->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));

            // Trả về định dạng Y-m-d H:i:s để lưu vào Postgres
            return $date->format('Y-m-d H:i:s');
        }

        // Nếu mất mạng thì lấy giờ máy (đành chịu), nhưng ép múi giờ VN
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        return date('Y-m-d H:i:s');
    }
}
