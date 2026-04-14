<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="assets/css/report.css">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-4 no-print flex-wrap gap-3">
        <div>
            <h4 class="fw-bold m-0" style="color: #2c3e50;">Báo Cáo Phân Tích Kho Chi Tiết</h4>
            <p class="text-muted small mb-0">Dữ liệu từ <?= date('d/m/Y', strtotime($filter['start'])) ?> đến <?= date('d/m/Y', strtotime($filter['end'])) ?></p>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="index.php" class="d-flex gap-2 bg-white p-2 shadow-sm rounded border">
                <input type="hidden" name="page" value="report">
                <!-- <select name="period" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 130px;">
                    <option value="day" <?= ($filter['period'] == 'day') ? 'selected' : '' ?>>Hôm nay</option>
                    <option value="week" <?= ($filter['period'] == 'week') ? 'selected' : '' ?>>7 ngày qua</option>
                    <option value="month" <?= ($filter['period'] == 'month') ? 'selected' : '' ?>>30 ngày qua</option>
                </select> -->
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $filter['start'] ?>">
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $filter['end'] ?>">
                <button type="submit" class="btn btn-primary btn-sm px-3">Lọc</button>
            </form>
            <button class="btn btn-sm btn-white border shadow-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> In báo cáo
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-custom border-left-primary h-100 mb-0">
                <div class="stat-label">Tổng tồn thực tế</div>
                <div class="stat-value text-dark"><?= number_format($all_stats['total_stock']) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom border-left-success h-100 mb-0">
                <div class="stat-label">Nhập kho (Giai đoạn)</div>
                <div class="stat-value text-primary"><?= number_format($all_stats['period_imports']) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom border-left-danger h-100 mb-0">
                <div class="stat-label">Xuất kho (Giai đoạn)</div>
                <div class="stat-value text-danger"><?= number_format($all_stats['period_exports']) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom border-left-warning h-100 mb-0">
                <div class="stat-label">Cảnh báo nhập hàng</div>
                <div class="stat-value text-danger"><?= $all_stats['shortage_count'] ?></div>
            </div>
        </div>
    </div>

    <div class="card-custom mb-4">
        <h6 class="section-title">Lưu Lượng Giao Dịch Chi Tiết Theo Ngày</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Ngày hoạt động</th>
                        <th class="text-center">Tổng Nhập</th>
                        <th class="text-center">Tổng Xuất</th>
                        <th class="text-center">Số giao dịch</th>
                        <th class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summary_data)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">Không có dữ liệu</td>
                        </tr>
                        <?php else: foreach ($summary_data as $row): ?>
                            <tr>
                                <td class="fw-bold"><?= date('d/m/Y', strtotime($row['work_date'])) ?></td>
                                <td class="text-center text-primary fw-bold">+ <?= number_format($row['total_import']) ?></td>
                                <td class="text-center text-danger fw-bold">- <?= number_format($row['total_export']) ?></td>
                                <td class="text-center text-muted"><?= $row['total_transactions'] ?> lượt</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-dark rounded-pill px-3 btn-open-report-detail"
                                        data-date="<?= $row['work_date'] ?>">
                                        <i class="fas fa-eye me-1"></i> Xem chi tiết ngày
                                    </button>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card-custom h-100">
                <h6 class="section-title">Top 5 Ngày Hoạt Động Cao Điểm</h6>
                <div class="chart-container"><canvas id="daysChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card-custom h-100">
                <h6 class="section-title">Cơ Cấu Thương Hiệu</h6>
                <div class="chart-container"><canvas id="brandChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card-custom h-100">
                <h6 class="section-title">Sản Phẩm Giao Dịch Nhiều Nhất (Flow)</h6>
                <div class="chart-container"><canvas id="productFlowChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card-custom h-100">
                <h6 class="section-title">Top 3 Nhân Viên Giao Dịch</h6>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th width="60">Hạng</th>
                                <th>Nhân viên</th>
                                <th class="text-center">Số giao dịch</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_perf as $idx => $s): ?>
                                <tr>
                                    <td><span class="badge bg-primary rounded-pill px-2 py-1"><?= $idx + 1 ?></span></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($s['full_name']) ?></td>
                                    <td class="text-center fw-bold text-primary"><?= number_format($s['total_tx']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom mb-4">
        <h6 class="section-title">Chi Tiết Biến Thể Thuộc Top 5 Sản Phẩm Lưu Chuyển Mạnh Nhất</h6>
        <?php
        $groupedFlow = [];
        foreach ($variant_flow as $row) {
            $groupedFlow[$row['product_name']]['total_vol'] = $row['total_vol'] ?? ($row['imp'] + $row['exp']);
            $groupedFlow[$row['product_name']]['variants'][] = $row;
        }
        ?>
        <div class="top-product-grid">
            <?php foreach ($groupedFlow as $pName => $pData): ?>
                <div class="top-p-card shadow-sm">
                    <div class="top-p-header">
                        <span class="text-truncate" style="max-width: 70%;" title="<?= htmlspecialchars($pName) ?>">
                            <?= htmlspecialchars($pName) ?>
                        </span>
                        <span class="badge-vol">Tổng: <?= number_format($pData['total_vol']) ?></span>
                    </div>
                    <div class="top-p-body">
                        <?php foreach ($pData['variants'] as $v):
                            $total_v = $v['imp'] + $v['exp'];
                            $pct_imp = $total_v > 0 ? ($v['imp'] / $total_v) * 100 : 0;
                            $pct_exp = $total_v > 0 ? ($v['exp'] / $total_v) * 100 : 0;
                        ?>
                            <div class="v-item">
                                <div class="v-item-title">
                                    <span>Sz: <?= $v['size'] ?> | <?= htmlspecialchars($v['color']) ?></span>
                                    <span class="text-muted small">Flow: <?= number_format($total_v) ?></span>
                                </div>
                                <div class="progress-stacked">
                                    <div class="progress-imp" style="width: <?= $pct_imp ?>%;"></div>
                                    <div class="progress-exp" style="width: <?= $pct_exp ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card-custom">
        <?php
        $groupedInventory = [];
        $uniqueBrands = [];
        foreach ($inventory as $item) {
            $brand = $item['category_name'];
            $groupedInventory[$item['product_name']]['brand'] = $brand;
            $groupedInventory[$item['product_name']]['variants'][] = $item;
            if (!in_array($brand, $uniqueBrands)) $uniqueBrands[] = $brand;
        }
        sort($uniqueBrands);
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="section-title m-0" id="inventoryTitle" style="border: none;">Phân Tích Tồn Kho Hiện Tại</h6>

            <div class="d-flex align-items-center">
                <label for="brandFilter" class="form-label mb-0 me-2 text-muted small fw-bold">Lọc:</label>
                <select id="brandFilter" class="form-select form-select-sm shadow-sm" style="width: auto;">
                    <option value="ALL">Tất cả thương hiệu</option>
                    <?php foreach ($uniqueBrands as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="inventory-grid" id="inventoryGrid">
            <?php foreach ($groupedInventory as $pName => $pData): ?>
                <div class="inv-product-card" data-brand="<?= htmlspecialchars($pData['brand']) ?>">
                    <div class="inv-header">
                        <strong class="text-truncate"><?= htmlspecialchars($pName) ?></strong>
                        <span class="inv-brand-badge"><?= htmlspecialchars($pData['brand']) ?></span>
                    </div>
                    <div class="inv-body">
                        <?php foreach ($pData['variants'] as $var): ?>
                            <div class="stock-chip <?= $var['stock'] < 10 ? 'danger' : '' ?>">
                                Sz <?= $var['size'] ?>: <b class="variant-stock"><?= $var['stock'] ?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- <div class="modal fade" id="modalReportDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-list-alt me-2"></i>Chi tiết vận động hàng hóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h6 id="reportDetailDate" class="fw-bold mb-0 text-primary"></h6>
                    <span class="badge bg-primary px-3" id="totalItemsCount">0 sản phẩm</span>
                </div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-striped mb-0">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>Thời gian</th>
                                <th>Loại</th>
                                <th>Hãng</th>
                                <th>Tên sản phẩm</th>
                                <th>Biến thể</th>
                                <th class="text-center">Số lượng</th>
                                <th>Nhân viên</th>
                            </tr>
                        </thead>
                        <tbody id="reportDetailBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> -->

    <script>
        // 1. DATA VÀ CHART (Giữ nguyên logic của bồ)
        const colorPrimary = '#4e73df';
        const colorSuccess = '#1cc88a';
        const dayData = <?= json_encode($top_5_days) ?>;
        const brandData = <?= json_encode($brand_dist) ?>;
        const flowData = <?= json_encode($product_flow) ?>;

        // Tìm đến đoạn vẽ 'daysChart' và sửa lại như sau:
        if (document.getElementById('daysChart') && dayData.length > 0) {
            new Chart(document.getElementById('daysChart'), {
                type: 'bar',
                data: {
                    // Nhãn là các ngày
                    labels: dayData.map(d => new Date(d.work_date).toLocaleDateString('vi-VN')),
                    datasets: [{
                            label: 'Nhập kho',
                            data: dayData.map(d => d.total_import),
                            backgroundColor: '#4e73df', // Màu xanh dương
                            borderRadius: 4,
                            barPercentage: 0.8,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'Xuất kho',
                            data: dayData.map(d => d.total_export),
                            backgroundColor: '#1cc88a', // Màu xanh lá (hoặc đỏ #e74a3b tùy bồ chọn)
                            borderRadius: 4,
                            barPercentage: 0.8,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom' // Hiện chú thích ở dưới cho dễ nhìn
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Số lượng (đôi)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        if (document.getElementById('brandChart') && brandData.length > 0) {
            new Chart(document.getElementById('brandChart'), {
                type: 'doughnut',
                data: {
                    labels: brandData.map(d => d.brand),
                    datasets: [{
                        data: brandData.map(d => d.total_stock),
                        backgroundColor: [colorPrimary, colorSuccess, '#36b9cc', '#f6c23e', '#e74a3b']
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '70%'
                }
            });
        }

        if (document.getElementById('productFlowChart') && flowData.length > 0) {
            new Chart(document.getElementById('productFlowChart'), {
                type: 'bar',
                data: {
                    labels: flowData.map(p => p.product_name),
                    datasets: [{
                            label: 'Nhập',
                            data: flowData.map(p => p.total_import),
                            backgroundColor: colorPrimary
                        },
                        {
                            label: 'Xuất',
                            data: flowData.map(p => p.total_export),
                            backgroundColor: colorSuccess
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false
                }
            });
        }

        // 2. LOGIC AJAX CHO MODAL CHI TIẾT (NEW)
        document.querySelectorAll('.btn-open-report-detail').forEach(btn => {
            btn.addEventListener('click', async function() {
                const date = this.dataset.date;
                const modal = new bootstrap.Modal(document.getElementById('modalReportDetail'));
                const tableBody = document.getElementById('reportDetailBody');

                document.getElementById('reportDetailDate').innerText = "Chi tiết ngày: " + new Date(date).toLocaleDateString('vi-VN');
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Đang tải dữ liệu...</td></tr>';
                modal.show();

                try {
                    const response = await fetch(`index.php?page=report-detail&date=${date}`);
                    const data = await response.json();
                    if (data.length > 0) {
                        let html = '';
                        data.forEach(item => {
                            const time = new Date(item.created_at).toLocaleTimeString('vi-VN', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            html += `
                        <tr>
                            <td class="small text-muted">${time}</td>
                            <td><span class="badge ${item.transaction_type === 'IMPORT' ? 'bg-success' : 'bg-danger'}">${item.transaction_type}</span></td>
                            <td class="fw-bold">${item.brand}</td>
                            <td>${item.product_name}</td>
                            <td>Sz: ${item.size} | ${item.color}</td>
                            <td class="text-center fw-bold text-dark">${item.quantity} đôi</td>
                            <td class="small text-muted">${item.staff}</td>
                        </tr>`;
                        });
                        tableBody.innerHTML = html;
                        document.getElementById('totalItemsCount').innerText = data.length + " lượt giao dịch";
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Không có chi tiết biến thể nào.</td></tr>';
                    }
                } catch (error) {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Lỗi kết nối máy chủ hoặc dữ liệu không hợp lệ.</td></tr>';
                }
            });
        });

        // 3. BỘ LỌC BRAND (Giữ nguyên logic của bồ)
        document.getElementById('brandFilter')?.addEventListener('change', function() {
            const brand = this.value;
            let count = 0;
            document.querySelectorAll('.inv-product-card').forEach(card => {
                const show = (brand === 'ALL' || card.dataset.brand === brand);
                card.style.display = show ? '' : 'none';
                if (show) count++;
            });
            document.getElementById('noDataMessage').style.display = count === 0 ? 'block' : 'none';
        });



        // hiện tồn kho theo brand
        document.addEventListener('DOMContentLoaded', function() {
            const brandFilter = document.getElementById('brandFilter');
            const inventoryTitle = document.getElementById('inventoryTitle');
            const productCards = document.querySelectorAll('.inv-product-card');

            function updateInventoryDisplay() {
                const selectedBrand = brandFilter.value;
                let totalStock = 0;

                productCards.forEach(card => {
                    const cardBrand = card.getAttribute('data-brand');

                    if (selectedBrand === 'ALL' || cardBrand === selectedBrand) {
                        // Hiển thị card
                        card.style.display = 'block';

                        // Tính tổng tồn kho của các biến thể trong card này
                        const variants = card.querySelectorAll('.variant-stock');
                        variants.forEach(v => {
                            totalStock += parseInt(v.innerText);
                        });
                    } else {
                        // Ẩn card không thuộc hãng được chọn
                        card.style.display = 'none';
                    }
                });

                // Cập nhật nội dung tiêu đề
                if (selectedBrand === 'ALL') {
                    inventoryTitle.innerHTML = `
        <i class="bi bi-box-seam me-2"></i>Phân Tích Tồn Kho 
        <span class="ms-2 badge bg-primary-soft text-primary fw-bold" style="font-size: 0.9rem; padding: 5px 12px; border-radius: 50px; background-color: #e7f1ff;">
            Tất cả: ${totalStock.toLocaleString()} đôi
        </span>
    `;
                } else {
                    inventoryTitle.innerHTML = `
        Phân Tích Tồn Kho 
        <span class="ms-2 text-muted fw-normal" style="font-size: 0.85rem;">|</span>
        <span class="ms-2 badge bg-light text-dark border fw-medium" style="padding: 5px 12px; border-radius: 6px;">
            Hãng: <b class="text-primary">${selectedBrand}</b>
        </span>
        <span class="ms-2 badge bg-light text-dark border fw-medium" style="padding: 5px 12px; border-radius: 6px;">
            Tổng tồn: <b class="text-danger">${totalStock.toLocaleString()} đôi</b>
        </span>
    `;
                }
            }

            // Chạy lần đầu khi load trang để hiện tổng số lượng ban đầu
            updateInventoryDisplay();

            // Lắng nghe sự kiện thay đổi bộ lọc
            brandFilter.addEventListener('change', updateInventoryDisplay);
        });
    </script>