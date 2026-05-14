<?php
require_once __DIR__ . '/config/database.php';

// 1. Ép kiểu dữ liệu về Integer. "undefined" hoặc rỗng sẽ trở thành 0.
$vid = (int)($_GET['vid'] ?? 0);
$tid = isset($_GET['tid']) && $_GET['tid'] !== 'undefined' ? (int)$_GET['tid'] : null;

if ($vid <= 0) {
    die("<h3 style='text-align:center; margin-top:50px; color:red;'>LỖI: MÃ SẢN PHẨM KHÔNG HỢP LỆ!</h3>");
}

$conn = getConnection();

// 2. SQL Nâng cao: Dùng LEFT JOIN để lấy được thông tin giày ngay cả khi chưa từng có phiếu nhập
$sql = "SELECT 
            t.ticket_code, t.created_at,
            p.product_name, p.product_image,
            c.category_name as brand,
            pv.color, pv.size,
            td.quantity, td.processed_qty, td.is_diff, td.note,
            u.full_name as staff_name
        FROM product_variants pv
        JOIN products p ON pv.product_id = p.product_id
        JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN ticket_details td ON td.variant_id = pv.variant_id
        LEFT JOIN tickets t ON td.ticket_id = t.ticket_id
        LEFT JOIN users u ON t.staff_id = u.user_id
        WHERE pv.variant_id = $1";

if ($tid) {
    $sql .= " AND t.ticket_id = $2";
    $params = [$vid, $tid];
} else {
    // Nếu quét tại kệ, lấy lần nhập mới nhất.
    $sql .= " ORDER BY t.created_at DESC NULLS LAST LIMIT 1";
    $params = [$vid];
}

$res = pg_query_params($conn, $sql, $params);
if (!$res) {
    die("<h3 style='text-align:center; margin-top:50px;'>LỖI TRUY VẤN HỆ THỐNG!</h3>");
}

$item = pg_fetch_assoc($res);

// 3. Xử lý trường hợp có sản phẩm nhưng chưa bao giờ nhập kho
if (!$item || empty($item['ticket_code'])) {
    $pName = $item['product_name'] ?? 'Sản phẩm';
    die("
        <div style='text-align:center; margin-top:100px; font-family:sans-serif; padding: 20px;'>
            <h2 style='color:#f39c12;'>CHƯA CÓ DỮ LIỆU NHẬP KHO</h2>
            <p>Sản phẩm: <b>$pName</b></p>
            <p>Mẫu này đã có trong danh mục nhưng chưa thực hiện nhập kho chính thức.</p>
            <a href='javascript:history.back()' style='text-decoration:none; color:#007bff;'>← Quay lại</a>
        </div>
    ");
}

$imgName = trim($item['product_image']);
$imgSrc = $imgName ? "/Shoe_Warehouse/public/assets/img_product/" . rawurlencode($imgName) : "/Shoe_Warehouse/public/assets/images/placeholder.png";
$isDiff = ($item['is_diff'] === 't' || $item['is_diff'] === true);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truy xuất nguồn gốc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: sans-serif; }
        .product-img { width: 100%; max-height: 250px; object-fit: contain; background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #dee2e6; }
        .card { border-radius: 15px; }
    </style>
</head>
<body>
<div class="container py-4" style="max-width: 500px;">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-dark"><i class="fas fa-box-open text-primary"></i> SMART WAREHOUSE</h4>
        <p class="text-muted small mb-0">Hệ thống truy xuất thông tin sản phẩm</p>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <img src="<?= $imgSrc ?>" onerror="this.src='/Shoe_Warehouse/public/assets/images/placeholder.png'" class="product-img mb-3">
            <div class="text-center mb-3">
                <span class="badge bg-secondary mb-1"><?= $item['brand'] ?></span>
                <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($item['product_name']) ?></h5>
                <div>
                    <span class="badge border border-dark text-dark">Màu: <?= $item['color'] ?></span>
                    <span class="badge border border-dark text-dark ms-1">Size: <?= $item['size'] ?></span>
                </div>
            </div>
            <hr>
            <div class="row text-center mb-2">
                <div class="col-6 border-end">
                    <div class="text-muted small">Cần nhập</div>
                    <div class="fs-4 fw-bold"><?= $item['quantity'] ?></div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Thực tế</div>
                    <div class="fs-4 fw-bold <?= $isDiff ? 'text-danger' : 'text-success' ?>"><?= $item['processed_qty'] ?></div>
                </div>
            </div>
            <?php if ($isDiff): ?>
                <div class="alert alert-warning text-center mt-3 mb-0 p-2 border-warning small">
                    <i class="fas fa-exclamation-triangle"></i> Lô hàng có sai lệch số lượng!<br>
                    Ghi chú: <?= htmlspecialchars($item['note'] ?: 'Không có') ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2 small">
                <span class="text-muted"><i class="fas fa-receipt me-1"></i> Mã phiếu:</span>
                <span class="fw-bold"><?= $item['ticket_code'] ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2 small">
                <span class="text-muted"><i class="fas fa-user-check me-1"></i> Người phụ trách:</span>
                <span class="fw-bold text-primary"><?= htmlspecialchars($item['staff_name'] ?: 'N/A') ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted"><i class="fas fa-clock me-1"></i> Thời điểm:</span>
                <span class="fw-bold"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></span>
            </div>
        </div>
    </div>
</div>
</body>
</html>