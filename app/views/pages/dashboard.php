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


<!-- sơ đồ kho -->
<div class="row mb-4">
    <div class="col-12 col-md-6">
        <button class="btn btn-outline-light w-100 fw-bold py-3 shadow-sm d-flex justify-content-between align-items-center text-dark rounded-2"
            type="button" data-bs-toggle="collapse" data-bs-target="#mapCollapseArea" id="btnToggleMap"
            style="background: rgba(255,255,255,0.05); border: 1px dashed rgba(255,255,255,0.2);">
            <span class="fs-6 text-dark"><i class="fas fa-map-marked-alt me-2 text-dark"></i> BẤM ĐỂ HIỂN THỊ SƠ ĐỒ KHÔNG GIAN KHO</span>
            <i class="fas fa-chevron-down fs-5 text-dark" id="mapToggleIcon"></i>
        </button>
    </div>
</div>

<div class="collapse" id="mapCollapseArea">
    <div class="card p-4 warehouse-map-box border-0 mb-4 warehouse-map-glass position-relative">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h3 class="text-dark mb-0 fw-bold"><i class="fas fa-map-marked-alt text-dark me-2"></i> Sơ Đồ Kho</h3>

            <div class="map-search-box position-relative flex-grow-1" style="max-width: 400px;">
                <input type="text" id="mapSearchInput" class="form-control custom-map-search" placeholder="Tìm tên giày hoặc brand...">
                <ul id="mapSearchSuggestions" class="list-group map-suggestions-list d-none"></ul>
            </div>

            <div class="d-flex flex-wrap gap-3 small fw-bold align-items-center">
                <span class="badge bg-secondary border border-secondary" style="padding: 10px 15px;">
                    SỨC CHỨA TỔNG: <span class="text-warning fs-6"><?= $warehouseMap['total_load'] ?? 0 ?></span> / <?= $warehouseMap['total_capacity'] ?? 0 ?>
                </span>
                <span class="badge text-dark border border-white" style="background: rgba(255, 255, 255, 0.95); padding: 8px 12px;">Đầy</span>
                <span class="badge text-white border border-white" style="background: rgba(0, 0, 0, 0.7); padding: 8px 12px;">Trống</span>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $displayShelves = $warehouseMap['processedShelves'] ?? [];
            $vDict = $variantDict ?? ($stats['variantDict'] ?? []);

            // BƯỚC 1: TÌM SỐ TẦNG VÀ SỐ Ô LỚN NHẤT TOÀN KHO ĐỂ ĐỒNG BỘ GIAO DIỆN
            $globalMaxTiers = 0;
            $globalMaxSlots = 0;
            foreach ($displayShelves as $s) {
                $layout = $s['layout'];
                foreach (array_keys($layout) as $t) {
                    $globalMaxTiers = max($globalMaxTiers, (int)$t);
                }
                foreach ($layout as $tData) {
                    $globalMaxSlots = max($globalMaxSlots, count($tData));
                }
            }
            if ($globalMaxTiers === 0) $globalMaxTiers = 4;
            if ($globalMaxSlots === 0) $globalMaxSlots = 6;

            // BƯỚC 2: LẶP QUA TỪNG KỆ
            foreach ($displayShelves as $shelf):
                $shelfName = $shelf['shelf_name'];
                $layout = $shelf['layout'];
            ?>
                <div class="col-12 col-xl-6">
                    <div class="shelf-wrapper p-3">
                        <div class="d-flex justify-content-between mb-3 border-bottom border-secondary pb-2">
                            <div>
                                <h4 class="text-white fw-bold mb-1">KỆ <?= $shelfName ?></h4>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($shelf['brand_counts'] as $bName => $bQty): ?>
                                        <span class="badge bg-dark border border-secondary small" style="font-size:10px;"><?= htmlspecialchars($bName) ?>: <?= $bQty ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="text-end text-white">
                                <small class="opacity-50">Sức chứa</small>
                                <div class="fw-bold"><?= $shelf['current_load'] ?> <span class="opacity-50">/ <?= $shelf['shelf_max_capacity'] ?></span></div>
                            </div>
                        </div>

                        <div class="shelf-grid" style="grid-template-columns: 60px repeat(<?= $globalMaxSlots ?>, 1fr);">
                            <?php
                            // LẶP QUA TẦNG (Từ cao xuống thấp)
                            for ($tier = $globalMaxTiers; $tier >= 1; $tier--):
                                echo "<div class='tier-label text-white-50'>T$tier</div>";

                                $slotsInTier = $layout[(string)$tier] ?? [];

                                // LẶP QUA Ô (Từ 01 đến Max)
                                for ($i = 1; $i <= $globalMaxSlots; $i++):
                                    $slotKey = str_pad($i, 2, '0', STR_PAD_LEFT);
                                    $shoesInSlot = $slotsInTier[$slotKey] ?? [];

                                    $slotCode = "{$shelfName}{$tier}-{$slotKey}";
                                    $occupancy = count($shoesInSlot);
                                    $slotMax = (int)$shelf['slot_max'];
                                    $fillPercent = ($slotMax > 0) ? ($occupancy / $slotMax) * 100 : 0;

                                    // LOGIC CHI TIẾT KHI RÊ CHUỘT
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
                                                    <img src='{$imgPath}' class='popover-shoe-img rounded me-3 border border-secondary' style='width: 45px; height: 45px; object-fit: cover;'>
                                                    <div class='text-start lh-sm text-white flex-grow-1'>
                                                        <strong class='d-block text-truncate' style='max-width: 150px;'>{$shoeData['product_name']}</strong>
                                                        <span class='d-block mt-1' style='font-size: 0.8rem; opacity: 0.7;'>Size: {$shoeData['size']} | {$shoeData['color']}</span>
                                                    </div>
                                                    <div class='ms-2 fw-bold text-black bg-white rounded px-2 py-1'>x{$qty}</div>
                                                </div>";
                                            }
                                        }
                                    }
                                    $detailHtml .= "</div>";
                            ?>
                                    <div class="shelf-cell"
                                        style="--fill: <?= $fillPercent ?>%;"
                                        data-code="<?= $slotCode ?>"
                                        data-occupancy="<?= $occupancy ?>"
                                        data-max="<?= $slotMax ?>"
                                        data-detail="<?= htmlspecialchars($detailHtml, ENT_QUOTES) ?>">
                                        <span><?= $slotKey ?></span>
                                    </div>
                            <?php
                                endfor; // Hết lặp ô
                            endfor; // Hết lặp tầng
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script src="assets/js/warehouse_map.js"></script>