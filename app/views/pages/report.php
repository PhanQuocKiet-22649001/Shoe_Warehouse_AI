<link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* =========================================
       CSS CHUNG & LAYOUT
       ========================================= */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f4f7f6;
        color: #333;
    }

    .card-custom {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #4e73df;
        margin-bottom: 15px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
    }

    .section-title::after {
        content: "";
        flex: 1;
        height: 1px;
        background: #e3e6f0;
        margin-left: 15px;
    }

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* =========================================
       CSS CHO KPI CARDS
       ========================================= */
    .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-top: 5px;
    }

    .border-left-primary {
        border-left: 4px solid #4e73df;
    }

    .border-left-success {
        border-left: 4px solid #1cc88a;
    }

    .border-left-warning {
        border-left: 4px solid #f6c23e;
    }

    .border-left-danger {
        border-left: 4px solid #e74a3b;
    }

    /* =========================================
       CSS CHO BẢNG (TABLES)
       ========================================= */
    .table thead th {
        background: #f8f9fc;
        font-weight: 600;
        color: #4e73df;
        border-bottom: 2px solid #e3e6f0;
        font-size: 0.85rem;
    }

    .table td {
        vertical-align: middle;
        font-size: 0.9rem;
        border-bottom: 1px solid #e3e6f0;
    }

    /* =========================================
       CSS BẢNG BIẾN THỂ CỦA TOP 5 SẢN PHẨM
       ========================================= */
    .top-product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .top-p-card {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        overflow: hidden;
    }

    .top-p-header {
        background: #4e73df;
        color: #fff;
        padding: 12px 15px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .top-p-header .badge-vol {
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
    }

    .top-p-body {
        padding: 0;
    }

    .v-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f3f9;
    }

    .v-item:last-child {
        border-bottom: none;
    }

    .v-item-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
    }

    .v-item-stats {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 5px;
    }

    .progress-stacked {
        display: flex;
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 4px;
        background-color: #eaecf4;
    }

    .progress-imp {
        background-color: #4e73df;
    }

    .progress-exp {
        background-color: #1cc88a;
    }

    /* =========================================
       CSS TỒN KHO CHI TIẾT (GROUPING CARDS)
       ========================================= */
    .inventory-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        max-height: 500px;
        overflow-y: auto;
        padding-right: 10px;
    }

    .inv-product-card {
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        background: #fff;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s;
    }

    .inv-product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    .inv-header {
        background: #f8f9fc;
        padding: 10px 15px;
        border-bottom: 1px solid #e3e6f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 8px 8px 0 0;
    }

    .inv-header strong {
        font-size: 0.9rem;
        color: #333;
    }

    .inv-brand-badge {
        font-size: 0.65rem;
        background: #eaecf4;
        color: #5a5c69;
        padding: 3px 8px;
        border-radius: 12px;
        font-weight: 600;
        border: 1px solid #d1d3e2;
    }

    .inv-body {
        padding: 15px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .stock-chip {
        border: 1px solid #d1d3e2;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        background: #fff;
        display: flex;
        align-items: center;
        gap: 5px;
        color: #5a5c69;
    }

    .stock-chip b {
        color: #333;
        font-size: 0.85rem;
    }

    .stock-chip.danger {
        border-color: #e74a3b;
        background: #fff1f0;
        color: #e74a3b;
    }

    .stock-chip.danger b {
        color: #e74a3b;
    }

    /* =========================================
       CSS KHI IN ẤN (PRINT)
       ========================================= */
    @media print {
        .no-print {
            display: none !important;
        }

        .card-custom {
            box-shadow: none;
            border: 1px solid #ccc;
            break-inside: avoid;
        }

        .inventory-grid {
            max-height: none;
            overflow: visible;
            display: block;
        }

        .inv-product-card {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h4 class="fw-bold m-0" style="color: #2c3e50;">Báo Cáo Phân Tích Kho Chi Tiết</h4>
        <button class="btn btn-sm btn-white border shadow-sm" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Xuất bản in
        </button>
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
                <div class="stat-label">Xuất kho (Tháng)</div>
                <div class="stat-value text-success"><?= number_format($all_stats['monthly_exports']) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom border-left-warning h-100 mb-0">
                <div class="stat-label">Nhân viên tích cực</div>
                <div class="stat-value text-dark" style="font-size: 1.1rem;"><?= htmlspecialchars($staff_perf[0]['full_name'] ?? 'N/A') ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom border-left-danger h-100 mb-0">
                <div class="stat-label">Cảnh báo nhập hàng</div>
                <div class="stat-value text-danger"><?= $all_stats['shortage_count'] ?></div>
            </div>
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

    <div class="card-custom">
        <h6 class="section-title">Chi Tiết Biến Thể Thuộc Top 5 Sản Phẩm Lưu Chuyển Mạnh Nhất</h6>
        <?php
        // Logic gom nhóm biến thể theo sản phẩm
        $groupedFlow = [];
        foreach ($variant_flow as $row) {
            // Fallback total_vol nếu query cũ chưa có
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
                                    <span class="text-muted" style="font-size: 0.75rem; font-weight: 400;">Flow: <?= number_format($total_v) ?></span>
                                </div>
                                <div class="progress-stacked" title="Nhập: <?= $v['imp'] ?> | Xuất: <?= $v['exp'] ?>">
                                    <div class="progress-imp" style="width: <?= $pct_imp ?>%;"></div>
                                    <div class="progress-exp" style="width: <?= $pct_exp ?>%;"></div>
                                </div>
                                <div class="v-item-stats">
                                    <span class="text-primary">Nhập: <?= number_format($v['imp']) ?></span>
                                    <span class="text-success">Xuất: <?= number_format($v['exp']) ?></span>
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
        // 1. Logic gom nhóm tồn kho theo sản phẩm & Lấy danh sách Thương hiệu
        $groupedInventory = [];
        $uniqueBrands = []; // Mảng chứa các thương hiệu duy nhất để đưa vào Combobox

        foreach ($inventory as $item) {
            $brand = $item['category_name'];
            $groupedInventory[$item['product_name']]['brand'] = $brand;
            $groupedInventory[$item['product_name']]['variants'][] = $item;

            // Nếu thương hiệu chưa có trong mảng thì thêm vào
            if (!in_array($brand, $uniqueBrands)) {
                $uniqueBrands[] = $brand;
            }
        }
        // Sắp xếp danh sách thương hiệu theo bảng chữ cái A-Z
        sort($uniqueBrands);
        ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="section-title m-0" style="border: none;">
                Phân Tích Tồn Kho Hiện Tại
            </h6>

            <div class="d-flex align-items-center">
                <label for="brandFilter" class="form-label mb-0 me-2 text-muted" style="font-size: 0.85rem; font-weight: 600;">Lọc theo:</label>
                <select id="brandFilter" class="form-select form-select-sm border shadow-sm" style="width: auto; min-width: 150px; cursor: pointer;">
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
                        <strong class="text-truncate" style="max-width: 75%;" title="<?= htmlspecialchars($pName) ?>">
                            <?= htmlspecialchars($pName) ?>
                        </strong>
                        <span class="inv-brand-badge"><?= htmlspecialchars($pData['brand']) ?></span>
                    </div>
                    <div class="inv-body">
                        <?php foreach ($pData['variants'] as $var): ?>
                            <div class="stock-chip <?= $var['stock'] < 10 ? 'danger' : '' ?>" title="Màu: <?= htmlspecialchars($var['color']) ?>">
                                Sz <?= $var['size'] ?>: <b><?= $var['stock'] ?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="noDataMessage" class="text-center text-muted py-4" style="display: none;">
            <i class="fas fa-box-open mb-2" style="font-size: 2rem; color: #ccc;"></i>
            <p class="mb-0">Không có sản phẩm nào thuộc thương hiệu này.</p>
        </div>
</div>


<script>
    // Config màu sắc chuẩn Bootstrap / SB Admin
    const colorPrimary = '#4e73df';
    const colorSuccess = '#1cc88a';
    const colorInfo = '#36b9cc';
    const colorWarning = '#f6c23e';
    const colorDanger = '#e74a3b';
    const colorSecondary = '#858796';

    // Dữ liệu từ PHP truyền sang JS
    const dayData = <?= json_encode($top_5_days) ?>;
    const brandData = <?= json_encode($brand_dist) ?>;
    const flowData = <?= json_encode($product_flow) ?>;

    // 1. Biểu đồ Top 5 Ngày (Cột Đôi)
    if (document.getElementById('daysChart') && dayData && dayData.length > 0) {
        new Chart(document.getElementById('daysChart'), {
            type: 'bar',
            data: {
                labels: dayData.map(d => new Date(d.work_date).toLocaleDateString('vi-VN')),
                datasets: [{
                        label: 'Nhập kho',
                        data: dayData.map(d => d.total_import),
                        backgroundColor: colorPrimary,
                        borderRadius: 4
                    },
                    {
                        label: 'Xuất kho',
                        data: dayData.map(d => d.total_export),
                        backgroundColor: colorSuccess,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // 2. Biểu đồ Cơ Cấu Thương Hiệu (Doughnut)
    if (document.getElementById('brandChart') && brandData && brandData.length > 0) {
        new Chart(document.getElementById('brandChart'), {
            type: 'doughnut',
            data: {
                labels: brandData.map(d => d.brand),
                datasets: [{
                    data: brandData.map(d => d.total_stock),
                    backgroundColor: [colorPrimary, colorSuccess, colorInfo, colorWarning, colorDanger, colorSecondary],
                    hoverBorderColor: "rgba(234, 236, 244, 1)"
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    }

    // 3. Biểu đồ Sản phẩm Flow (Cột Ngang)
    if (document.getElementById('productFlowChart') && flowData && flowData.length > 0) {
        new Chart(document.getElementById('productFlowChart'), {
            type: 'bar',
            data: {
                labels: flowData.map(p => p.product_name),
                datasets: [{
                        label: 'Nhập',
                        data: flowData.map(p => p.total_import),
                        backgroundColor: colorPrimary,
                        borderRadius: 4
                    },
                    {
                        label: 'Xuất',
                        data: flowData.map(p => p.total_export),
                        backgroundColor: colorSuccess,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }


    // ==========================================
    // LOGIC BỘ LỌC TỒN KHO THEO BRAND (CLIENT-SIDE)
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        const brandFilter = document.getElementById('brandFilter');
        const productCards = document.querySelectorAll('.inv-product-card');
        const noDataMessage = document.getElementById('noDataMessage');

        if (brandFilter) {
            brandFilter.addEventListener('change', function() {
                const selectedBrand = this.value;
                let visibleCount = 0;

                productCards.forEach(card => {
                    // Lấy giá trị data-brand của thẻ hiện tại
                    const cardBrand = card.getAttribute('data-brand');

                    // Kiểm tra điều kiện: Nếu chọn ALL hoặc brand khớp với lựa chọn
                    if (selectedBrand === 'ALL' || cardBrand === selectedBrand) {
                        card.style.display = ''; // Trả lại CSS mặc định (hiện)
                        visibleCount++;
                    } else {
                        card.style.display = 'none'; // Ẩn thẻ
                    }
                });

                // Nếu không có thẻ nào hiện lên, hiển thị thông báo "Không có dữ liệu"
                if (visibleCount === 0) {
                    noDataMessage.style.display = 'block';
                } else {
                    noDataMessage.style.display = 'none';
                }
            });
        }
    });
</script>