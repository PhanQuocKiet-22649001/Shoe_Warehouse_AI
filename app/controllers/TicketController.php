<?php
class TicketController {
    private $ticketModel;

    public function __construct() {
        $this->ticketModel = new TicketModel();
    }

    public function create($type) {
        $staffs = $this->ticketModel->getStaffs();
        $brands = $this->ticketModel->getBrands();
        $auto_code = $this->ticketModel->generateTicketCode($type); // Lấy mã tự sinh
        
        // Nếu là phiếu NHẬP, lấy thêm danh sách cảnh báo tồn kho < 5
        $suggestions = [];
        if ($type === 'IMPORT') {
            $suggestions = $this->ticketModel->getLowStockSuggestions();
        }

        return [
            'staffs' => $staffs, 
            'brands' => $brands, 
            'type' => $type,
            'auto_code' => $auto_code,
            'suggestions' => $suggestions
        ];
    }

    public function saveTicket() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ticket_code = $_POST['ticket_code']; // Lấy mã đã sinh (Readonly)
            $batch_code = $_POST['batch_code']; // Lô hàng (tùy chọn)
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

            if ($this->ticketModel->createTicket($ticket_code, $batch_code, $type, $manager_id, $staff_id, $details)) {
                echo "<script>alert('Tạo phiếu thành công!'); window.location.href='index.php?page=ticket_list';</script>";
            } else {
                echo "<script>alert('Lỗi tạo phiếu! Không thể lưu dữ liệu.'); history.back();</script>";
            }
        }
    }

    public function index() {
        $status = $_GET['filter_status'] ?? '';
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';

        $tickets = $this->ticketModel->getAllTickets($status, $start_date, $end_date);
        
        return [
            'tickets' => $tickets,
            'filter_status' => $status,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }

    // Cập nhật API lấy Variants để ném cả URL ảnh ra
    public function getVariantsByProductAjax() {
        $product_id = $_GET['product_id'] ?? 0;
        $variants = $this->ticketModel->getVariantsByProduct($product_id);
        echo json_encode($variants);
    }


    public function getProductsByBrandAjax() {
        $brand_id = $_GET['brand_id'] ?? 0;
        $products = $this->ticketModel->getProductsByBrand($brand_id);
        echo json_encode($products);
    }
}