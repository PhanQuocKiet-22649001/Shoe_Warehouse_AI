<?php
$error = isset($error) ? $error : '';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng nhập Smart Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="d-flex flex-column min-vh-100 m-0 p-0">

    <header class="w-100">
        <?php include __DIR__ . '/../layouts/header.php'; ?>
    </header>

    <main class="flex-grow-1 d-flex justify-content-center align-items-center w-100">
        <div class="login-box">
            <h2>SMART WAREHOUSE</h2>
            <p class="subtitle">ĐĂNG NHẬP</p>

            <?php if (!empty($error)): ?>
                <p class="error text-danger text-center"><?= $error ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Đăng nhập</button>
            </form>
        </div>
    </main>

    <footer class="w-100">
        <?php include __DIR__ . '/../layouts/footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>