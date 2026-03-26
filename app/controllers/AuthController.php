<?php

require_once __DIR__ . '/../models/UserModel.php';

class AuthController
{

    public function handleLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            $userModel = new UserModel();
            $user = $userModel->findByUsername($username);

            if (!$user) {
                return "Tên đăng nhập không tồn tại!";
            }

            if (password_verify($password, $user['password_hash'])) {

                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
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
