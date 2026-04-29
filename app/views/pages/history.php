<link rel="stylesheet" href="assets/css/history.css">

<!-- Thêm topbar nếu bồ cần -->
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<div class="history-container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0 fw-bold text-dark">LỊCH SỬ GIAO DỊCH KHO</h2>

        <!-- Form lọc kính lỏng -->
        <form method="GET" action="index.php" class="glass-filter-form d-flex gap-2 p-2 rounded">
            <input type="hidden" name="page" value="history">
            <input type="date" name="from_date" class="glass-input form-control form-control-sm" value="<?= $_GET['from_date'] ?? '' ?>">
            <span class="align-self-center fw-bold text-dark">đến</span>
            <input type="date" name="to_date" class="glass-input form-control form-control-sm" value="<?= $_GET['to_date'] ?? '' ?>">
            <button type="submit" class="btn btn-action-glass px-3">Lọc</button>
            <a href="index.php?page=history" class="btn btn-cancel-glass border">Reset</a>
        </form>
    </div>

    <!-- KHU VỰC BẢNG (IMPORT / EXPORT) -->
    <div class="row g-4">
        <!-- Cột Nhập Kho -->
        <div class="col-md-6">
            <div class="glass-panel">
                <div class="glass-panel-header">
                    <h5 class="m-0"><i class="fas fa-file-import me-2"></i>Tổng Hợp Nhập Kho</h5>
                </div>
                <div class="table-responsive table-scroll">
                    <table class="history-table">
                        <thead class="sticky-top">
                            <tr>
                                <th>Ngày</th>
                                <th>Sản phẩm / Nhân viên</th>
                                <th class="text-center">Tổng SL</th>
                                <th class="text-center">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($importHistory)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-dark fw-bold">Chưa có giao dịch nhập nào</td>
                                </tr>
                                <?php else:
                                $currentDateImport = '';
                                $isGrayImport = false; // Trạng thái màu mặc định
                                foreach ($importHistory as $item):
                                    // Format ngày để so sánh
                                    $itemDate = date('d/m/Y', strtotime($item['log_date']));

                                    // Nếu ngày đổi sang ngày mới, thì đảo trạng thái màu
                                    if ($itemDate !== $currentDateImport) {
                                        $isGrayImport = !$isGrayImport;
                                        $currentDateImport = $itemDate;
                                    }

                                    // Gán class màu nếu trạng thái là true
                                    $rowClass = $isGrayImport ? 'row-group-gray' : '';
                                ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td class="align-middle fw-bold" style="font-size: 0.85rem;">
                                            <?= $itemDate ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-dark"><?= $item['product_name'] ?></div>
                                            <small class="text-dark"><i class="fas fa-user-edit me-1"></i><?= $item['user_name'] ?></small>
                                        </td>
                                        <td class="align-middle text-center fw-bold" style="color: #10b981;">+<?= $item['total_qty'] ?></td>
                                        <td class="align-middle text-center">
                                            <button class="btn-view-glass btn-view-detail"
                                                data-date="<?= $item['log_date'] ?>"
                                                data-pid="<?= $item['product_id'] ?>"
                                                data-uid="<?= $item['user_id'] ?>"
                                                data-type="IMPORT"
                                                data-pname="<?= $item['product_name'] ?>">
                                                Xem
                                            </button>
                                        </td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cột Xuất Kho -->
        <div class="col-md-6">
            <div class="glass-panel">
                <div class="glass-panel-header">
                    <h5 class="m-0"><i class="fas fa-file-export me-2"></i>Tổng Hợp Xuất Kho</h5>
                </div>
                <div class="table-responsive table-scroll">
                    <table class="history-table">
                        <thead class="sticky-top">
                            <tr>
                                <th>Ngày</th>
                                <th>Sản phẩm / Nhân viên</th>
                                <th class="text-center">Tổng SL</th>
                                <th class="text-center">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($exportHistory)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-dark fw-bold">Chưa có giao dịch xuất nào</td>
                                </tr>
                                <?php else:
                                $currentDateExport = '';
                                $isGrayExport = false;
                                foreach ($exportHistory as $item):
                                    $itemDate = date('d/m/Y', strtotime($item['log_date']));

                                    if ($itemDate !== $currentDateExport) {
                                        $isGrayExport = !$isGrayExport;
                                        $currentDateExport = $itemDate;
                                    }

                                    $rowClass = $isGrayExport ? 'row-group-gray' : '';
                                ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td class="align-middle fw-bold" style="font-size: 0.85rem;">
                                            <?= $itemDate ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-dark"><?= $item['product_name'] ?></div>
                                            <small class="text-dark"><i class="fas fa-user-tag me-1"></i><?= $item['user_name'] ?></small>
                                        </td>
                                        <td class="align-middle text-center fw-bold" style="color: #ef4444;">-<?= $item['total_qty'] ?></td>
                                        <td class="align-middle text-center">
                                            <button class="btn-view-glass btn-view-detail"
                                                data-date="<?= $item['log_date'] ?>"
                                                data-pid="<?= $item['product_id'] ?>"
                                                data-uid="<?= $item['user_id'] ?>"
                                                data-type="EXPORT"
                                                data-pname="<?= $item['product_name'] ?>">
                                                Xem
                                            </button>
                                        </td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================
     MODAL CHI TIẾT GIAO DỊCH (GLASSMORPHISM)
=============================================== -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Chi tiết biến thể</h5>
                <span class="modal-close" data-bs-dismiss="modal">×</span>
            </div>
            <div class="modal-body p-3">
                <div class="p-3">
                    <p class="mb-1" style="color: rgba(255, 255, 255, 0.7); font-size: 0.85rem;">Tên sản phẩm:</p>
                    <h6 id="detailProductName"></h6>
                </div>
                <!-- Bảng chi tiết bên trong Modal cũng cần trong suốt -->
                <table class="history-table modal-inner-table">
                    <thead style="background-color: rgb(38, 38, 38)">
                        <tr>
                            <th class="ps-3">Kích cỡ (Size)</th>
                            <th>Màu sắc</th>
                            <th class="text-center">Số lượng</th>
                        </tr>
                    </thead>
                    <tbody id="detailTableBody">
                        <!-- JS đổ dữ liệu vào đây -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Nhúng file JS tách riêng -->
<script src="assets/js/history.js"></script>