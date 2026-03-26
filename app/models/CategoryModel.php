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
}
