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




    // 1. Tạo sản phẩm chính
    public function create($name, $category_id, $image, $user_id)
    {
        // Tuyệt đối không liệt kê product_id ở đây
        $sql = "INSERT INTO products (product_name, category_id, product_image, created_by, status, is_deleted, created_at) 
            VALUES ($1, $2, $3, $4, 't', false, NOW()) 
            RETURNING product_id";

        $result = pg_query_params($this->conn, $sql, [
            $name,
            (int)$category_id,
            $image,
            (int)$user_id
        ]);

        if ($result) {
            $row = pg_fetch_assoc($result);
            return $row['product_id']; // Lấy ID tự tăng vừa đẻ ra để dùng cho variant
        }
        return false;
    }

    // 2. Tạo biến thể cho sản phẩm đó
    public function createVariant($product_id, $size, $color, $stock, $sku)
    {
        // Tuyệt đối không liệt kê variant_id ở đây
        $sql = "INSERT INTO product_variants (product_id, size, color, stock, sku) 
            VALUES ($1, $2, $3, $4, $5)";

        return pg_query_params($this->conn, $sql, [
            (int)$product_id,
            (int)$size,
            $color,
            (int)$stock,
            $sku
        ]);
    }


    // Hàm "Đối soát" cực kỳ quan trọng
    public function findExistingProduct($brandName, $modelName)
    {
        $brandName = trim($brandName);
        $modelName = trim($modelName);

        // SQL này sẽ loại bỏ dấu " trong Database và trong từ khóa tìm kiếm để đối soát
        $sql = "SELECT p.product_id, p.product_name, c.category_id, c.category_name
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE (REPLACE(p.product_name, '\"', '') ILIKE $1) 
            AND c.category_name ILIKE $2 
            AND p.is_deleted = false 
            LIMIT 1";

        // Loại bỏ dấu " ở modelName do AI trả về trước khi truyền vào query
        $cleanModelName = str_replace('"', '', $modelName);

        $res = pg_query_params($this->conn, $sql, ["%$cleanModelName%", "%$brandName%"]);
        return $res ? pg_fetch_assoc($res) : null;
    }

    // Kiểm tra xem biến thể màu này của sản phẩm đã có chưa
    public function checkColorExists($product_id, $color)
    {
        $sql = "SELECT color FROM product_variants 
            WHERE product_id = $1 AND color ILIKE $2 AND is_deleted = false LIMIT 1";
        $res = pg_query_params($this->conn, $sql, [$product_id, $color]);
        return (bool)pg_fetch_assoc($res);
    }


    // 1. Tìm biến thể đã có (Trùng cả màu và size)
    public function findVariant($product_id, $size, $color)
    {
        $sql = "SELECT variant_id, stock FROM product_variants 
            WHERE product_id = $1 AND size = $2 AND color ILIKE $3 AND is_deleted = false LIMIT 1";
        $res = pg_query_params($this->conn, $sql, [(int)$product_id, (int)$size, $color]);
        return $res ? pg_fetch_assoc($res) : null;
    }

    // 2. Cộng dồn số lượng tồn kho (Update stock)
    public function addStock($variant_id, $quantity)
    {
        $sql = "UPDATE product_variants SET stock = stock + $1 WHERE variant_id = $2";
        return pg_query_params($this->conn, $sql, [(int)$quantity, (int)$variant_id]);
    }


    // lấy màu sắc giày trong db
    public function getColorsByProduct($product_id)
    {
        $sql = "SELECT DISTINCT color FROM product_variants 
            WHERE product_id = $1 AND is_deleted = false 
            ORDER BY color ASC";
        $result = pg_query_params($this->conn, $sql, [(int)$product_id]);
        return $result ? pg_fetch_all($result) : [];
    }
}
