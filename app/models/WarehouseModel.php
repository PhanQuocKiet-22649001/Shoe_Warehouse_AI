<?php
// app/models/WarehouseModel.php

class WarehouseModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    // 1. Lấy toàn bộ dữ liệu kệ thô
    public function getAllShelves() {
        $res = pg_query($this->conn, "SELECT * FROM shelves WHERE is_deleted = true ORDER BY shelf_name ASC");
        return $res ? pg_fetch_all($res) : [];
    }

    // 2. Lấy Map ánh xạ: [variant_id => tên_hãng]
    public function getVariantBrandMap() {
        $sql = "SELECT v.variant_id, c.category_name 
                FROM product_variants v 
                JOIN products p ON v.product_id = p.product_id 
                JOIN categories c ON p.category_id = c.category_id";
        $res = pg_query($this->conn, $sql);
        $map = [];
        if ($res) { 
            while ($r = pg_fetch_assoc($res)) { 
                $map[$r['variant_id']] = $r['category_name']; 
            } 
        }
        return $map;
    }

    // 3. Tìm Variant thô cho AJAX Search trên Sơ đồ
    public function searchVariantsRaw($keyword) {
        $sql = "SELECT v.variant_id, p.product_name, p.product_image, v.size, v.color, c.category_name as brand
                FROM product_variants v JOIN products p ON v.product_id = p.product_id
                JOIN categories c ON p.category_id = c.category_id
                WHERE (p.product_name ILIKE $1 OR c.category_name ILIKE $1) AND v.is_deleted = false";
        $res = pg_query_params($this->conn, $sql, ["%$keyword%"]);
        return $res ? pg_fetch_all($res) : [];
    }


    //Lấy Dictionary chi tiết của tất cả Variant (Dùng cho Popover Sơ đồ kho)
    public function getVariantDict() {
        // BỔ SUNG: Đã thêm p.product_id và p.category_id vào SELECT
        $sql = "SELECT v.variant_id, p.product_id, p.category_id, p.product_name, p.product_image, v.size, v.color 
                FROM product_variants v 
                JOIN products p ON v.product_id = p.product_id";
        
        $res = pg_query($this->conn, $sql);
        $dict = [];
        if ($res) {
            while ($row = pg_fetch_assoc($res)) {
                $dict[$row['variant_id']] = $row;
            }
        }
        return $dict;
    }


    // Chức năng: Thêm kệ mới vào DB, tự sinh cấu trúc layout mảng JSON trống theo số tầng và ô bạn đã kéo lưới
        public function addShelf($shelf_name, $max_capacity, $tiers, $slots) {
        $layout = [];
        for ($t = $tiers; $t >= 1; $t--) {
            $layout[(string)$t] = [];
            for ($s = 1; $s <= $slots; $s++) {
                $slotKey = str_pad($s, 2, '0', STR_PAD_LEFT);
                $layout[(string)$t][$slotKey] = [];
            }
        }
        
        // Bổ sung: Gắn cứng is_deleted = true và status = true khi tạo mới
        $sql = "INSERT INTO shelves (shelf_name, total_tiers, slots_per_tier, max_capacity_per_slot, layout, is_deleted, status) 
                VALUES ($1, $2, $3, $4, $5, true, true)";
                
        return pg_query_params($this->conn, $sql, [
            trim($shelf_name), 
            (int)$tiers, 
            (int)$slots, 
            (int)$max_capacity, 
            json_encode($layout)
        ]);
    }




     // Chức năng: Kiểm tra xem toàn bộ các ô trong kệ có đang trống hoàn toàn hay không
    public function isShelfEmpty($shelf_id) {
        $sql = "SELECT layout FROM shelves WHERE shelf_id = $1";
        $res = pg_query_params($this->conn, $sql, [$shelf_id]);
        if ($row = pg_fetch_assoc($res)) {
            $layout = json_decode($row['layout'], true);
            foreach ($layout as $tier => $slots) {
                foreach ($slots as $slot => $items) {
                    if (count($items) > 0) return false; // Có chứa giày, không trống
                }
            }
            return true; // Trống hoàn toàn
        }
        return false;
    }


    // Chức năng: Xóa mềm kệ (Đổi is_deleted thành false)
    public function softDeleteShelf($shelf_id) {
        $sql = "UPDATE shelves SET is_deleted = false WHERE shelf_id = $1";
        return pg_query_params($this->conn, $sql, [$shelf_id]);
    }


    // Chức năng: Bật/Tắt trạng thái hoạt động của kệ (status lật ngược bằng NOT status)
    public function toggleStatusShelf($shelf_id) {
        $sql = "UPDATE shelves SET status = NOT status WHERE shelf_id = $1";
        return pg_query_params($this->conn, $sql, [$shelf_id]);
    }


         // Chức năng: Helper tìm ID kệ và trạng thái thông qua tên kệ
    public function getShelfIdByName($shelf_name) {
        // SỬA CHỮ FALSE THÀNH TRUE Ở CUỐI CÂU DƯỚI ĐÂY
        $sql = "SELECT shelf_id, status FROM shelves WHERE shelf_name = $1 AND is_deleted = true";
        
        $res = pg_query_params($this->conn, $sql, [$shelf_name]);
        return $res ? pg_fetch_assoc($res) : null;
    }

}