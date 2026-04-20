<?php include __DIR__ . '/../layouts/topbar.php'; ?>
<link rel="stylesheet" href="assets/css/warehouse_map.css">
<!-- sơ đồ kho -->
<div class="card p-4 warehouse-map-box border-0 mb-4 warehouse-map-glass position-relative">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h3 class="text-dark mb-0 fw-bold"><i class="fas fa-map-marked-alt text-dark me-2"></i> Sơ Đồ Kho</h3>

        <div class="map-search-box position-relative flex-grow-1" style="max-width: 400px;">
            <input type="text" id="mapSearchInput" class="form-control custom-map-search" placeholder="Tìm tên giày hoặc brand...">
            <ul id="mapSearchSuggestions" class="list-group map-suggestions-list d-none"></ul>
        </div>

        <div class="d-flex flex-wrap gap-3 small fw-bold align-items-center">
            <span class="badge bg-secondary border border-secondary" style="padding: 10px 15px;">
                SỨC CHỨA TỔNG: <span class="text-warning fs-6"><?= $total_load ?? 0 ?></span> / <?= $total_capacity ?? 0 ?>
            </span>
            <span class="badge text-dark border border-white" style="background: rgba(255, 255, 255, 0.95); padding: 8px 12px;">Đầy</span>
            <span class="badge text-white border border-white" style="background: rgba(0, 0, 0, 0.7); padding: 8px 12px;">Trống</span>
        </div>
    </div>

    <div class="row g-4">
        <?php
        $displayShelves = $processedShelves ?? [];
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
                        for ($tier = $globalMaxTiers; $tier >= 1; $tier--):
                            echo "<div class='tier-label text-white-50'>T$tier</div>";
                            $slotsInTier = $layout[(string)$tier] ?? [];

                            for ($i = 1; $i <= $globalMaxSlots; $i++):
                                $slotKey = str_pad($i, 2, '0', STR_PAD_LEFT);
                                $shoesInSlot = $slotsInTier[$slotKey] ?? [];
                                $slotCode = "{$shelfName}{$tier}-{$slotKey}";
                                $occupancy = count($shoesInSlot);
                                $slotMax = (int)$shelf['slot_max'];
                                $fillPercent = ($slotMax > 0) ? ($occupancy / $slotMax) * 100 : 0;

                                // --- BẮT ĐẦU LOGIC CHI TIẾT ---
                                $detailHtml = "<div class='popover-inventory shadow-lg'>";
                                $detailHtml .= "<div class='d-flex justify-content-between align-items-center border-bottom border-secondary pb-2 mb-3'>
                                <span class='text-white fw-bold fs-5'>{$slotCode}</span>
                                <span class='badge bg-white text-dark py-1'>{$occupancy}/{$slotMax}</span>
                            </div>";

                                if ($occupancy == 0) {
                                    $detailHtml .= "<p class='text-white mb-0 text-center fs-6 py-2'>Ô trống</p>";
                                } else {
                                    $groupedShoes = array_count_values($shoesInSlot);
                                    foreach ($groupedShoes as $v_id => $qty) {
                                        $shoeData = $vDict[$v_id] ?? null;
                                        if ($shoeData) {
                                            $imgPath = "assets/img_product/" . htmlspecialchars($shoeData['product_image']);
                                            $proId = $shoeData['product_id'] ?? 0;
                                            $catId = $shoeData['category_id'] ?? 0;
                                            $targetLink = "index.php?page=products&category_id={$catId}&open_modal={$proId}&highlight_vid={$v_id}";

                                            $detailHtml .= "
                                                <div class='d-flex align-items-center mb-3 border-bottom pb-2' style='border-color: rgba(255,255,255,0.1) !important;'>
                                                    <a href='{$targetLink}' class='flex-shrink-0 me-2'>
                                                        <img src='{$imgPath}' class='popover-shoe-img rounded border border-secondary' 
                                                            style='width: 30px; height: 30px; object-fit: cover;'>
                                                    </a>
                                                    
                                                    <div class='text-start lh-sm text-white flex-grow-1' style='min-width: 0;'>
                                                        <a href='{$targetLink}' class='text-white text-decoration-none'>
                                                            <strong class='d-block text-truncate' style='font-size: 13px; max-width: 140px;'>
                                                                {$shoeData['product_name']}
                                                            </strong>
                                                        </a>
                                                        <span class='d-block mt-1' style='font-size: 12px; opacity: 0.7;'>
                                                            Sz: {$shoeData['size']} | {$shoeData['color']}
                                                        </span>
                                                    </div>
                                                    
                                                    <div class='ms-2 fw-bold text-black bg-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0' 
                                                        style='width: 25px; height: 25px; font-size: 12px;'>
                                                        {$qty}
                                                    </div>
                                                </div>";
                                        }
                                    }
                                }
                                $detailHtml .= "</div>";
                                // --- KẾT THÚC LOGIC CHI TIẾT ---
                        ?>
                                <div class="shelf-cell"
                                    style="--fill: <?= $fillPercent ?>%;"
                                    data-code="<?= $slotCode ?>"
                                    data-occupancy="<?= $occupancy ?>"
                                    data-max="<?= $slotMax ?>">
                                    <span><?= $slotKey ?></span>

                                    <?= $detailHtml ?>
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

<script src="assets/js/warehouse_map.js"></script>