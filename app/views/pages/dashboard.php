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
            <h5 class="mb-0 text-success"><?= number_format($stats['period_exports'] ?? 0) ?> đôi</h5>
        </div>
    </div>
    <div class="flex-fill" style="min-width: 150px;">
        <div class="card p-3 border-0 shadow-sm">
            <p class="mb-1 text-secondary">Dự báo Thiếu hụt</p>
            <h5 class="mb-0 text-danger"><?= $stats['shortage_count'] ?? 0 ?> mã hàng</h5>
        </div>
    </div>
</div>

<?php if (strtoupper($_SESSION['role'] ?? '') === 'MANAGER'): ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card p-3 border-0 shadow-sm h-100">
                <h3>Top Bán Chạy</h3>
                <?php 
                if (!empty($top_selling)): 
                    // Tìm số lượng bán cao nhất để làm mốc 100% cho thanh Progress
                    $max_sold = $top_selling[0]['total_sold']; 
                    foreach ($top_selling as $item): 
                        $percent = ($item['total_sold'] / $max_sold) * 100;
                ?>
                    <div class="bar-item mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold"><?= htmlspecialchars($item['product_name']) ?></span>
                            <span class="small text-muted"><?= number_format($item['total_sold']) ?> đôi</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <p class="text-muted small">Chưa có dữ liệu xuất hàng.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-3 border-0 shadow-sm h-100">
                <h3>AI Dự báo Xu Thế</h3>
                <div class="chart-placeholder text-center p-5 bg-light rounded d-flex flex-column justify-content-center h-100">
                    <i class="fas fa-chart-line fa-3x text-muted mb-2"></i>
                    <p class="text-muted mb-0">Biểu đồ đang phân tích...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-3 heatmap-box border-0 shadow-sm">
        <h3>Heatmap Vị Trí Sản Phẩm</h3>
        <p class="small text-muted mb-3">Màu càng đậm thể hiện biến thể càng được luân chuyển nhiều</p>
    </div>
<?php else: ?>
    <div class="alert alert-info border-0 shadow-sm">
        <i class="fas fa-info-circle me-2"></i> Chào <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'bạn') ?></strong>, hãy kiểm tra danh sách yêu cầu xuất kho hôm nay nhé!
    </div>
<?php endif; ?>