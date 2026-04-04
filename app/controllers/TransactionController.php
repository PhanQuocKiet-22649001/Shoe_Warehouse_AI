<?php
// app/controllers/TransactionController.php
require_once __DIR__ . '/../models/TransactionModel.php';

class TransactionController
{
    private $model;

    public function __construct()
    {
        $this->model = new TransactionModel();
    }

    public function index()
    {
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        // ĐỔI TÊN HÀM: Từ getTransactionsByType thành getSummary
        $importHistory = $this->model->getSummary('IMPORT', $fromDate, $toDate);
        $exportHistory = $this->model->getSummary('EXPORT', $fromDate, $toDate);

        return [
            'importHistory' => $importHistory,
            'exportHistory' => $exportHistory
        ];
    }

    // HÀM MỚI: Xử lý yêu cầu lấy chi tiết khi bấm nút "Xem"
    /**
     * API: Trả về chi tiết biến thể (size, màu, số lượng) cho Modal lịch sử
     * Sử dụng AJAX để tối ưu tốc độ load trang
     */
    public function getDetailsAjax()
    {
        // 1. Tẩy tủy: Xóa sạch bộ đệm đầu ra để tránh các khoảng trắng hoặc lỗi PHP làm hỏng chuỗi JSON
        if (ob_get_length()) ob_clean();

        // 2. Thiết lập Header báo cho trình duyệt đây là dữ liệu JSON
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 3. Tiếp nhận và làm sạch tham số
            $date      = $_GET['date'] ?? null;
            $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
            $userId    = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
            $type      = isset($_GET['type']) ? strtoupper(trim($_GET['type'])) : null;

            // 4. Kiểm tra điều kiện đầu vào
            if (!$date || !$productId || !$userId || !$type) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Thiếu thông tin truy vấn chi tiết."
                ]);
                exit;
            }

            // 5. Gọi Model truy vấn dữ liệu từ PostgreSQL
            $details = $this->model->getGroupDetails($date, $productId, $userId, $type);

            // 6. Trả về kết quả (đảm bảo luôn là một mảng [] nếu không có dữ liệu)
            echo json_encode($details ?: []);
        } catch (Exception $e) {
            // 7. Xử lý lỗi ngoại lệ (ví dụ lỗi SQL) - Trả về lỗi dạng JSON để JavaScript bắt được
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Lỗi hệ thống: " . $e->getMessage()
            ]);
        }

        // 8. Chặn đứng mọi tiến trình nạp Layout/Footer phía sau của index.php
        exit;
    }
}
