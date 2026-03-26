<?php
// app/models/UserModel.php

require_once __DIR__ . '/../../config/database.php';

class UserModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    // Tìm user theo username (bảo mật bằng prepare)
    public function findByUsername($username)
    {
        $sql =
            "SELECT user_id, username, password_hash, full_name, role, status 
        FROM users 
        WHERE username = $1 AND is_deleted = false AND status = true";

        // Chuẩn bị query
        pg_prepare($this->conn, "find_user", $sql);

        // Thực thi
        $result = pg_execute($this->conn, "find_user", array($username));

        if ($result && pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }

        return null;
    }

    // Lấy tất cả nhân viên chưa bị xóa
    public function getAllUsers()
    {

        $sql = "SELECT user_id, full_name, username, role, status, created_at 
            FROM users 
            WHERE is_deleted = false 
            ORDER BY user_id ASC";

        $result = pg_query($this->conn, $sql);
        return pg_fetch_all($result);
    }

    // Thêm nhân viên mới
    public function addUser($data)
    {
        $sql = "INSERT INTO users (full_name, username, password_hash, role, status, is_deleted, created_at)
            VALUES ($1, $2, $3, $4, $5, false, NOW())";

        // Hash mật khẩu an toàn
        $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);

        // Chuyển đổi boolean sang chuỗi 'true'/'false' cho PostgreSQL
        $status_db = $data['status'] ? 'true' : 'false';

        $result = pg_query_params($this->conn, $sql, [
            trim($data['full_name']),
            trim($data['username']),
            $password_hash,
            strtoupper(trim($data['role'])),
            $status_db
        ]);

        return $result;
    }

    // Xóa mềm nhân viên
    public function deleteUser($user_id)
    {
        $sql = "UPDATE users SET is_deleted = true WHERE user_id = $1";
        return pg_query_params($this->conn, $sql, [$user_id]);
    }

    // cập nhật trạng thái (manager)
    public function updateUserStatus($user_id, $status)
    {
        // Chuyển đổi sang định dạng 'true'/'false' của PostgreSQL
        $status_db = ($status === true || $status === 'true' || $status === 'on') ? 'true' : 'false';

        $sql = "UPDATE users SET status = $1 WHERE user_id = $2 AND is_deleted = false";
        return pg_query_params($this->conn, $sql, [$status_db, $user_id]);
    }

    // Lấy 1 nhân viên theo ID
    public function getUserById($user_id)
    {
        $sql = "SELECT user_id, full_name, username, role, status, phone_number, address 
            FROM users 
            WHERE user_id = $1 AND is_deleted = false";

        // Dùng cái này cho an toàn, gọi bao nhiêu lần cũng được không bị lỗi "already exists"
        $result = pg_query_params($this->conn, $sql, [$user_id]);

        if ($result && pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
        return null;
    }



    // chỉnh sửa thông tin cá nhân
    public function updateProfile($user_id, $data)
    {
        // 1. Lấy thông tin hiện tại để giữ lại nếu không nhập mới
        $current = $this->getUserById($user_id);

        $phone = !empty($data['phone_number']) ? trim($data['phone_number']) : $current['phone_number'];
        $address = !empty($data['address']) ? trim($data['address']) : $current['address'];

        // 2. Xử lý mật khẩu
        if (!empty($data['new_password'])) {
            $password_hash = password_hash($data['new_password'], PASSWORD_BCRYPT);
            $sql = "UPDATE users SET phone_number = $1, address = $2, password_hash = $3 WHERE user_id = $4";
            $params = [$phone, $address, $password_hash, $user_id];
        } else {
            $sql = "UPDATE users SET phone_number = $1, address = $2 WHERE user_id = $3";
            $params = [$phone, $address, $user_id];
        }

        return pg_query_params($this->conn, $sql, $params);
    }

    // Hàm kiểm tra mật khẩu cũ
    public function verifyOldPassword($user_id, $old_password)
    {
        $sql = "SELECT password_hash FROM users WHERE user_id = $1";
        $result = pg_query_params($this->conn, $sql, [$user_id]);
        $user = pg_fetch_assoc($result);

        if ($user && password_verify($old_password, $user['password_hash'])) {
            return true;
        }
        return false;
    }
}
