<?php
$type = $type ?? 'IMPORT';
$staffs = $staffs ?? [];
$brands = $brands ?? [];
$suggestions = $suggestions ?? [];
$auto_code = $auto_code ?? '';

// Nhóm đề xuất theo Brand
$groupedSuggestions = [];
foreach ($suggestions as $s) {
    $groupedSuggestions[$s['brand']][] = $s;
}
?>

<div class="container-fluid mt-4">
    <!-- Khu vực Đề xuất Nhập hàng (Dropdown theo Brand) -->
    <?php if ($type === 'IMPORT' && !empty($groupedSuggestions)): ?>
    <div class="accordion mb-4 shadow-sm" id="suggestionAccordion">
        <div class="accordion-item border-warning">
            <h2 class="accordion-header">
                <button class="accordion-button bg-light text-danger fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSuggestions">
                    <i class="fas fa-exclamation-triangle me-2"></i> CẢNH BÁO HẾT HÀNG - ĐỀ XUẤT NHẬP (Nhấn để xem chi tiết theo Brand)
                </button>
            </h2>
            <div id="collapseSuggestions" class="accordion-collapse collapse" data-bs-parent="#suggestionAccordion">
                <div class="accordion-body p-2">
                    <div class="row g-2">
                        <?php foreach ($groupedSuggestions as $brandName => $items): ?>
                            <div class="col-md-4">
                                <div class="dropdown">
                                    <button class="btn btn-outline-dark btn-sm dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown">
                                        <span>Hãng: <strong><?= $brandName ?></strong></span>
                                        <span class="badge bg-danger"><?= count($items) ?></span>
                                    </button>
                                    <ul class="dropdown-menu shadow w-100 p-2" style="max-height: 300px; overflow-y: auto;">
                                        <?php foreach ($items as $item): ?>
                                            <li class="d-flex align-items-center mb-2 border-bottom pb-1">
                                                <img src="assets/img_product/<?= $item['product_image'] ?? 'default.jpg' ?>" 
                                                     style="width: 40px; height: 40px; object-fit: cover;" class="rounded me-2">
                                                <div style="font-size: 0.8rem;">
                                                    <div class="fw-bold"><?= $item['product_name'] ?></div>
                                                    <div class="text-muted">Size: <?= $item['size'] ?> | Tồn: <span class="text-danger"><?= $item['stock'] ?></span></div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
            <h4 class="mb-0 text-primary fw-bold">TẠO PHIẾU <?= $type === 'IMPORT' ? 'NHẬP KHO' : 'XUẤT KHO' ?></h4>
        </div>
        <div class="card-body">
            <form id="ticketForm" method="POST" action="index.php?page=ticket_create">
                <input type="hidden" name="ticket_type" value="<?= $type ?>">
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-success">Mã Phiếu (Hệ thống tự sinh)</label>
                        <input type="text" name="ticket_code" class="form-control bg-light fw-bold text-success" value="<?= $auto_code ?>" readonly required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Mã Lô hàng *</label>
                        <input type="text" name="batch_code" class="form-control" placeholder="Bắt buộc nhập mã lô..." required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nhân viên thực hiện *</label>
                        <select name="staff_id" class="form-select" required>
                            <option value="">-- Chọn nhân viên --</option>
                            <?php foreach ($staffs as $staff): ?>
                                <option value="<?= $staff['user_id'] ?>"><?= $staff['full_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Chi tiết Hàng hóa</h5>
                    <button type="button" id="addRow" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-plus"></i> Thêm dòng mới
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="detailTable">
                        <thead class="table-dark text-center">
                            <tr>
                                <th width="120">Ảnh</th>
                                <th width="180">Hãng (Brand)</th>
                                <th>Mẫu Giày (Model)</th>
                                <th width="300">Biến thể</th>
                                <th width="100">SL *</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center p-1">
                                    <img src="https://placehold.co/100x100?text=SP" 
                                         class="img-preview rounded shadow-sm" 
                                         style="width:100px; height:100px; object-fit:cover; border:1px solid #eee;">
                                </td>
                                <td>
                                    <select class="form-select brand-select" required>
                                        <option value="">Chọn Hãng</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= $brand['category_id'] ?>"><?= $brand['category_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select product-select" disabled required>
                                        <option value="">Chọn Hãng trước</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="variant_id[]" class="form-select variant-select" disabled required>
                                        <option value="">Chọn Mẫu trước</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="quantity[]" class="form-control text-center fw-bold" min="1" placeholder="SL" required>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-link text-danger btn-remove p-0">
                                        <i class="fas fa-times-circle fa-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" name="save_ticket" class="btn btn-primary px-5 fw-bold shadow">
                        <i class="fas fa-save me-2"></i>LƯU PHIẾU KHO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/ticket-create.js?v=<?= time() ?>"></script>