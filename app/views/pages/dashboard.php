
<!-- CSS riêng của dashboard -->
<link rel="stylesheet" href="assets/css/dashboard.css">

<!-- thêm topbar -->
<?php include __DIR__ . '/../layouts/topbar.php'; ?>


<h1>Tổng quan Quản lý Kho</h1>
<p class="text-muted mb-4">Hệ thống AI đang phân tích dữ liệu kho vật lý</p>

<!-- Stats Cards -->
<div class="d-flex flex-wrap gap-3 mb-4 cards">
    <div class="flex-fill" style="min-width: 150px; max-width: 23%;">
        <div class="card p-3">
            <p>Tổng Tồn Kho</p>
            <h5>[Số lượng Tồn Kho]</h5>
        </div>
    </div>
    <div class="flex-fill" style="min-width: 150px; max-width: 23%;">
        <div class="card p-3">
            <p>Đã xuất (Tháng này)</p>
            <h5>[Số lượng]</h5>
        </div>
    </div>
    <div class="flex-fill" style="min-width: 150px; max-width: 23%;">
        <div class="card p-3">
            <p>Độ chính xác</p>
            <h5>[Phần trăm]</h5>
        </div>
    </div>
    <div class="flex-fill" style="min-width: 150px; max-width: 23%;">
        <div class="card p-3">
            <p>Dự báo Thiếu hụt</p>
            <h5>[Số lượng]</h5>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card p-3">
            <h3>Top Bán Chạy</h3>
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="bar-item">
                    <span>[Tên sản phẩm <?= $i ?>]</span>
                    <div class="bar"></div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3">
            <h3>AI Dự báo Xu Thế</h3>
            <div class="chart-placeholder">
                <div class="text-muted">T4 - T5 - T6 - T7</div>
            </div>
        </div>
    </div>
</div>

<!-- Heatmap -->
<div class="card p-3 heatmap-box">
    <h3>Heatmap Mặt Cắt Kệ Kho & Vị Trí Sản Phẩm</h3>
    <p class="text-muted">Hot: gần cửa / tầng thấp — Cold: xa cửa / tầng cao</p>
    <div class="heatmap">
        <?php for ($i = 0; $i < 24; $i++): ?>
            <div class="cell"></div>
        <?php endfor; ?>
    </div>
</div>