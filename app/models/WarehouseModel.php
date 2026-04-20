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
        $res = pg_query($this->conn, "SELECT * FROM shelves ORDER BY shelf_name ASC");
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
}