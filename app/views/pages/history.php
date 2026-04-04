<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0 fw-bold text-dark">Lịch Sử Giao Dịch Kho</h2>
        
        <form method="GET" action="index.php" class="d-flex gap-2 bg-white p-2 shadow-sm rounded border">
            <input type="hidden" name="page" value="history">
            <input type="date" name="from_date" class="form-control form-control-sm" value="<?= $_GET['from_date'] ?? '' ?>">
            <span class="align-self-center text-muted">đến</span>
            <input type="date" name="to_date" class="form-control form-control-sm" value="<?= $_GET['to_date'] ?? '' ?>">
            <button type="submit" class="btn btn-primary btn-sm px-3">Lọc</button>
            <a href="index.php?page=history" class="btn btn-light btn-sm border">Reset</a>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="m-0"><i class="fas fa-file-import me-2"></i>Tổng Hợp Nhập Kho</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive table-scroll">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Ngày</th>
                                    <th>Sản phẩm / Nhân viên</th>
                                    <th class="text-center">Tổng SL</th>
                                    <th class="text-center">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($importHistory)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">Chưa có giao dịch nhập nào</td></tr>
                                <?php else: foreach ($importHistory as $item): ?>
                                    <tr>
                                        <td class="align-middle text-muted" style="font-size: 0.85rem;">
                                            <?= date('d/m/Y', strtotime($item['log_date'])) ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-dark"><?= $item['product_name'] ?></div>
                                            <small class="text-muted"><i class="fas fa-user-edit me-1"></i><?= $item['user_name'] ?></small>
                                        </td>
                                        <td class="align-middle text-center text-success fw-bold">+<?= $item['total_qty'] ?></td>
                                        <td class="align-middle text-center">
                                            <button class="btn btn-sm btn-outline-success rounded-pill btn-view-detail"
                                                    data-date="<?= $item['log_date'] ?>"
                                                    data-pid="<?= $item['product_id'] ?>"
                                                    data-uid="<?= $item['user_id'] ?>"
                                                    data-type="IMPORT"
                                                    data-pname="<?= $item['product_name'] ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white py-3">
                    <h5 class="m-0"><i class="fas fa-file-export me-2"></i>Tổng Hợp Xuất Kho</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive table-scroll">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Ngày</th>
                                    <th>Sản phẩm / Nhân viên</th>
                                    <th class="text-center">Tổng SL</th>
                                    <th class="text-center">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($exportHistory)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">Chưa có giao dịch xuất nào</td></tr>
                                <?php else: foreach ($exportHistory as $item): ?>
                                    <tr>
                                        <td class="align-middle text-muted" style="font-size: 0.85rem;">
                                            <?= date('d/m/Y', strtotime($item['log_date'])) ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-dark"><?= $item['product_name'] ?></div>
                                            <small class="text-muted"><i class="fas fa-user-tag me-1"></i><?= $item['user_name'] ?></small>
                                        </td>
                                        <td class="align-middle text-center text-danger fw-bold">-<?= $item['total_qty'] ?></td>
                                        <td class="align-middle text-center">
                                            <button class="btn btn-sm btn-outline-danger rounded-pill btn-view-detail"
                                                    data-date="<?= $item['log_date'] ?>"
                                                    data-pid="<?= $item['product_id'] ?>"
                                                    data-uid="<?= $item['user_id'] ?>"
                                                    data-type="EXPORT"
                                                    data-pname="<?= $item['product_name'] ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalTitle">Chi tiết biến thể</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <p class="mb-1 text-muted small">Tên sản phẩm:</p>
                    <h6 id="detailProductName" class="fw-bold mb-0 text-primary"></h6>
                </div>
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Kích cỡ (Size)</th>
                            <th>Màu sắc</th>
                            <th class="text-center">Số lượng</th>
                        </tr>
                    </thead>
                    <tbody id="detailTableBody">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.table-scroll {
    max-height: 65vh; /* Sử dụng view-height để linh hoạt hơn */
    overflow-y: auto;
}
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);
}
.btn-view-detail:hover {
    transform: scale(1.1);
    transition: 0.2s;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailModal = new bootstrap.Modal(document.getElementById('modalDetail'));
    const tableBody = document.getElementById('detailTableBody');
    const productNameEl = document.getElementById('detailProductName');

    document.querySelectorAll('.btn-view-detail').forEach(button => {
        button.addEventListener('click', async function() {
            // Lấy thông tin từ data attributes
            const date = this.dataset.date;
            const pid = this.dataset.pid;
            const uid = this.dataset.uid;
            const type = this.dataset.type;
            const pname = this.dataset.pname;

            // Hiển thị tên sản phẩm lên modal
            productNameEl.innerText = pname;
            tableBody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Đang tải...</td></tr>';
            detailModal.show();

            try {
                // Gọi API lấy chi tiết (Page: history-detail đã thêm ở index.php)
                const response = await fetch(`index.php?page=history-detail&date=${date}&product_id=${pid}&user_id=${uid}&type=${type}`);
                const data = await response.json();

                if (data.length > 0) {
                    let html = '';
                    data.forEach(row => {
                        html += `
                            <tr>
                                <td class="ps-3 fw-bold">${row.size}</td>
                                <td><span class="badge bg-secondary opacity-75">${row.color}</span></td>
                                <td class="text-center fw-bold ${type === 'IMPORT' ? 'text-success' : 'text-danger'}">
                                    ${type === 'IMPORT' ? '+' : '-'}${row.quantity}
                                </td>
                            </tr>
                        `;
                    });
                    tableBody.innerHTML = html;
                } else {
                    tableBody.innerHTML = '<tr><td colspan="3" class="text-center py-3">Không có chi tiết.</td></tr>';
                }
            } catch (error) {
                tableBody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-danger">Lỗi kết nối máy chủ.</td></tr>';
            }
        });
    });
});
</script>