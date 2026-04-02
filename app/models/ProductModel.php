<?php
// app/models/ProductModel.php

class ProductModel
{
    private $conn;

    /**
     * Khởi tạo kết nối CSDL toàn cục
     */
    public function __construct()
    {
        $this->conn = getConnection();
    }

    public function getConnection()
    {
        return $this->conn;
    }
    /**
     * Lấy danh sách sản phẩm theo ID Hãng (Loại trừ sản phẩm đã xóa)
     */
    public function getByCategory($category_id)
    {
        $sql = "SELECT product_id, category_id, product_name, product_image, status, is_deleted 
                FROM products 
                WHERE category_id = $1 AND is_deleted = false 
                ORDER BY product_id DESC";
        $result = pg_query_params($this->conn, $sql, [$category_id]);
        return $result ? pg_fetch_all($result) : [];
    }

    /**
     * Lấy tên hãng để hiển thị tiêu đề UI
     */
    public function getCategoryName($category_id)
    {
        $sql = "SELECT category_name FROM categories WHERE category_id = $1";
        $result = pg_query_params($this->conn, $sql, [$category_id]);
        $row = pg_fetch_assoc($result);
        return $row ? $row['category_name'] : 'Sản phẩm';
    }

    /**
     * Xóa mềm sản phẩm
     */
    public function delete($product_id)
    {
        $sql = "UPDATE products SET is_deleted = true WHERE product_id = $1";
        return pg_query_params($this->conn, $sql, [$product_id]);
    }

    /**
     * Cập nhật trạng thái kinh doanh của 1 sản phẩm
     */
    public function updateStatus($product_id, $new_status)
    {
        $sql = "UPDATE products SET status = $1 WHERE product_id = $2";
        return pg_query_params($this->conn, $sql, [$new_status, $product_id]);
    }

    /**
     * Cập nhật trạng thái hàng loạt theo Hãng
     */
    public function updateStatusByCategory($category_id, $new_status)
    {
        $sql = "UPDATE products SET status = $1 WHERE category_id = $2";
        return pg_query_params($this->conn, $sql, [$new_status, $category_id]);
    }

    /**
     * Lấy danh sách biến thể (Size, Màu, Tồn kho) thuộc về 1 sản phẩm
     */
    public function getVariantsByProductId($product_id)
    {
        $sql = "SELECT v.variant_id, v.sku, v.size, v.color, v.stock, v.status as variant_status,
                   p.product_name, p.product_image
            FROM product_variants v
            JOIN products p ON v.product_id = p.product_id
            WHERE v.product_id = $1 AND v.is_deleted = false
            ORDER BY v.size ASC";
        $result = pg_query_params($this->conn, $sql, [$product_id]);
        return $result ? pg_fetch_all($result) : [];
    }

    /**
     * Tìm kiếm sản phẩm linh hoạt (Tên, SKU, Size, Màu)
     */
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
            LIMIT 8";
        $result = pg_query_params($this->conn, $sql, [$keyword]);
        return $result ? pg_fetch_all($result) : [];
    }

    /**
     * Tạo bản ghi Sản phẩm Gốc (Cha)
     */
    public function create($name, $category_id, $image, $user_id)
    {
        $sql = "INSERT INTO products (product_name, category_id, product_image, created_by, status, is_deleted, created_at) 
            VALUES ($1, $2, $3, $4, 't', false, NOW()) 
            RETURNING product_id";
        $result = pg_query_params($this->conn, $sql, [$name, (int)$category_id, $image, (int)$user_id]);
        if ($result) {
            $row = pg_fetch_assoc($result);
            return $row['product_id'];
        }
        return false;
    }

    /**
     * Tạo bản ghi Biến thể (Con)
     */
    public function createVariant($product_id, $size, $color, $stock, $sku)
    {
        $sql = "INSERT INTO product_variants (product_id, size, color, stock, sku) 
            VALUES ($1, $2, $3, $4, $5)";
        return pg_query_params($this->conn, $sql, [(int)$product_id, (int)$size, $color, (int)$stock, $sku]);
    }

    /**
     * Đối soát văn bản: Tìm sản phẩm dựa theo Tên và Hãng
     */
    public function findExistingProduct($brandName, $modelName)
    {
        $brandName = trim($brandName);
        $modelName = trim($modelName);
        $sql = "SELECT p.product_id, p.product_name, c.category_id, c.category_name
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE (REPLACE(p.product_name, '\"', '') ILIKE $1) 
            AND c.category_name ILIKE $2 
            AND p.is_deleted = false 
            LIMIT 1";
        $cleanModelName = str_replace('"', '', $modelName);
        $res = pg_query_params($this->conn, $sql, ["%$cleanModelName%", "%$brandName%"]);
        return $res ? pg_fetch_assoc($res) : null;
    }

    /**
     * Kiểm tra tính duy nhất của Màu sắc trong 1 sản phẩm
     */
    public function checkColorExists($product_id, $color)
    {
        $sql = "SELECT color FROM product_variants 
            WHERE product_id = $1 AND color ILIKE $2 AND is_deleted = false LIMIT 1";
        $res = pg_query_params($this->conn, $sql, [$product_id, $color]);
        return (bool)pg_fetch_assoc($res);
    }

    /**
     * Đối soát Biến thể: Tìm chính xác ID, Size, Màu
     */
    public function findVariant($product_id, $size, $color)
    {
        $sql = "SELECT variant_id, stock FROM product_variants 
            WHERE product_id = $1 AND size = $2 AND color ILIKE $3 AND is_deleted = false LIMIT 1";
        $res = pg_query_params($this->conn, $sql, [(int)$product_id, (int)$size, $color]);
        return $res ? pg_fetch_assoc($res) : null;
    }

    /**
     * Cộng dồn số lượng tồn kho
     */
    public function addStock($variant_id, $quantity)
    {
        $sql = "UPDATE product_variants SET stock = stock + $1 WHERE variant_id = $2";
        return pg_query_params($this->conn, $sql, [(int)$quantity, (int)$variant_id]);
    }

    /**
     * Lấy mảng màu sắc hiện có của sản phẩm để hiện Combobox AI
     */
    public function getColorsByProduct($product_id)
    {
        $sql = "SELECT DISTINCT color FROM product_variants 
            WHERE product_id = $1 AND is_deleted = false 
            ORDER BY color ASC";
        $result = pg_query_params($this->conn, $sql, [(int)$product_id]);
        return $result ? pg_fetch_all($result) : [];
    }

    /**
     * Lưu trữ "ADN hình ảnh" (Mảng Vector 512 chiều) vào CSDL bằng pgvector
     */
    public function updateImageEmbedding($product_id, $vectorArray)
    {
        // pgvector yêu cầu chuỗi có dạng: "[0.1, 0.2, 0.3...]"
        $vectorString = '[' . implode(',', $vectorArray) . ']';

        $sql = "UPDATE products SET image_embedding = $1 WHERE product_id = $2";

        // Thực thi query với tham số an toàn
        $result = pg_query_params($this->conn, $sql, [$vectorString, (int)$product_id]);

        return $result ? true : false;
    }

    /**
     * Tìm kiếm hình ảnh bằng AI: Hỗ trợ kịch bản đối soát thông minh
     * Lấy danh sách các sản phẩm có độ tương đồng >= 80%
     */
    public function findTopMatchesByAI($vectorArray, $limit = 3)
    {
        // Định dạng lại mảng Vector cho PostgreSQL
        $vectorString = '[' . implode(',', $vectorArray) . ']';

        // SQL: Lấy thông tin sản phẩm + hãng + tính toán Similarity Score
        // (1 - Distance) = Similarity. Distance càng nhỏ, Similarity càng cao.
        $sql = "SELECT p.product_id, p.product_name, p.product_image, 
                       c.category_id, c.category_name as brand,
                       (1 - (p.image_embedding <=> $1)) AS similarity_score
                FROM products p
                JOIN categories c ON p.category_id = c.category_id
                WHERE p.is_deleted = false 
                  AND p.image_embedding IS NOT NULL
                  AND (1 - (p.image_embedding <=> $1)) >= 0.80 -- Chỉ lấy những mẫu giống trên 80%
                ORDER BY similarity_score DESC 
                LIMIT $2";

        $result = pg_query_params($this->conn, $sql, [$vectorString, (int)$limit]);

        return $result ? pg_fetch_all($result) : [];
    }


    /**
     * Trừ số lượng tồn kho (Xuất kho)
     */
    public function removeStock($variant_id, $quantity)
    {
        // Chỉ trừ nếu số lượng xuất <= số lượng tồn
        $sql = "UPDATE product_variants SET stock = stock - $1 
            WHERE variant_id = $2 AND stock >= $1";
        return pg_query_params($this->conn, $sql, [(int)$quantity, (int)$variant_id]);
    }


    /**
     * Ghi lại lịch sử giao dịch (Nhập/Xuất)
     */
    public function logTransaction($type, $variant_id, $quantity, $user_id)
    {
        $sql = "INSERT INTO transactions (transaction_type, variant_id, quantity, user_id) 
            VALUES ($1, $2, $3, $4)";
        return pg_query_params($this->conn, $sql, [$type, (int)$variant_id, (int)$quantity, (int)$user_id]);
    }


    /**
     * Cập nhật trạng thái Bật/Tắt của biến thể (Status)
     */
    public function updateVariantStatus($variantId, $status)
    {
        // PostgreSQL hiểu chuỗi 'true' hoặc 'false' cho kiểu boolean
        $sql = "UPDATE product_variants 
                SET status = $1 
                WHERE variant_id = $2";
        
        $result = pg_query_params($this->conn, $sql, [$status, (int)$variantId]);
        return $result ? true : false;
    }

    /**
     * Xóa mềm biến thể (Đưa vào thùng rác)
     */
    public function softDeleteVariant($variantId)
    {
        // Xóa mềm: is_deleted = true và tắt luôn status = false
        $sql = "UPDATE product_variants 
                SET is_deleted = true, status = false 
                WHERE variant_id = $1";
                
        $result = pg_query_params($this->conn, $sql, [(int)$variantId]);
        return $result ? true : false;
    }
}
