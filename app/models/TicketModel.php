<?php
require_once __DIR__ . '/../../config/database.php';

class TicketModel
{
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    // ======================================================
    // CÁC HÀM LẤY DỮ LIỆU ĐỔ RA GIAO DIỆN DROPDOWN
    // ======================================================

    // Lấy danh sách nhân viên để gán vào phiếu
    public function getStaffs()
    {
        $sql = "SELECT user_id, full_name 
                FROM users 
                WHERE role = 'STAFF' AND is_deleted = false AND status = true";
        
        $result = pg_query($this->conn, $sql);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }

    // Lấy danh sách hãng (Brand)
    public function getBrands()
    {
        $sql = "SELECT category_id, category_name 
                FROM categories 
                WHERE is_deleted = false AND status = true";
        
        $result = pg_query($this->conn, $sql);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }

    // Lấy danh sách mẫu giày theo hãng
    public function getProductsByBrand($brand_id)
    {
        $sql = "SELECT product_id, product_name 
                FROM products 
                WHERE category_id = $1 AND is_deleted = false";
        
        $result = pg_query_params($this->conn, $sql, [$brand_id]);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }

    // ======================================================
    // CÁC HÀM XỬ LÝ NGHIỆP VỤ PHIẾU
    // ======================================================

    // 1. Sinh Mã Phiếu tự động (PN-XXXXXX hoặc PX-XXXXXX)
    public function generateTicketCode($type)
    {
        $prefix = ($type === 'IMPORT') ? 'PN-' : 'PX-';
        
        // Tìm ID lớn nhất hiện tại
        $sql = "SELECT COALESCE(MAX(ticket_id), 0) + 1 AS next_id FROM tickets";
        $result = pg_query($this->conn, $sql);
        $row = pg_fetch_assoc($result);
        
        // Pad 6 số (VD: PN-000001)
        return $prefix . str_pad($row['next_id'], 6, '0', STR_PAD_LEFT);
    }

    // 2. Lấy danh sách các biến thể sắp hết hàng (Tồn kho < 5) KÈM HÌNH ẢNH
    public function getLowStockSuggestions()
    {
        $sql = "SELECT pv.variant_id, c.category_name as brand, p.product_name, p.product_image, pv.color, pv.size, pv.stock
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.product_id
                JOIN categories c ON p.category_id = c.category_id
                WHERE pv.stock < 5 AND pv.is_deleted = false AND p.is_deleted = false
                ORDER BY pv.stock ASC";
        
        $result = pg_query($this->conn, $sql);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }

    // Lấy chi tiết Biến thể + Hình ảnh (Dùng cho AJAX ở màn hình Tạo Phiếu)
    public function getVariantsByProduct($product_id)
    {
        $sql = "SELECT pv.variant_id, pv.color, pv.size, pv.stock, p.product_image 
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.product_id
                WHERE pv.product_id = $1 AND pv.is_deleted = false";
        
        $result = pg_query_params($this->conn, $sql, [$product_id]);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }

    // 3. Tạo phiếu lưu vào DB (Dùng Transaction để đảm bảo an toàn)
    public function createTicket($ticket_code, $batch_code, $type, $manager_id, $staff_id, $details)
    {
        $staff_id_param = !empty($staff_id) ? $staff_id : null;
        
        // Bắt đầu Transaction
        pg_query($this->conn, "BEGIN");

        try {
            // Lưu vào bảng chính (tickets)
            $sqlMaster = "INSERT INTO tickets (ticket_code, batch_code, ticket_type, manager_id, staff_id) 
                          VALUES ($1, $2, $3, $4, $5) RETURNING ticket_id";
            $resMaster = pg_query_params($this->conn, $sqlMaster, [trim($ticket_code), trim($batch_code), $type, $manager_id, $staff_id_param]);
            
            if (!$resMaster) throw new Exception("Lỗi tạo phiếu chính");

            // Lấy ra cái ID phiếu vừa tạo
            $ticket_id = pg_fetch_result($resMaster, 0, 'ticket_id');

            // Lưu danh sách sản phẩm vào bảng chi tiết (ticket_details)
            $sqlDetail = "INSERT INTO ticket_details (ticket_id, variant_id, quantity) VALUES ($1, $2, $3)";
            foreach ($details as $item) {
                if (!empty($item['variant_id']) && !empty($item['quantity'])) {
                    $resDetail = pg_query_params($this->conn, $sqlDetail, [$ticket_id, $item['variant_id'], $item['quantity']]);
                    if (!$resDetail) throw new Exception("Lỗi tạo chi tiết phiếu");
                }
            }

            // Nếu mọi thứ trơn tru thì Commit lưu thật vào DB
            pg_query($this->conn, "COMMIT");
            return true;
        } catch (Exception $e) {
            // Có 1 lỗi nhỏ cũng Rollback xóa hết làm lại, không để sinh ra rác
            pg_query($this->conn, "ROLLBACK");
            return false;
        }
    }

    // 4. Lấy danh sách toàn bộ phiếu CÓ BỘ LỌC (Ngày & Trạng thái)
    public function getAllTickets($status = '', $start_date = '', $end_date = '')
    {
        $sql = "SELECT t.*, u.full_name as staff_name 
                FROM tickets t 
                LEFT JOIN users u ON t.staff_id = u.user_id 
                WHERE 1=1";
        
        $params = [];
        $pIdx = 1;

        if (!empty($status)) {
            $sql .= " AND t.status = $" . $pIdx++;
            $params[] = $status;
        }
        if (!empty($start_date)) {
            $sql .= " AND DATE(t.created_at) >= $" . $pIdx++;
            $params[] = $start_date;
        }
        if (!empty($end_date)) {
            $sql .= " AND DATE(t.created_at) <= $" . $pIdx++;
            $params[] = $end_date;
        }

        $sql .= " ORDER BY t.created_at DESC";
        
        $result = empty($params) ? pg_query($this->conn, $sql) : pg_query_params($this->conn, $sql, $params);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }
}