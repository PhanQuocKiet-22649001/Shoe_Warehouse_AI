<link rel="stylesheet" href="assets/css/dashboard.css">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<h1>Tổng quan Kho</h1>
<p class="text-muted mb-4">Dữ liệu cập nhật thời gian thực</p>

<!-- card thống kê -->
<div class="d-flex flex-wrap gap-3 mb-4 cards">
    <div class="flex-fill clickable-card" data-type="stock" style="min-width: 150px; cursor: pointer;">
        <div class="card p-3 border-0 shadow-sm border-start border-primary border-4">
            <p class="mb-1 text-secondary">Tổng Tồn Kho</p>
            <h5 class="mb-0"><?= number_format($stats['total_stock'] ?? 0) ?> đôi</h5>
        </div>
    </div>

    <div class="flex-fill clickable-card" data-type="import" style="min-width: 150px; cursor: pointer;">
        <div class="card p-3 border-0 shadow-sm border-start border-info border-4">
            <p class="mb-1 text-secondary">Đã nhập (Tháng này)</p>
            <h5 class="mb-0 text-info"><?= number_format($stats['period_imports'] ?? 0) ?> đôi</h5>
        </div>
    </div>

    <div class="flex-fill clickable-card" data-type="export" style="min-width: 150px; cursor: pointer;">
        <div class="card p-3 border-0 shadow-sm border-start border-success border-4">
            <p class="mb-1 text-secondary">Đã xuất (Tháng này)</p>
            <h5 class="mb-0 text-success"><?= number_format($stats['period_exports'] ?? 0) ?> đôi</h5>
        </div>
    </div>

    <div class="flex-fill clickable-card" data-type="shortage" style="min-width: 150px; cursor: pointer;">
        <div class="card p-3 border-0 shadow-sm border-start border-danger border-4">
            <p class="mb-1 text-secondary">Dự báo Thiếu hụt</p>
            <h5 class="mb-0 text-danger"><?= $stats['shortage_count'] ?? 0 ?> mã hàng</h5>
        </div>
    </div>
</div>

<!-- modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Chi tiết dữ liệu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalContent">
                </div>
            </div>
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
                    <?php endforeach;
                else: ?>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('detailModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalContent = document.getElementById('modalContent');
        const bsModal = new bootstrap.Modal(modalEl);

        // 1. XỬ LÝ CLICK CARD CHÍNH (CẤP 1: HIỆN HÃNG BẰNG TABLE)
        document.querySelectorAll('.clickable-card').forEach(card => {
            card.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                const title = this.querySelector('p').innerText;

                modalTitle.innerText = 'Phân tích chi tiết: ' + title;
                modalTitle.style.color = '#ffffff'; // Thép ép màu trắng bằng JS
                modalContent.innerHTML = '<div class="text-center p-4"><div class="spinner-border" role="status"></div><p class="mt-2">Đang nạp dữ liệu...</p></div>';
                bsModal.show();

                fetch(`index.php?page=get_brand_data&type=${type}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            modalContent.innerHTML = '<p class="text-center p-4">Không có dữ liệu cho mục này.</p>';
                            return;
                        }

                        // VẪN GIỮ CẤU TRÚC TABLE, CHỈ BỎ CLASS MÀU
                        let html = `
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Thương hiệu</th>
                                    <th class="text-end">Số lượng</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>`;

                        data.forEach(item => {
                            html += `
                            <tr class="brand-row" data-id="${item.category_id}" data-type="${type}">
                                <td class="fw-bold">${item.brand}</td>
                                <td class="text-end fw-bold">${parseInt(item.total).toLocaleString()}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-get-products" style="border: 1px solid currentColor;" data-id="${item.category_id}" data-type="${type}">
                                        Chi tiết <i class="fas fa-chevron-down ms-1"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr id="brand-detail-${item.category_id}" class="d-none">
                                <td colspan="3" class="p-3">
                                    <div class="product-container p-2 rounded" style="border: 1px solid currentColor;">Đang tải sản phẩm...</div>
                                </td>
                            </tr>`;
                        });
                        html += '</tbody></table>';
                        modalContent.innerHTML = html;
                    });
            });
        });

        // 2. XỬ LÝ CLICK XEM SẢN PHẨM (CẤP 2: HIỆN MẪU GIÀY BẰNG TABLE)
        modalContent.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-get-products');
            if (!btn) return;

            const brandId = btn.getAttribute('data-id');
            const type = btn.getAttribute('data-type');
            const childBox = document.getElementById(`brand-detail-${brandId}`);
            const icon = btn.querySelector('i');

            if (childBox.classList.contains('d-none')) {
                childBox.classList.remove('d-none');
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');

                fetch(`index.php?page=get_product_data&brand_id=${brandId}&type=${type}`)
                    .then(res => res.json())
                    .then(products => {
                        let pTable = `
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Mẫu giày</th>
                                    <th class="text-end">Tổng</th>
                                    <th class="text-center">Biến thể</th>
                                </tr>
                            </thead>
                            <tbody>`;
                        products.forEach(p => {
                            pTable += `
                            <tr class="product-row" data-id="${p.product_id}" data-type="${type}">
                                <td>${p.product_name}</td>
                                <td class="text-end">${p.total}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm p-0 btn-get-variants" style="text-decoration: underline;">
                                        Xem
                                    </button>
                                </td>
                            </tr>
                            <tr id="product-detail-${p.product_id}" class="d-none">
                                <td colspan="3" class="p-2">
                                    <div class="variant-container"></div>
                                </td>
                            </tr>`;
                        });
                        pTable += '</tbody></table>';
                        childBox.querySelector('.product-container').innerHTML = pTable;
                    });
            } else {
                childBox.classList.add('d-none');
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        });

        // 3. XỬ LÝ CLICK XEM BIẾN THỂ (CẤP 3: HIỆN SIZE/MÀU)
        modalContent.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-get-variants');
            if (!btn) return;

            const row = btn.closest('.product-row');
            const productId = row.getAttribute('data-id');
            const type = row.getAttribute('data-type');
            const variantBox = document.getElementById(`product-detail-${productId}`);

            if (variantBox.classList.contains('d-none')) {
                variantBox.classList.remove('d-none');
                btn.innerText = 'Đóng';

                fetch(`index.php?page=get_variant_data&product_id=${productId}&type=${type}`)
                    .then(res => res.json())
                    .then(variants => {
                        let vHtml = '<div class="row g-2">';
                        variants.forEach(v => {
                            vHtml += `
                            <div class="col-4">
                                <div class="p-2 border rounded text-center small">
                                    <span>Size ${v.size} - ${v.color}</span><br>
                                    <strong>${v.total} đôi</strong>
                                </div>
                            </div>`;
                        });
                        vHtml += '</div>';
                        variantBox.querySelector('.variant-container').innerHTML = vHtml;
                    });
            } else {
                variantBox.classList.add('d-none');
                btn.innerHTML = 'Xem';
            }
        });
    });
</script>