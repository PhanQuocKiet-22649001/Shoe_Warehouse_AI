<?php
// app/models/ProductModel.php

class ProductModel
{
    private $conn;

    public function __construct()
    {
        // Sử dụng hàm global getConnection() để lấy kết nối
        $this->conn = getConnection();
    }

    // Lấy danh sách sản phẩm theo hãng (chỉ lấy sản phẩm chưa bị xóa)
    public function getByCategory($category_id)
    {
        $sql = "SELECT product_id, category_id, product_name, product_image, status, is_deleted 
                FROM products 
                WHERE category_id = $1 AND is_deleted = false 
                ORDER BY product_id DESC";
        $result = pg_query_params($this->conn, $sql, [$category_id]);
        return $result ? pg_fetch_all($result) : [];
    }

    // Lấy tên hãng để hiển thị tiêu đề trang
    public function getCategoryName($category_id)
    {
        $sql = "SELECT category_name FROM categories WHERE category_id = $1";
        $result = pg_query_params($this->conn, $sql, [$category_id]);
        $row = pg_fetch_assoc($result);
        return $row ? $row['category_name'] : 'Sản phẩm';
    }

    // Xóa mềm sản phẩm
    public function delete($product_id)
    {
        $sql = "UPDATE products SET is_deleted = true WHERE product_id = $1";
        return pg_query_params($this->conn, $sql, [$product_id]);
    }

    // Cập nhật trạng thái kinh doanh của sản phẩm
    public function updateStatus($product_id, $new_status)
    {
        $sql = "UPDATE products SET status = $1 WHERE product_id = $2";
        return pg_query_params($this->conn, $sql, [$new_status, $product_id]);
    }

    // Hàm cập nhật trạng thái toàn bộ sản phẩm theo ID hãng
    public function updateStatusByCategory($category_id, $new_status)
    {
        // Cập nhật trạng thái ('t' hoặc 'f') cho tất cả sản phẩm của hãng này
        $sql = "UPDATE products SET status = $1 WHERE category_id = $2";
        return pg_query_params($this->conn, $sql, [$new_status, $category_id]);
    }

    // lấy các biến thể của 1 sản phẩm
    public function getVariantsByProductId($product_id)
    {
        // JOIN với bảng products để lấy ảnh và tên (đề phòng sau này bồ cần)
        $sql = "SELECT v.variant_id, v.sku, v.size, v.color, v.stock, v.status as variant_status,
                   p.product_name, p.product_image
            FROM product_variants v
            JOIN products p ON v.product_id = p.product_id
            WHERE v.product_id = $1 AND v.is_deleted = false
            ORDER BY v.size ASC";

        $result = pg_query_params($this->conn, $sql, [$product_id]);
        return $result ? pg_fetch_all($result) : [];
    }

    // tìm kiếm sản phẩm
    public function searchProducts($keyword)
    {
        $keyword = '%' . $keyword . '%';
        $sql = "SELECT DISTINCT p.product_id, p.product_name, p.product_image, p.category_id 
            FROM products p
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            WHERE (p.product_name ILIKE $1 
               OR v.sku ILIKE $1 
               OR v.size ILIKE $1 
               OR v.color ILIKE $1)
               AND p.is_deleted = false
            LIMIT 8"; // Giới hạn 8 kết quả cho đẹp khung search

        $result = pg_query_params($this->conn, $sql, [$keyword]);
        return $result ? pg_fetch_all($result) : [];
    }
}
