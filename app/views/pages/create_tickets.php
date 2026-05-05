<?php
$type = $type ?? 'IMPORT';
$staffs = $staffs ?? [];
$brands = $brands ?? [];
$suggestions = $suggestions ?? [];
$auto_code = $auto_code ?? '';
$tickets = $tickets ?? [];
$filter_status = $filter_status ?? '';
$start_date = $start_date ?? '';
$end_date = $end_date ?? '';

// Nhóm đề xuất theo Brand
$groupedSuggestions = [];
foreach ($suggestions as $s) {
    $groupedSuggestions[$s['brand']][] = $s;
}
?>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $msgText = '';
    if ($_GET['msg'] === 'create_success') $msgText = 'Tạo phiếu thành công!';
    if ($_GET['msg'] === 'reassign_success') $msgText = 'Đổi nhân viên thành công!';
    if ($_GET['msg'] === 'delete_success') $msgText = 'Xóa phiếu thành công!';
    if ($_GET['msg'] === 'error') $msgText = 'Có lỗi xảy ra, vui lòng kiểm tra lại!';
    ?>
    <?php if ($msgText): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Đợi load giao diện xong mới báo
                setTimeout(function() {
                    alert("<?= $msgText ?>");
                    // Xóa tham số msg khỏi URL để F5 không bị báo lại
                    window.history.replaceState(null, null, window.location.pathname + "?page=ticket_create&type=<?= $type ?>");
                }, 100);
            });
        </script>
    <?php endif; ?>
<?php endif; ?>

<div class="container-fluid mt-4">
    <!-- KHU VỰC 1: CẢNH BÁO HẾT HÀNG -->
    <?php if ($type === 'IMPORT' && !empty($groupedSuggestions)): ?>
        <div class="accordion mb-4 shadow-sm" id="suggestionAccordion">
            <div class="accordion-item border-warning">
                <h2 class="accordion-header">
                    <button class="accordion-button bg-light text-danger fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSuggestions">
                        <i class="fas fa-exclamation-triangle me-2"></i> CẢNH BÁO HẾT HÀNG - ĐỀ XUẤT NHẬP (Nhấn để xem chi tiết)
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

    <!-- KHU VỰC 2: FORM TẠO PHIẾU -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white border-bottom">
            <h4 class="mb-0 text-primary fw-bold">TẠO PHIẾU <?= $type === 'IMPORT' ? 'NHẬP KHO' : 'XUẤT KHO' ?></h4>
        </div>
        <div class="card-body">
            <form id="ticketForm" method="POST" action="index.php?page=ticket_create">
                <input type="hidden" name="ticket_type" value="<?= $type ?>">

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-success">Mã Phiếu (Tự sinh)</label>
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

    <!-- KHU VỰC 3: LỊCH SỬ PHIẾU KHO KÈM BỘ LỌC -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 text-dark fw-bold">LỊCH SỬ PHIẾU <?= $type === 'IMPORT' ? 'NHẬP' : 'XUẤT' ?></h4>
    </div>

    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body py-2">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="ticket_create">
                <input type="hidden" name="type" value="<?= $type ?>">
                <div class="col-auto">
                    <label class="small fw-bold">Trạng thái:</label>
                    <select name="filter_status" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        <option value="PENDING" <?= $filter_status == 'PENDING' ? 'selected' : '' ?>>Đang chờ</option>
                        <option value="PROCESSING" <?= $filter_status == 'PROCESSING' ? 'selected' : '' ?>>Đang xử lý</option>
                        <option value="PAUSED" <?= $filter_status == 'PAUSED' ? 'selected' : '' ?>>Tạm ngừng</option>
                        <option value="COMPLETED" <?= $filter_status == 'COMPLETED' ? 'selected' : '' ?>>Hoàn thành</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="small fw-bold">Từ ngày:</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                </div>
                <div class="col-auto">
                    <label class="small fw-bold">Đến ngày:</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                </div>
                <div class="col-auto mt-4">
                    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
                    <a href="index.php?page=ticket_create&type=<?= $type ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo"></i> Xóa lọc</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3 text-start">Mã Phiếu</th>
                            <th>Lô hàng</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Nhân viên phụ trách</th>
                            <th>TG Hoàn thành</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Chưa có phiếu nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary text-start"><?= htmlspecialchars($t['ticket_code']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['batch_code'] ?? 'N/A') ?></span></td>
                                    <td>
                                        <?php
                                        $bgClass = 'bg-secondary';
                                        if ($t['status'] == 'PENDING') $bgClass = 'bg-warning text-dark';
                                        if ($t['status'] == 'PROCESSING') $bgClass = 'bg-info text-dark';
                                        if ($t['status'] == 'PAUSED') $bgClass = 'bg-danger';
                                        if ($t['status'] == 'COMPLETED') $bgClass = 'bg-success';
                                        ?>
                                        <span class="badge <?= $bgClass ?>"><?= $t['status'] ?></span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>

                                    <?php if (!empty($t['staff_name'])): ?>
                                        <td class="fw-bold"><i class="fas fa-user-check text-success"></i> <?= htmlspecialchars($t['staff_name']) ?></td>
                                    <?php else: ?>
                                        <td class="text-muted fst-italic">Trống</td>
                                    <?php endif; ?>

                                    <?php if ($t['status'] === 'COMPLETED' && !empty($t['completed_at'])): ?>
                                        <td class="text-success fw-bold"><?= date('d/m/Y H:i', strtotime($t['completed_at'])) ?></td>
                                    <?php else: ?>
                                        <td class="text-muted fst-italic">-</td>
                                    <?php endif; ?>
                                    <!-- CỘT THAO TÁC THEO LOGIC -->
                                    <td>
                                        <?php if (in_array($t['status'], ['PENDING', 'PAUSED'])): ?>
                                            <!-- 1. Dùng chung nút Đổi NV cho cả PENDING và PAUSED -->
                                            <button type="button" class="btn btn-sm btn-warning btn-change-staff"
                                                data-bs-toggle="modal"
                                                data-bs-target="#changeStaffModal"
                                                data-ticket-id="<?= $t['ticket_id'] ?>"
                                                data-ticket-code="<?= $t['ticket_code'] ?>"
                                                data-staff-name="<?= htmlspecialchars($t['staff_name'] ?? 'Chưa chỉ định') ?>"
                                                title="Chỉ định nhân viên khác">
                                                <i class="fas fa-user-edit"></i> Đổi NV
                                            </button>

                                            <!-- 2. Nút Xóa CHỈ HIỆN khi PENDING (Nhớ action có page=ticket_create để không văng) -->
                                            <?php if ($t['status'] === 'PENDING'): ?>
                                                <form action="index.php?page=ticket_create" method="POST" class="d-inline" onsubmit="return confirm('Chắc chắn xóa phiếu này?');">
                                                    <input type="hidden" name="ticket_id" value="<?= $t['ticket_id'] ?>">
                                                    <input type="hidden" name="return_type" value="<?= $type ?>">
                                                    <button type="submit" name="delete_ticket" class="btn btn-sm btn-outline-danger" title="Xóa phiếu">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>


                                            <button type="button" class="btn btn-sm btn-outline-info btn-view-details"
                                                data-bs-toggle="modal" data-bs-target="#ticketDetailModal"
                                                data-ticket-id="<?= $t['ticket_id'] ?>"
                                                data-ticket-code="<?= $t['ticket_code'] ?>"
                                                title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                        <?php else: ?>
                                            <span class="text-muted small"><i class="fas fa-lock"></i> Đã khóa</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- MODAL ĐỔI NHÂN VIÊN KHI PHIẾU BỊ PENDING / PAUSED -->
<div class="modal fade" id="changeStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Chỉ định lại Nhân viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=ticket_reassign" method="POST">
                <div class="modal-body">
                    <!-- Đã sửa lại câu thông báo -->
                    <p class="small text-danger">Phiếu này đang ở trạng thái <strong>Đang chờ</strong> hoặc <strong>Tạm ngừng</strong>. Bạn có thể chỉ định lại nhân viên thực hiện.</p>
                    <input type="hidden" name="ticket_id" id="modal_ticket_id">
                    <input type="hidden" name="return_type" value="<?= $type ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mã Phiếu</label>
                        <input type="text" id="modal_ticket_code" class="form-control bg-light" readonly>
                    </div>

                    <!-- THÊM: Hiện nhân viên hiện tại -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nhân viên hiện tại</label>
                        <input type="text" id="modal_current_staff" class="form-control bg-light text-muted" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Chọn Nhân viên mới *</label>
                        <select name="new_staff_id" class="form-select" required>
                            <option value="">-- Chọn nhân viên thay thế --</option>
                            <?php foreach ($staffs as $staff): ?>
                                <option value="<?= $staff['user_id'] ?>"><?= $staff['full_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- MODAL XEM CHI TIẾT PHIẾU -->
<div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="fas fa-file-invoice me-2"></i> Chi tiết phiếu: <span id="view_ticket_code" class="text-dark"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-dark">
                            <tr>
                                <th width="100">Hình ảnh</th>
                                <th>Hãng</th>
                                <th class="text-start">Tên sản phẩm</th>
                                <th>Phân loại (Màu - Size)</th>
                                <th>Số lượng</th>
                            </tr>
                        </thead>
                        <tbody id="detailModalBody">
                            <!-- JS sẽ đổ dữ liệu vào đây -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/ticket-create.js?v=<?= time() ?>"></script>