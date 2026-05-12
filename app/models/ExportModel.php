<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/TicketModel.php'; 

class ExportModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    /**
     * Chức năng: Tìm danh sách các phiếu EXPORT chưa xử lý.
     * Tác dụng: Render ra giao diện xuất thủ công của riêng tài khoản.
     */
    public function getMyExports($staff_id)
    {
        $sql = "SELECT ticket_id, ticket_code, status, created_at 
                FROM tickets 
                WHERE staff_id = $1 AND ticket_type = 'EXPORT' AND status IN ('PENDING', 'PROCESSING', 'PAUSED') AND is_deleted = false 
                ORDER BY created_at DESC";
        $res = pg_query_params($this->conn, $sql, [$staff_id]);
        return $res ? (pg_fetch_all($res) ?: []) : [];
    }

    /**
     * Chức năng: Cập nhật biến số lượng nhặt được vào CSDL.
     * Tác dụng: Cộng dồn biến processed_qty cho tới khi hoàn tất chỉ tiêu đề ra.
     */
    public function updateExportProgress($detail_id, $picked_qty)
    {
        $sql = "UPDATE ticket_details SET processed_qty = processed_qty + $1 WHERE detail_id = $2";
        return pg_query_params($this->conn, $sql, [$picked_qty, $detail_id]);
    }

    /**
     * Chức năng: Tìm các Ô, Kệ chứa dòng JSONB mã hàng.
     * Tác dụng: Truy xuất được vị trí (layout) kho và chỉ đường cho thủ kho đi gom giày xuất đi.
     */
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

    /**
     * Chức năng: Rà soát & kết thúc quy trình xuất kho.
     * Tác dụng: Xác nhận hàng đi. Khấu trừ cả lượng stock thực và lượng stock reserved. Giải phóng kệ chứa mảng JSON.
     */
    public function completeExportTicket($ticket_id)
    {
        pg_query($this->conn, "BEGIN");
        try {
            $ticketModel = new TicketModel();
            $details = $ticketModel->getTicketDetails($ticket_id);
            foreach ($details as $item) {
                $qty = (int)$item['quantity'];
                $v_id = $item['variant_id'];

                $sqlStock = "UPDATE product_variants SET stock = stock - $1, reserved_stock = reserved_stock - $1 WHERE variant_id = $2";
                pg_query_params($this->conn, $sqlStock, [$qty, $v_id]);

                $this->removeItemsFromShelves($v_id, $qty);
            }

            pg_query_params($this->conn, "UPDATE tickets SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP WHERE ticket_id = $1", [$ticket_id]);

            pg_query($this->conn, "COMMIT");
            return true;
        } catch (Exception $e) {
            pg_query($this->conn, "ROLLBACK");
            return false;
        }
    }

    /**
     * Chức năng: Helper bóc tách mảng JSON của cấu trúc kho bãi.
     * Tác dụng: Gỡ chuỗi Object array theo ID, trả lại chổ trống cho kệ kho để sau này chứa đồ khác.
     */
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
                            $items = array_values($items); 
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
}