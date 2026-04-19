<link rel="stylesheet" href="assets/css/dashboard.css">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<h1>Tổng quan Kho</h1>
<p class="text-muted mb-4">Dữ liệu cập nhật thời gian thực</p>

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

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Chi tiết dữ liệu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalContent"></div>
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
<?php else: ?>
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <i class="fas fa-info-circle me-2"></i> Chào <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'bạn') ?></strong>, hãy kiểm tra bản đồ kho bên dưới để bắt đầu nhặt hàng nhé!
    </div>
<?php endif; ?>


<!-- heatmap -->
<div class="row mb-4">
    <div class="col-12 col-md-6">
        <button class="btn btn-outline-light w-100 fw-bold py-3 shadow-sm d-flex justify-content-between align-items-center text-dark rounded-2" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#mapCollapseArea" 
                aria-expanded="false" 
                aria-controls="mapCollapseArea"
                id="btnToggleMap">
            <span class="fs-6 text-dark"><i class="fas fa-map-marked-alt me-2"></i> BẤM ĐỂ HIỂN THỊ SƠ ĐỒ KHÔNG GIAN KHO</span>
            <i class="fas fa-chevron-down fs-5 text-dark" id="mapToggleIcon"></i>
        </button>
    </div>
</div>

<div class="collapse" id="mapCollapseArea">
    <div class="card p-4 warehouse-map-box border-0 mb-4 warehouse-map-glass position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-dark"><i class="fas fa-map-marked-alt text-dark me-2"></i> Sơ Đồ Không Gian Kho</h3>
            <div class="d-flex gap-3 small fw-bold">
                <span class="badge text-dark border border-white" style="background: rgba(255, 255, 255, 0.95); padding: 8px 12px;">Đầy (4/4)</span>
                <span class="badge text-white border border-white" style="background: linear-gradient(to right, rgba(255,255,255,0.9) 50%, rgba(0,0,0,0.7) 50%); padding: 8px 12px; text-shadow: 1px 1px 2px #000;">Còn chỗ</span>
                <span class="badge text-white border border-white" style="background: rgba(0, 0, 0, 0.7); padding: 8px 12px;">Trống (0/4)</span>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $shelvesList = $stats['shelvesData'] ?? ($shelvesData ?? []);
            $vDict = $stats['variantDict'] ?? ($variantDict ?? []);

            foreach ($shelvesList as $shelf):
                $shelfName = $shelf['shelf_name'];
                $layout = json_decode($shelf['layout'], true) ?: [];
            ?>
                <div class="col-12 col-xl-6">
                    <div class="shelf-wrapper p-3">
                        <h5 class="text-center shelf-title">KỆ <?= $shelfName ?></h5>
                        <div class="shelf-grid">
                            <?php
                            for ($tier = 4; $tier >= 1; $tier--):
                                echo "<div class='tier-label'>Tầng {$tier}</div>";
                                for ($slot = 1; $slot <= 6; $slot++):
                                    $slotKey = str_pad($slot, 2, '0', STR_PAD_LEFT);
                                    $slotCode = "{$shelfName}{$tier}-{$slotKey}";
                                    $shoesInSlot = $layout[(string)$tier][$slotKey] ?? [];
                                    $occupancy = count($shoesInSlot);
                                    $fillPercent = ($occupancy / 4) * 100;

                                    // Xây dựng Data ẩn thay vì gắn Popover trực tiếp
                                    $detailHtml = "<div class='popover-inventory'>";
                                    if ($occupancy == 0) {
                                        $detailHtml .= "<p class='text-white mb-0 text-center fs-6'>Ô trống</p>";
                                    } else {
                                        $groupedShoes = array_count_values($shoesInSlot);
                                        foreach ($groupedShoes as $v_id => $qty) {
                                            $shoeData = $vDict[$v_id] ?? null;
                                            if ($shoeData) {
                                                $imgPath = "assets/img_product/" . htmlspecialchars($shoeData['product_image']);
                                                $detailHtml .= "
                                                <div class='d-flex align-items-center mb-3 border-bottom pb-3' style='border-color: rgba(255,255,255,0.1) !important;'>
                                                    <img src='{$imgPath}' class='popover-shoe-img rounded me-3 border border-secondary'>
                                                    <div class='text-start lh-sm text-white flex-grow-1'>
                                                        <strong class='d-block text-truncate popover-shoe-name'>{$shoeData['product_name']}</strong>
                                                        <span class='d-block mt-1 popover-shoe-detail'>Size: {$shoeData['size']} | {$shoeData['color']}</span>
                                                    </div>
                                                    <div class='ms-2 fw-bold text-black bg-white rounded popover-shoe-qty'>
                                                        x{$qty}
                                                    </div>
                                                </div>";
                                            }
                                        }
                                    }
                                    $detailHtml .= "</div>";
                            ?>
                                    <div class="shelf-cell"
                                        style="--fill: <?= $fillPercent ?>%; cursor: pointer;"
                                        data-code="<?= $slotCode ?>"
                                        data-occupancy="<?= $occupancy ?>"
                                        data-detail="<?= htmlspecialchars($detailHtml, ENT_QUOTES) ?>">
                                        <span><?= $slotKey ?></span>
                                    </div>
                            <?php
                                endfor;
                            endfor;
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="assets/js/warehouse_map.js"></script>

