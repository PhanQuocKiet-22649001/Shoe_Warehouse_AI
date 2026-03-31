<?php
// app/models/CategoryModel.php

class CategoryModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    // lấy danh sách hãng giày
    public function getAll()
    {
        $sql = "SELECT category_id, category_name, logo, created_at, status 
                FROM categories 
                WHERE is_deleted = false 
                ORDER BY category_id ASC";
        $result = pg_query($this->conn, $sql);
        return $result ? pg_fetch_all($result) : [];
    }

    // lấy thông tin chi tiết 1 hãng
    public function getById($id)
    {
        $sql = "SELECT * FROM categories WHERE category_id = $1";
        $result = pg_query_params($this->conn, $sql, [$id]);
        return $result ? pg_fetch_assoc($result) : null;
    }

    // thêm hãng mới
    public function create($category_name, $logo, $created_by) // Thêm tham số $created_by
    {
        $sql = "INSERT INTO categories (category_name, logo, is_deleted, created_at, created_by)
            VALUES ($1, $2, false, NOW(), $3)";
        return pg_query_params($this->conn, $sql, [trim($category_name), $logo, $created_by]);
    }

    // cập nhật
    public function updateStatus($id, $new_status)
    {
        // $new_status sẽ là 't' (true) hoặc 'f' (false)
        $sql = "UPDATE categories SET status = $1 WHERE category_id = $2";
        return pg_query_params($this->conn, $sql, [$new_status, $id]);
    }

    //xóa hãng giày
    public function delete($category_id)
    {
        $sql = "UPDATE categories SET is_deleted = true WHERE category_id = $1";
        return pg_query_params($this->conn, $sql, [$category_id]);
    }

    /**
     * HÀM MỚI: Tự động dò tìm ID Hãng bằng Tên chữ. 
     * Nếu không tìm thấy, tự động khởi tạo Hãng mới.
     */
    public function getCategoryIdByName($brandName, $user_id)
    {
        $brandName = trim($brandName);

        // 1. Dùng ILIKE để tìm không phân biệt chữ hoa/thường (ví dụ: gõ "nike" vẫn khớp với "Nike")
        $sql = "SELECT category_id FROM categories WHERE category_name ILIKE $1 AND is_deleted = false LIMIT 1";
        $result = pg_query_params($this->conn, $sql, [$brandName]);
        $row = pg_fetch_assoc($result);

        if ($row) {
            // Đã có Hãng -> Trả về ID
            return $row['category_id'];
        } else {
            // 2. Chưa có Hãng -> Tự động Insert mới để hệ thống không bị lỗi gãy
            // Dùng một logo rỗng hoặc mặc định
            $insertSql = "INSERT INTO categories (category_name, logo, is_deleted, created_at, created_by, status)
                          VALUES ($1, 'default_brand.png', false, NOW(), $2, 't') RETURNING category_id";
            $insertResult = pg_query_params($this->conn, $insertSql, [$brandName, $user_id]);

            if ($insertResult) {
                $newRow = pg_fetch_assoc($insertResult);
                return $newRow['category_id']; // Trả về ID vừa mới sinh ra
            }

            return false;
        }
    }
}
