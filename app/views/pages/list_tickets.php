<?php
$tickets = $tickets ?? [];
$filter_status = $filter_status ?? '';
$start_date = $start_date ?? '';
$end_date = $end_date ?? '';
?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="text-dark fw-bold mb-0">Danh sách Phiếu Kho</h3>
        <div>
            <a href="index.php?page=ticket_create&type=IMPORT" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Nhập kho</a>
            <a href="index.php?page=ticket_create&type=EXPORT" class="btn btn-danger btn-sm"><i class="fas fa-minus"></i> Xuất kho</a>
        </div>
    </div>
    
    <!-- KHU VỰC BỘ LỌC -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body py-3">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="ticket_list">
                <div class="col-auto">
                    <label class="small fw-bold">Trạng thái:</label>
                    <select name="filter_status" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        <option value="PENDING" <?= $filter_status=='PENDING'?'selected':'' ?>>Đang chờ</option>
                        <option value="PROCESSING" <?= $filter_status=='PROCESSING'?'selected':'' ?>>Đang xử lý</option>
                        <option value="COMPLETED" <?= $filter_status=='COMPLETED'?'selected':'' ?>>Hoàn thành</option>
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
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
                    <a href="index.php?page=ticket_list" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i> Xóa</a>
                </div>
            </form>
        </div>
    </div>

    <!-- BẢNG DỮ LIỆU -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3 text-start">Mã Phiếu</th>
                            <th>Lô hàng</th>
                            <th>Loại</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Nhân viên phụ trách</th>
                            <th>TG Hoàn thành</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Không tìm thấy phiếu nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary text-start"><?= htmlspecialchars($t['ticket_code']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['batch_code'] ?? 'N/A') ?></span></td>
                                    <td>
                                        <?php if($t['ticket_type'] === 'IMPORT'): ?>
                                            <span class="badge bg-success">NHẬP KHO</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">XUẤT KHO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $bgClass = 'bg-secondary';
                                            if($t['status'] == 'PENDING') $bgClass = 'bg-warning text-dark';
                                            if($t['status'] == 'PROCESSING') $bgClass = 'bg-info text-dark';
                                            if($t['status'] == 'COMPLETED') $bgClass = 'bg-success';
                                        ?>
                                        <span class="badge <?= $bgClass ?>"><?= $t['status'] ?></span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                    
                                    <!-- LOGIC HIỂN THỊ NHÂN VIÊN & TG HOÀN THÀNH -->
                                    <?php if(in_array($t['status'], ['PROCESSING', 'COMPLETED', 'PAUSED'])): ?>
                                        <td class="fw-bold"><i class="fas fa-user-check text-success"></i> <?= htmlspecialchars($t['staff_name']) ?></td>
                                    <?php else: ?>
                                        <td class="text-muted fst-italic">Trống</td>
                                    <?php endif; ?>

                                    <?php if($t['status'] === 'COMPLETED' && !empty($t['completed_at'])): ?>
                                        <td class="text-success fw-bold"><?= date('d/m/Y H:i', strtotime($t['completed_at'])) ?></td>
                                    <?php else: ?>
                                        <td class="text-muted fst-italic">Trống</td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>