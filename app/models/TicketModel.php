<?php
require_once __DIR__ . '/../../config/database.php';

class TicketModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    // ======================================================
    // CÁC HÀM LẤY DỮ LIỆU ĐỔ RA GIAO DIỆN DROPDOWN
    // ======================================================

    public function getStaffs()
    {
        $sql = "SELECT user_id, full_name 
                FROM users 
                WHERE role = 'STAFF' AND is_deleted = false AND status = true";

        $result = pg_query($this->conn, $sql);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }

    public function getBrands()
    {
        $sql = "SELECT category_id, category_name 
                FROM categories 
                WHERE is_deleted = false AND status = true";

        $result = pg_query($this->conn, $sql);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }

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

    public function generateTicketCode($type)
    {
        $typeStr = ($type === 'IMPORT') ? 'PN-' : 'PX-';
        $dateStr = date('ymd');
        $prefix = $typeStr . $dateStr . '-';

        $sql = "SELECT COALESCE(MAX(REPLACE(ticket_code, $1, '')::integer), 0) + 1 AS next_id 
                FROM tickets 
                WHERE ticket_code LIKE $2";

        $result = pg_query_params($this->conn, $sql, [$prefix, $prefix . '%']);
        $row = pg_fetch_assoc($result);

        return $prefix . str_pad($row['next_id'], 4, '0', STR_PAD_LEFT);
    }

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

    public function getVariantsByProduct($product_id, $type = '')
    {
        if ($type === 'EXPORT') {
            // BÍ KÍP Ở ĐÂY: Trả về (stock - reserved_stock) dưới "lớp vỏ" bí danh AS stock.
            // Như vậy JS ở frontend bồ vẫn gọi item.stock bình thường mà không cần sửa code.
            // AND ... > 0 để ẩn luôn các biến thể đã bị giữ chỗ hết sạch.
            $sql = "SELECT pv.variant_id, pv.color, pv.size, 
                           (pv.stock - COALESCE(pv.reserved_stock, 0)) AS stock, 
                           p.product_image 
                    FROM product_variants pv
                    JOIN products p ON pv.product_id = p.product_id
                    WHERE pv.product_id = $1 AND pv.is_deleted = false 
                      AND (pv.stock - COALESCE(pv.reserved_stock, 0)) > 0";
        } else {
            // Nếu là IMPORT (Nhập kho), cứ hiện đủ tồn kho vật lý và hiện cả những mẫu = 0
            $sql = "SELECT pv.variant_id, pv.color, pv.size, pv.stock, p.product_image 
                    FROM product_variants pv
                    JOIN products p ON pv.product_id = p.product_id
                    WHERE pv.product_id = $1 AND pv.is_deleted = false";
        }

        $result = pg_query_params($this->conn, $sql, [$product_id]);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }

    public function createTicket($ticket_code, $batch_code, $type, $manager_id, $staff_id, $details)
    {
        $staff_id_param = !empty($staff_id) ? $staff_id : null;
        pg_query($this->conn, "BEGIN");

        try {
            $sqlMaster = "INSERT INTO tickets (ticket_code, batch_code, ticket_type, manager_id, staff_id) 
                          VALUES ($1, $2, $3, $4, $5) RETURNING ticket_id";
            $resMaster = pg_query_params($this->conn, $sqlMaster, [trim($ticket_code), trim($batch_code), $type, $manager_id, $staff_id_param]);
            $ticket_id = pg_fetch_result($resMaster, 0, 'ticket_id');

            foreach ($details as $item) {
                // ==========================================
                // CHỐT CHẶN: Ép kiểm tra trước khi cho xuất
                // ==========================================
                if ($type === 'EXPORT') {
                    // Dùng FOR UPDATE để lock dòng này, ngăn 2 Manager tạo phiếu cùng 1 giây
                    $checkSql = "SELECT (stock - COALESCE(reserved_stock, 0)) AS available_stock 
                                 FROM product_variants WHERE variant_id = $1 FOR UPDATE";
                    $resCheck = pg_query_params($this->conn, $checkSql, [$item['variant_id']]);
                    $avail = pg_fetch_result($resCheck, 0, 'available_stock');

                    if ($item['quantity'] > $avail) {
                        // Ném ra mã lỗi đặc biệt
                        throw new Exception("out_of_stock");
                    }
                }

                $sqlDetail = "INSERT INTO ticket_details (ticket_id, variant_id, quantity) VALUES ($1, $2, $3)";
                pg_query_params($this->conn, $sqlDetail, [$ticket_id, $item['variant_id'], $item['quantity']]);

                if ($type === 'EXPORT') {
                    $sqlReserve = "UPDATE product_variants SET reserved_stock = reserved_stock + $1 WHERE variant_id = $2";
                    pg_query_params($this->conn, $sqlReserve, [$item['quantity'], $item['variant_id']]);
                }
            }

            pg_query($this->conn, "COMMIT");
            return true;
        } catch (Exception $e) {
            pg_query($this->conn, "ROLLBACK");
            // Gửi "tín hiệu" về cho Controller
            if ($e->getMessage() === 'out_of_stock') {
                return 'out_of_stock';
            }
            return false;
        }
    }

    public function getAllTickets($type, $status = '', $start_date = '', $end_date = '')
    {
        $sql = "SELECT t.*, u.full_name as staff_name 
                FROM tickets t 
                LEFT JOIN users u ON t.staff_id = u.user_id 
                WHERE t.ticket_type = $1 AND t.is_deleted = false";

        $params = [$type];
        $pIdx = 2;

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

        $result = pg_query_params($this->conn, $sql, $params);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }

    public function updateStaffInTicket($ticket_id, $new_staff_id)
    {
        // BỔ SUNG: Lấy ID nhân viên hiện tại trước khi ghi đè người mới
        $sqlGet = "SELECT staff_id FROM tickets WHERE ticket_id = $1";
        $resGet = pg_query_params($this->conn, $sqlGet, [$ticket_id]);
        $old_staff_id = pg_fetch_result($resGet, 0, 'staff_id');

        $sql = "UPDATE tickets 
            SET staff_id = $1 
            WHERE ticket_id = $2 AND status = 'PENDING' AND is_deleted = false";
        $result = pg_query_params($this->conn, $sql, [$new_staff_id, $ticket_id]);

        // TRẢ VỀ ID CŨ thay vì true
        return $result ? $old_staff_id : false;
    }

    // ĐÃ NÂNG CẤP: Giải phóng reserved_stock khi Hủy Phiếu
    public function softDeleteTicket($ticket_id)
    {
        pg_query($this->conn, "BEGIN");
        try {
            // BỔ SUNG: Lấy thêm staff_id để trả về cho Controller bắn tín hiệu Pusher
            $checkSql = "SELECT staff_id, ticket_type FROM tickets WHERE ticket_id = $1 AND status = 'PENDING' AND is_deleted = false";
            $resCheck = pg_query_params($this->conn, $checkSql, [$ticket_id]);

            if ($row = pg_fetch_assoc($resCheck)) {
                $staff_id = $row['staff_id']; // Lưu lại ID nhân viên đang giữ phiếu

                // Kiểm tra xem đây có phải phiếu EXPORT không, nếu phải thì hoàn trả giữ chỗ
                if ($row['ticket_type'] === 'EXPORT') {
                    $details = $this->getTicketDetails($ticket_id);
                    foreach ($details as $item) {
                        pg_query_params($this->conn, "UPDATE product_variants SET reserved_stock = reserved_stock - $1 WHERE variant_id = $2", [$item['quantity'], $item['variant_id']]);
                    }
                }

                // Tiến hành xóa mềm
                $sql = "UPDATE tickets SET is_deleted = true WHERE ticket_id = $1 AND status = 'PENDING'";
                $result = pg_query_params($this->conn, $sql, [$ticket_id]);
                $affected = pg_affected_rows($result);

                pg_query($this->conn, "COMMIT");

                // SỬA LẠI: Nếu xóa thành công (affected > 0), trả về staff_id thay vì true
                // Nếu xóa thất bại trả về false
                return ($result && $affected > 0) ? $staff_id : false;
            }

            pg_query($this->conn, "ROLLBACK");
            return false;
        } catch (Exception $e) {
            pg_query($this->conn, "ROLLBACK");
            return false;
        }
    }

    // ĐÃ NÂNG CẤP: Lấy thêm product_id và mảng other_variants
    public function getTicketDetails($ticket_id)
    {
        $sql = "SELECT 
                    td.detail_id,       
                    td.variant_id,      
                    td.quantity, 
                    COALESCE(td.processed_qty, 0) as processed_qty, 
                    pv.color, 
                    pv.size, 
                    p.product_id,       -- Bổ sung để truy tìm anh em
                    p.product_name, 
                    p.product_image, 
                    c.category_name as brand
                FROM ticket_details td
                JOIN product_variants pv ON td.variant_id = pv.variant_id
                JOIN products p ON pv.product_id = p.product_id
                JOIN categories c ON p.category_id = c.category_id
                WHERE td.ticket_id = $1";

        $result = pg_query_params($this->conn, $sql, [$ticket_id]);
        $items = $result ? (pg_fetch_all($result) ?: []) : [];

        // Lấy thêm các biến thể khác cùng mẫu ĐANG CÓ TRONG PHIẾU NÀY
        foreach ($items as &$item) {
            $sqlOthers = "SELECT pv.size, pv.color, td.quantity 
                          FROM ticket_details td 
                          JOIN product_variants pv ON td.variant_id = pv.variant_id
                          WHERE td.ticket_id = $1 AND pv.product_id = $2 AND pv.variant_id != $3";
            $resOthers = pg_query_params($this->conn, $sqlOthers, [$ticket_id, $item['product_id'], $item['variant_id']]);
            $item['other_variants'] = $resOthers ? (pg_fetch_all($resOthers) ?: []) : [];
        }

        return $items;
    }

    //đếm số lượng phiếu bắn thông báo
    public function getPendingCounts($staff_id)
    {
        $sql = "SELECT ticket_type, COUNT(*) as total 
            FROM tickets 
            WHERE staff_id = $1 AND status IN ('PENDING', 'PAUSED', 'PROCESSING') AND is_deleted = false 
            GROUP BY ticket_type";

        $result = pg_query_params($this->conn, $sql, [$staff_id]);
        $counts = ['IMPORT' => 0, 'EXPORT' => 0];

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $counts[$row['ticket_type']] = (int)$row['total'];
            }
        }
        return $counts;
    }

    // ======================================================
    // CÁC HÀM HỖ TRỢ XUẤT KHO THỦ CÔNG 
    // ======================================================

    public function getMyExports($staff_id)
    {
        $sql = "SELECT ticket_id, ticket_code, status, created_at 
                FROM tickets 
                WHERE staff_id = $1 AND ticket_type = 'EXPORT' AND status IN ('PENDING', 'PROCESSING', 'PAUSED') AND is_deleted = false 
                ORDER BY created_at DESC";
        $res = pg_query_params($this->conn, $sql, [$staff_id]);
        return $res ? (pg_fetch_all($res) ?: []) : [];
    }

    public function updateTicketStatus($ticket_id, $status)
    {
        $sql = "UPDATE tickets SET status = $1 WHERE ticket_id = $2";
        return pg_query_params($this->conn, $sql, [$status, $ticket_id]);
    }

    public function updateExportProgress($detail_id, $picked_qty)
    {
        $sql = "UPDATE ticket_details SET processed_qty = processed_qty + $1 WHERE detail_id = $2";
        return pg_query_params($this->conn, $sql, [$picked_qty, $detail_id]);
    }

    public function getLocationsFromJSON($variant_id)
    {
        $sql = "
            SELECT 
                s.shelf_name, 
                (tier.key || '-' || slot.key) AS slot_code, 
                COUNT(item.value)::int AS qty_in_slot
            FROM 
                public.shelves s,
                jsonb_each(s.layout) AS tier,         
                jsonb_each(tier.value) AS slot,       
                jsonb_array_elements_text(slot.value) AS item 
            WHERE 
                item.value = $1::text                 
            GROUP BY 
                s.shelf_name, tier.key, slot.key
            HAVING 
                COUNT(item.value) > 0
        ";

        $res = pg_query_params($this->conn, $sql, [$variant_id]);
        return $res ? (pg_fetch_all($res) ?: []) : [];
    }

    // BỔ SUNG MỚI: Hàm hoàn tất phiếu dứt điểm tồn kho
    public function completeExportTicket($ticket_id)
    {
        pg_query($this->conn, "BEGIN");
        try {
            $details = $this->getTicketDetails($ticket_id);
            foreach ($details as $item) {
                $qty = (int)$item['quantity'];
                $v_id = $item['variant_id'];

                // Trừ stock thật và Xả reserved_stock
                $sqlStock = "UPDATE product_variants SET stock = stock - $1, reserved_stock = reserved_stock - $1 WHERE variant_id = $2";
                pg_query_params($this->conn, $sqlStock, [$qty, $v_id]);

                // Xóa khỏi JSONB kệ
                $this->removeItemsFromShelves($v_id, $qty);
            }

            // Đóng phiếu
            pg_query_params($this->conn, "UPDATE tickets SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP WHERE ticket_id = $1", [$ticket_id]);

            pg_query($this->conn, "COMMIT");
            return true;
        } catch (Exception $e) {
            pg_query($this->conn, "ROLLBACK");
            return false;
        }
    }

    // BỔ SUNG MỚI: Hàm helper bóc tách mảng JSONB
    private function removeItemsFromShelves($variant_id, $total_to_remove)
    {
        $sql = "SELECT shelf_id, layout FROM shelves WHERE layout::text LIKE '%" . $variant_id . "%'";
        $res = pg_query($this->conn, $sql);

        while ($row = pg_fetch_assoc($res)) {
            if ($total_to_remove <= 0) break;
            $layout = json_decode($row['layout'], true);
            $changed = false;

            foreach ($layout as $tier => &$slots) {
                foreach ($slots as $slot_code => &$items) {
                    if ($total_to_remove <= 0) break;
                    foreach ($items as $key => $val) {
                        if ($val == $variant_id && $total_to_remove > 0) {
                            unset($items[$key]);
                            $items = array_values($items); // Reset lại index mảng
                            $total_to_remove--;
                            $changed = true;
                        }
                    }
                }
            }
            if ($changed) {
                pg_query_params($this->conn, "UPDATE shelves SET layout = $1 WHERE shelf_id = $2", [json_encode($layout), $row['shelf_id']]);
            }
        }
    }


    //lấy danh sách phiếu của riêng nhân viên đang đăng nhập, có hỗ trợ truy vấn theo bộ lọc
    public function getStaffTickets($staff_id, $status = '', $start_date = '', $end_date = '')
    {
        $sql = "SELECT * FROM tickets WHERE staff_id = $1 AND is_deleted = false";
        $params = [$staff_id];
        $pIdx = 2;

        if (!empty($status)) {
            $sql .= " AND status = $" . $pIdx++;
            $params[] = $status;
        }
        if (!empty($start_date)) {
            $sql .= " AND DATE(created_at) >= $" . $pIdx++;
            $params[] = $start_date;
        }
        if (!empty($end_date)) {
            $sql .= " AND DATE(created_at) <= $" . $pIdx++;
            $params[] = $end_date;
        }

        $sql .= " ORDER BY created_at DESC";

        $result = pg_query_params($this->conn, $sql, $params);
        return $result ? (pg_fetch_all($result) ?: []) : [];
    }



    // ======================================================
    // CÁC HÀM XỬ LÝ NHẬP KHO AI (BẢNG TẠM)
    // ======================================================

    public function getMyImports($staff_id)
    {
        $sql = "SELECT ticket_id, ticket_code, status, created_at 
                FROM tickets 
                WHERE staff_id = $1 AND ticket_type = 'IMPORT' AND status IN ('PENDING', 'PROCESSING', 'PAUSED') AND is_deleted = false 
                ORDER BY created_at DESC";

        $res = pg_query_params($this->conn, $sql, [$staff_id]);
        return $res ? (pg_fetch_all($res) ?: []) : [];
    }

    public function saveTempImport($ticket_id, $detail_id, $variant_id, $actual_qty, $image, $status, $disc_type, $note, $staff_id)
    {
        pg_query($this->conn, "BEGIN");
        try {
            // Lấy expected_qty từ phiếu gốc
            $sqlGetQty = "SELECT quantity FROM ticket_details WHERE detail_id = $1";
            $resQty = pg_query_params($this->conn, $sqlGetQty, [$detail_id]);
            $expected_qty = pg_fetch_result($resQty, 0, 'quantity');

            // Lưu vào bảng tạm ticket_import_temp
            $sqlTemp = "INSERT INTO ticket_import_temp 
                        (ticket_id, variant_id, expected_qty, actual_qty, scanned_image, status, discrepancy_type, note, staff_id) 
                        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
            pg_query_params($this->conn, $sqlTemp, [
                $ticket_id,
                $variant_id,
                $expected_qty,
                $actual_qty,
                $image,
                $status,
                $disc_type,
                $note,
                $staff_id
            ]);

            // Cập nhật CỘNG DỒN processed_qty ở ticket_details để thanh tiến trình chạy
            $sqlUpdateDetail = "UPDATE ticket_details SET processed_qty = COALESCE(processed_qty, 0) + $1 WHERE detail_id = $2";
            pg_query_params($this->conn, $sqlUpdateDetail, [$actual_qty, $detail_id]);

            pg_query($this->conn, "COMMIT");
            return true;
        } catch (Exception $e) {
            pg_query($this->conn, "ROLLBACK");
            return $e->getMessage();
        }
    }

    public function completeImportTicket($ticket_id, $user_id)
    {
        pg_query($this->conn, "BEGIN");
        try {
            // 1. Đọc dữ liệu từ bảng tạm để cộng vô kho thực tế
            $sqlTemp = "SELECT variant_id, SUM(actual_qty) as total_act FROM ticket_import_temp WHERE ticket_id = $1 GROUP BY variant_id";
            $resTemp = pg_query_params($this->conn, $sqlTemp, [$ticket_id]);

            if ($resTemp) {
                while ($row = pg_fetch_assoc($resTemp)) {
                    $v_id = $row['variant_id'];
                    $qty = $row['total_act'];

                    // Cập nhật tồn kho bảng product_variants
                    pg_query_params($this->conn, "UPDATE product_variants SET stock = stock + $1 WHERE variant_id = $2", [$qty, $v_id]);

                    // Ghi log Transaction
                    pg_query_params($this->conn, "INSERT INTO transactions (transaction_type, variant_id, quantity, user_id, reference_id) VALUES ('IMPORT', $1, $2, $3, $4)", [$v_id, $qty, $user_id, "TICKET-$ticket_id"]);
                }
            }

            // 2. Chốt phiếu và xóa bảng tạm
            pg_query_params($this->conn, "UPDATE tickets SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP WHERE ticket_id = $1", [$ticket_id]);
            pg_query_params($this->conn, "DELETE FROM ticket_import_temp WHERE ticket_id = $1", [$ticket_id]);

            pg_query($this->conn, "COMMIT");
            return true;
        } catch (Exception $e) {
            pg_query($this->conn, "ROLLBACK");
            return false;
        }
    }


    // 1. Khởi tạo bảng tạm khi nhấn "Bắt đầu"
    public function startImportProcess($ticket_id)
    {
        pg_query($this->conn, "BEGIN");
        try {
            // Đổi trạng thái phiếu chính
            pg_query_params($this->conn, "UPDATE tickets SET status = 'PROCESSING' WHERE ticket_id = $1", [$ticket_id]);

            // Đổ dữ liệu dự kiến vào bảng temp (Dùng ON CONFLICT để nếu bấm lại không bị lỗi trùng)
            $sql = "INSERT INTO ticket_import_temp (ticket_id, variant_id, expected_qty, actual_qty)
                SELECT ticket_id, variant_id, quantity, 0 
                FROM ticket_details WHERE ticket_id = $1
                ON CONFLICT (ticket_id, variant_id) DO NOTHING";
            pg_query_params($this->conn, $sql, [$ticket_id]);

            pg_query($this->conn, "COMMIT");
            return true;
        } catch (Exception $e) {
            pg_query($this->conn, "ROLLBACK");
            return false;
        }
    }

    // 2. AI quét trúng -> Cập nhật actual_qty (Chỉ update nếu variant_id có trong phiếu)
    public function updateImportTemp($ticket_id, $variant_id, $image_url)
    {
        $sql = "UPDATE ticket_import_temp 
            SET actual_qty = actual_qty + 1, scanned_image = $3
            WHERE ticket_id = $1 AND variant_id = $2 
            RETURNING actual_qty";
        $res = pg_query_params($this->conn, $sql, [$ticket_id, $variant_id, $image_url]);
        return $res ? pg_fetch_assoc($res) : false; // Trả về false nếu hàng không có trong phiếu
    }

    // 3. Nhấn X -> Trừ bớt 1 số lượng trong bảng tạm
    public function decreaseImportTemp($ticket_id, $variant_id)
    {
        $sql = "UPDATE ticket_import_temp 
            SET actual_qty = GREATEST(actual_qty - 1, 0)
            WHERE ticket_id = $1 AND variant_id = $2";
        return pg_query_params($this->conn, $sql, [$ticket_id, $variant_id]);
    }

    // 4. Chốt phiếu (Xóa bảng tạm, cập nhật tồn kho thật)
    public function finalizeImport($ticket_id, $user_id)
    {
        pg_query($this->conn, "BEGIN");
        try {
            $res = pg_query_params($this->conn, "SELECT variant_id, actual_qty FROM ticket_import_temp WHERE ticket_id = $1", [$ticket_id]);
            while ($row = pg_fetch_assoc($res)) {
                // Cộng kho thật
                pg_query_params($this->conn, "UPDATE product_variants SET stock = stock + $1 WHERE variant_id = $2", [$row['actual_qty'], $row['variant_id']]);
                // Cập nhật số lượng thực tế vào phiếu chính
                pg_query_params($this->conn, "UPDATE ticket_details SET processed_qty = $1 WHERE ticket_id = $2 AND variant_id = $3", [$row['actual_qty'], $ticket_id, $row['variant_id']]);
            }
            pg_query_params($this->conn, "UPDATE tickets SET status = 'COMPLETE', completed_at = CURRENT_TIMESTAMP WHERE ticket_id = $1", [$ticket_id]);
            pg_query_params($this->conn, "DELETE FROM ticket_import_temp WHERE ticket_id = $1", [$ticket_id]);
            pg_query($this->conn, "COMMIT");
            return true;
        } catch (Exception $e) {
            pg_query($this->conn, "ROLLBACK");
            return false;
        }
    }
}
