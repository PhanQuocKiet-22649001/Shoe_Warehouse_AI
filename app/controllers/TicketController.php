<?php
// Thêm autoload vào đầu file controller để gọi thư viện Pusher
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../models/TicketModel.php';

class TicketController
{
    private $model;

    /**
     * Hàm khởi tạo: Tự động gọi TicketModel để tương tác với Database
     */
    public function __construct()
    {
        $this->model = new TicketModel();
        date_default_timezone_set('Asia/Ho_Chi_Minh');
    }

    // ======================================================
    // 0. CÁC HÀM GỌI BỘ ĐÀM (PUSHER) - REALTIME
    // ======================================================

    /**
     * Chức năng: Gửi thông báo Real-time cho một nhân viên cụ thể.
     * Tác dụng: Dùng khi Manager vừa tạo xong phiếu mới và giao việc cho Staff.
     */
    private function triggerPusherNotification($staff_id, $message)
    {
        try {
            $options = array(
                'cluster' => 'ap1',
                'useTLS' => true
            );

            $pusher = new Pusher\Pusher(
                '24a79cb74cfa666e1831',
                '4cb0f10dc4e59d30d062',
                '2150978',
                $options
            );

            // Phát sóng vào kênh 'warehouse-channel', đích danh nhân viên được giao việc
            $pusher->trigger('warehouse-channel', 'new-ticket-' . $staff_id, [
                'message' => $message
            ]);
        } catch (Exception $e) {
            // Bắt lỗi ngầm để web PHP vẫn chạy tạo phiếu bình thường dù rớt mạng
            error_log("Lỗi gửi Pusher: " . $e->getMessage());
        }
    }

    /**
     * Chức năng: Bắn tín hiệu đồng bộ trạng thái phiếu về cho màn hình của Manager.
     * Tác dụng: Cập nhật màu sắc (Pending, Processing, Paused) và thời gian hoàn thành (Completed) mà không cần F5.
     */
    private function triggerManagerSync($ticket_id, $status, $completed_at = null)
    {
        try {
            $options = ['cluster' => 'ap1', 'useTLS' => true];
            $pusher = new Pusher\Pusher('24a79cb74cfa666e1831', '4cb0f10dc4e59d30d062', '2150978', $options);

            $payload = [
                'ticket_id' => $ticket_id,
                'status' => $status
            ];

            // Nếu có thời gian hoàn thành thì nhét thêm vào gói tin
            if ($completed_at) {
                $payload['completed_at'] = $completed_at;
            }

            $pusher->trigger('warehouse-channel', 'ticket-status-changed', $payload);
        } catch (Exception $e) {
            error_log("Lỗi gửi Pusher Sync: " . $e->getMessage());
        }
    }


    /**
     * Chức năng: Bắn tín hiệu ngầm yêu cầu trình duyệt nhân viên tải lại số lượng Badge.
     * Tác dụng: Dùng khi nhân viên đó bị thu hồi phiếu hoặc phiếu bị xóa.
     */
    private function triggerPusherSilentUpdate($staff_id)
    {
        try {
            if (!$staff_id) return;
            $options = ['cluster' => 'ap1', 'useTLS' => true];
            $pusher = new Pusher\Pusher('24a79cb74cfa666e1831', '4cb0f10dc4e59d30d062', '2150978', $options);

            // Bắn tín hiệu refresh-badge đích danh cho staff_id
            $pusher->trigger('warehouse-channel', 'refresh-badge-' . $staff_id, ['refresh' => true]);
        } catch (Exception $e) {
        }
    }
    // ======================================================
    // 1. CÁC HÀM XỬ LÝ GIAO DIỆN & SUBMIT FORM (TRUYỀN THỐNG)
    // ======================================================

    /**
     * Chức năng: Chuẩn bị dữ liệu để hiển thị trang Tạo Phiếu.
     * Tác dụng: Load danh sách nhân viên, hãng, mã phiếu tự sinh, lịch sử phiếu và bộ lọc.
     */
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

    /**
     * Chức năng: Xử lý lưu phiếu mới vào Database khi Manager nhấn Submit.
     * Tác dụng: Validate dữ liệu, lưu DB, thực hiện giữ chỗ (reserved_stock), và điều hướng báo lỗi/thành công.
     */
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

            // Nhận kết quả từ Model
            $result = $this->model->createTicket($ticket_code, $batch_code, $type, $manager_id, $staff_id, $details);

            if ($result === true) {
                // Thành công: Bắn thông báo cho Staff và quay về trang
                $loaiPhieu = ($type === 'IMPORT') ? "Nhập kho" : "Xuất kho";
                $this->triggerPusherNotification($staff_id, "Quản lý vừa giao cho bạn 1 phiếu $loaiPhieu mới!");
                header("Location: index.php?page=ticket_create&type={$type}&msg=create_success");
                exit;
            } elseif ($result === 'out_of_stock') {
                // LỖI: Không đủ hàng khả dụng
                header("Location: index.php?page=ticket_create&type={$type}&msg=out_of_stock");
                exit;
            } else {
                // Lỗi hệ thống khác
                header("Location: index.php?page=ticket_create&type={$type}&msg=error");
                exit;
            }
        }
    }

    /**
     * Chức năng: Đổi nhân viên phụ trách cho một phiếu đang chờ.
     * Tác dụng: Cập nhật DB, báo cho NV mới (Alert) và báo cho NV cũ trừ số (Silent).
     */
    public function reassignStaff()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ticket_id = $_POST['ticket_id'];
            $new_staff_id = $_POST['new_staff_id'];
            $return_type = $_POST['return_type'];

            // Model trả về ID nhân viên cũ (NV1) nếu thành công
            $old_staff_id = $this->model->updateStaffInTicket($ticket_id, $new_staff_id);

            if ($old_staff_id !== false) {
                // 1. Báo cho người mới (Hiện thông báo Alert)
                $this->triggerPusherNotification($new_staff_id, "Bạn vừa được bàn giao xử lý 1 phiếu kho mới!");

                // 2. Báo cho người cũ cập nhật lại số Badge (Âm thầm refresh, không hiện Alert)
                if ($old_staff_id && $old_staff_id != $new_staff_id) {
                    $this->triggerPusherSilentUpdate($old_staff_id);
                }

                header("Location: index.php?page=ticket_create&type={$return_type}&msg=reassign_success");
                exit;
            } else {
                header("Location: index.php?page=ticket_create&type={$return_type}&msg=error");
                exit;
            }
        }
    }

    /**
     * Chức năng: Xóa (Hủy) một phiếu đang ở trạng thái PENDING.
     * Tác dụng: Xóa phiếu trong DB, xả hàng giữ chỗ, và báo NV đang giữ phiếu cập nhật lại số Badge.
     */
    public function deleteTicket()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ticket_id = $_POST['ticket_id'];
            $return_type = $_POST['return_type'];

            // Model trả về ID nhân viên đang phụ trách phiếu đó trước khi bị xóa
            $staff_id_to_refresh = $this->model->softDeleteTicket($ticket_id);

            if ($staff_id_to_refresh !== false) {
                // Bắn tín hiệu Silent Update để NV đó tự động trừ số trên Badge vì phiếu đã bay màu
                if (is_numeric($staff_id_to_refresh)) {
                    $this->triggerPusherSilentUpdate($staff_id_to_refresh);
                }

                header("Location: index.php?page=ticket_create&type={$return_type}&msg=delete_success");
                exit;
            } else {
                header("Location: index.php?page=ticket_create&type={$return_type}&msg=error");
                exit;
            }
        }
    }


    // ======================================================
    // 2. CÁC HÀM AJAX (PHẢN HỒI ĐỊNH DẠNG JSON CHO JAVASCRIPT)
    // ======================================================

    /**
     * Chức năng: Lấy danh sách mẫu giày dựa theo ID Hãng (Brand).
     * Tác dụng: Đổ dữ liệu vào Dropdown thứ 2 khi người dùng chọn Hãng ở form tạo phiếu.
     */
    public function getProductsByBrandAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
            if (!$brand_id) {
                echo json_encode(["status" => "error", "message" => "Thiếu mã hãng."]);
                exit;
            }

            $products = $this->model->getProductsByBrand($brand_id);
            echo json_encode($products ?: []);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống: " . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Chức năng: Lấy danh sách Biến thể (Màu, Size) dựa theo ID Mẫu giày.
     * Tác dụng: Đổ dữ liệu vào Dropdown thứ 3. Nếu là phiếu XUẤT, tự động tính Tồn Khả Dụng (Stock - Reserved).
     */
    public function getVariantsByProductAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
            $type = isset($_GET['type']) ? $_GET['type'] : '';

            if (!$product_id) {
                echo json_encode(["status" => "error", "message" => "Thiếu mã sản phẩm."]);
                exit;
            }

            $variants = $this->model->getVariantsByProduct($product_id, $type);
            echo json_encode($variants ?: []);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống: " . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Chức năng: Lấy chi tiết toàn bộ sản phẩm bên trong 1 phiếu cụ thể.
     * Tác dụng: Dùng để hiển thị Modal Xem Chi Tiết cho Manager hoặc form Xuất Kho cho Staff.
     */
    public function getTicketDetailsAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

            if (!$ticket_id) {
                echo json_encode(["status" => "error", "message" => "Thiếu mã phiếu."]);
                exit;
            }

            $details = $this->model->getTicketDetails($ticket_id);
            echo json_encode($details ?: []);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống: " . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Chức năng: Đếm số lượng phiếu chưa hoàn thành của nhân viên.
     * Tác dụng: Hiển thị cục màu đỏ (Badge) thông báo trên thanh Topbar của nhân viên.
     */
    public function getPendingCountsAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $staff_id = $_SESSION['user_id'] ?? 0;
        if (!$staff_id) {
            echo json_encode(['import' => 0, 'export' => 0]);
            exit;
        }

        $counts = $this->model->getPendingCounts($staff_id);
        echo json_encode([
            'import' => $counts['IMPORT'] ?? 0,
            'export' => $counts['EXPORT'] ?? 0
        ]);
        exit;
    }

    /**
     * Chức năng: Lấy danh sách các phiếu xuất kho đang giao cho nhân viên hiện tại.
     * Tác dụng: Hiển thị danh sách phiếu (Cột bên trái) ở màn hình thao tác Xuất Kho của Staff.
     */
    public function getMyExportsAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $staff_id = $_SESSION['user_id'];
        $data = $this->model->getMyExports($staff_id);
        echo json_encode($data);
        exit;
    }

    /**
     * Chức năng: Thay đổi trạng thái của phiếu (Ví dụ: Từ PENDING sang PROCESSING).
     * Tác dụng: Lưu Database và gọi hàm đồng bộ Real-time về màn hình Manager ngay lập tức.
     */
    public function updateStatusAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $ticket_id = $_REQUEST['ticket_id'];
        $status = $_REQUEST['status'];

        $this->model->updateTicketStatus($ticket_id, $status);
        $this->triggerManagerSync($ticket_id, $status);

        echo json_encode(['status' => 'success']);
        exit;
    }

    /**
     * Chức năng: Cập nhật tiến độ xuất kho.
     * Tác dụng: Cộng dồn số lượng đôi giày mà nhân viên đã xác nhận nhặt được vào Database.
     */
    public function updateExportProgressAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $detail_id = $_POST['detail_id'];
        $picked_qty = (int)$_POST['picked_qty'];

        if ($this->model->updateExportProgress($detail_id, $picked_qty)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật CSDL']);
        }
        exit;
    }

    /**
     * Chức năng: Mổ xẻ JSONB để tìm vị trí đôi giày.
     * Tác dụng: Cung cấp thông tin Kệ và Ô để hiển thị lộ trình đi nhặt hàng cho Staff.
     */
    public function getLocationsAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $variant_id = $_GET['variant_id'];
        $data = $this->model->getLocationsFromJSON($variant_id);
        echo json_encode($data);
        exit;
    }

    /**
     * Chức năng: Chốt sổ phiếu Xuất Kho (Nút Xác Nhận Hoàn Tất).
     * Tác dụng: Khởi chạy Transaction trừ tồn kho thật, xả giữ chỗ, xóa giày trên JSONB kệ, và thông báo về Manager.
     */
    public function completeExportAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $ticket_id = $_REQUEST['ticket_id'] ?? 0;

        if ($ticket_id > 0) {
            if ($this->model->completeExportTicket($ticket_id)) {
                // Lấy giờ hệ thống hiện tại để bắn cho Manager
                $completed_at = date('d/m/Y H:i');
                $this->triggerManagerSync($ticket_id, 'COMPLETED', $completed_at);

                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi trừ kho thực tế.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Mã phiếu không hợp lệ.']);
        }
        exit;
    }
}
