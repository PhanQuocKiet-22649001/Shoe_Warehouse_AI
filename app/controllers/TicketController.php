<?php
// app/controllers/TicketController.php
require_once __DIR__ . '/../models/TicketModel.php';

class TicketController
{
    private $model;

    public function __construct()
    {
        $this->model = new TicketModel();
    }

    public function create($type)
    {
        $staffs = $this->model->getStaffs();
        $brands = $this->model->getBrands();
        $auto_code = $this->model->generateTicketCode($type);

        $suggestions = [];
        if ($type === 'IMPORT') {
            $suggestions = $this->model->getLowStockSuggestions();
        }

        // Lọc lịch sử
        $status = $_GET['filter_status'] ?? '';
        $start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

        $tickets = $this->model->getAllTickets($type, $status, $start_date, $end_date);

        return [
            'staffs' => $staffs,
            'brands' => $brands,
            'type' => $type,
            'auto_code' => $auto_code,
            'suggestions' => $suggestions,
            'tickets' => $tickets,
            'filter_status' => $status,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }

    public function saveTicket()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ticket_code = $_POST['ticket_code'];
            $batch_code = $_POST['batch_code'];
            $type = $_POST['ticket_type'];
            $staff_id = $_POST['staff_id'];
            $manager_id = $_SESSION['user_id'];

            $details = [];
            if (isset($_POST['variant_id']) && is_array($_POST['variant_id'])) {
                for ($i = 0; $i < count($_POST['variant_id']); $i++) {
                    $details[] = [
                        'variant_id' => $_POST['variant_id'][$i],
                        'quantity' => $_POST['quantity'][$i]
                    ];
                }
            }

            if ($this->model->createTicket($ticket_code, $batch_code, $type, $manager_id, $staff_id, $details)) {
                // Chuyển hướng kèm theo biến msg
                header("Location: index.php?page=ticket_create&type={$type}&msg=create_success");
                exit;
            } else {
                header("Location: index.php?page=ticket_create&type={$type}&msg=error");
                exit;
            }
        }
    }

    public function reassignStaff()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ticket_id = $_POST['ticket_id'];
            $new_staff_id = $_POST['new_staff_id'];
            $return_type = $_POST['return_type'];

            if ($this->model->updateStaffInTicket($ticket_id, $new_staff_id)) {
                header("Location: index.php?page=ticket_create&type={$return_type}&msg=reassign_success");
                exit;
            } else {
                header("Location: index.php?page=ticket_create&type={$return_type}&msg=error");
                exit;
            }
        }
    }

    public function deleteTicket()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ticket_id = $_POST['ticket_id'];
            $return_type = $_POST['return_type'];

            if ($this->model->softDeleteTicket($ticket_id)) {
                header("Location: index.php?page=ticket_create&type={$return_type}&msg=delete_success");
                exit;
            } else {
                header("Location: index.php?page=ticket_create&type={$return_type}&msg=error");
                exit;
            }
        }
    }

    // ======================================================
    // CÁC HÀM AJAX (TẨY TỦY BỘ ĐỆM VÀ BẮT LỖI JSON)
    // ======================================================

    public function getProductsByBrandAjax()
    {
        // 1. Tẩy tủy: Xóa sạch bộ đệm đầu ra
        if (ob_get_length()) ob_clean();

        // 2. Thiết lập Header JSON
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 3. Tiếp nhận tham số
            $brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;

            if (!$brand_id) {
                echo json_encode(["status" => "error", "message" => "Thiếu mã hãng."]);
                exit;
            }

            // 4. Truy vấn dữ liệu
            $products = $this->model->getProductsByBrand($brand_id);

            // 5. Trả về kết quả
            echo json_encode($products ?: []);
        } catch (Exception $e) {
            // 6. Bắt lỗi ngoại lệ
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống: " . $e->getMessage()]);
        }

        // 7. Chặn luồng chạy dư thừa
        exit;
    }

    public function getVariantsByProductAjax()
    {
        // 1. Tẩy tủy: Xóa sạch bộ đệm đầu ra
        if (ob_get_length()) ob_clean();

        // 2. Thiết lập Header JSON
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 3. Tiếp nhận tham số
            $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

            if (!$product_id) {
                echo json_encode(["status" => "error", "message" => "Thiếu mã sản phẩm."]);
                exit;
            }

            // 4. Truy vấn dữ liệu
            $variants = $this->model->getVariantsByProduct($product_id);

            // 5. Trả về kết quả
            echo json_encode($variants ?: []);
        } catch (Exception $e) {
            // 6. Bắt lỗi ngoại lệ
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống: " . $e->getMessage()]);
        }

        // 7. Chặn luồng chạy dư thừa
        exit;
    }



    // LẤY THÔNG TIN CHI TIẾT 1 PHIẾU
    public function getTicketDetailsAjax()
    {
        // 1. Tẩy tủy: Xóa sạch bộ đệm đầu ra
        if (ob_get_length()) ob_clean();

        // 2. Thiết lập Header JSON
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 3. Tiếp nhận tham số
            $ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

            if (!$ticket_id) {
                echo json_encode(["status" => "error", "message" => "Thiếu mã phiếu."]);
                exit;
            }

            // 4. Truy vấn dữ liệu từ Model
            $details = $this->model->getTicketDetails($ticket_id);

            // 5. Trả về kết quả
            echo json_encode($details ?: []);
        } catch (Exception $e) {
            // 6. Bắt lỗi ngoại lệ
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống: " . $e->getMessage()]);
        }
        exit;
    }
}
