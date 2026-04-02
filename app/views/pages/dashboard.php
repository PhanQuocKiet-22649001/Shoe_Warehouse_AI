<link rel="stylesheet" href="assets/css/dashboard.css">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<h1>Tổng quan Kho</h1>
<p class="text-muted mb-4">Dữ liệu cập nhật thời gian thực</p>

<div class="d-flex flex-wrap gap-3 mb-4 cards">
    <div class="flex-fill" style="min-width: 150px;">
        <div class="card p-3 border-0 shadow-sm">
            <p class="mb-1 text-secondary">Tổng Tồn Kho</p>
            <h5 class="mb-0"><?= number_format($stats['total_stock'] ?? 0) ?> đôi</h5>
        </div>
    </div>
    <div class="flex-fill" style="min-width: 150px;">
        <div class="card p-3 border-0 shadow-sm">
            <p class="mb-1 text-secondary">Đã xuất (Tháng này)</p>
            <h5 class="mb-0"><?= number_format($stats['monthly_exports'] ?? 0) ?> đôi</h5>
        </div>
    </div>
    <div class="flex-fill" style="min-width: 150px;">
        <div class="card p-3 border-0 shadow-sm">
            <p class="mb-1 text-secondary">Dự báo Thiếu hụt</p>
            <h5 class="mb-0 text-danger"><?= $stats['shortage_count'] ?? 0 ?> mã hàng</h5>
        </div>
    </div>
</div>

<?php if (strtoupper($_SESSION['role']) === 'MANAGER'): ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card p-3 border-0 shadow-sm">
                <h3>Top Bán Chạy</h3>
                <?php if (!empty($top_selling)): foreach ($top_selling as $item): ?>
                    <div class="bar-item mb-2">
                        <span class="small"><?= $item['product_name'] ?> (<?= $item['total_sold'] ?>)</span>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" style="width: 70%"></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-3 border-0 shadow-sm">
                <h3>AI Dự báo Xu Thế</h3>
                <div class="chart-placeholder text-center p-5 bg-light rounded">
                    <i class="fas fa-chart-line fa-3x text-muted mb-2"></i>
                    <p class="text-muted">Biểu đồ đang phân tích...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-3 heatmap-box border-0 shadow-sm">
        <h3>Heatmap Vị Trí Sản Phẩm</h3>
        <div class="heatmap d-flex flex-wrap gap-1">
            <?php foreach ($heatmap as $h): ?>
                <div class="cell" style="background: rgba(255, 0, 0, <?= $h['activity_count']/10 ?>);" title="ID: <?= $h['variant_id'] ?>"></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info border-0 shadow-sm">
        Chào <strong><?= $_SESSION['full_name'] ?></strong>, hãy kiểm tra danh sách yêu cầu xuất kho hôm nay nhé!
    </div>
<?php endif; ?>