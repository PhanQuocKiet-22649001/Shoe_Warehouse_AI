<?php

require_once __DIR__ . '/../models/UserModel.php';

class AuthController
{

    public function handleLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $user_id = trim($_POST['user_id'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($user_id) || !is_numeric($user_id)) {
                return "Mã nhân viên (ID) phải là định dạng số!";
            }

            $userModel = new UserModel();
            $user = $userModel->findById($user_id);

            if (!$user) {
                return "Mã nhân viên (ID) không tồn tại!";
            }

            if (password_verify($password, $user['password_hash'])) {

                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                header("Location: index.php?page=dashboard");
                exit;
            } else {
                return "Sai mật khẩu!";
            }
        }
    }
}
