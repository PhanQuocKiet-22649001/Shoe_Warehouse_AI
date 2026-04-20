<link rel="stylesheet" href="assets/css/product.css">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<div class="container-fluid p-0">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success'];
                                                        unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $_SESSION['error'];
                                                                unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="index.php?page=categories" class="text-decoration-none text-muted">Danh mục</a></li>
                    <li class="breadcrumb-item active fw-bold" style="color: #61839D;"><?= $categoryName ?></li>
                </ol>
            </nav>
            <h1 class="page-title fw-bold text-dark mb-0">Kho giày <?= $categoryName ?></h1>
        </div>

        <?php if ($_SESSION['role'] === 'MANAGER'): ?>
            <a href="index.php?page=categories" class="btn btn-dark px-4 rounded-pill fw-bold shadow-sm">
                <i class="fas fa-arrow-left me-2"></i> Quay lại
            </a>
        <?php endif; ?>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $pro): ?>
                <div class="col">
                    <div class="card brand-card h-100 border-0 shadow-sm">
                        <div class="brand-logo-wrapper py-4 d-flex align-items-center justify-content-center position-relative"
                            style="height: 220px; background-color: #ffffff; border-radius: 12px 12px 0 0;">

                            <?php $isActive = ($pro['status'] == 't' || $pro['status'] === true || $pro['status'] == 1); ?>

                            <span class="position-absolute top-0 start-0 m-2 badge rounded-pill shadow-sm"
                                style="background-color: <?= $isActive ? '#61839D' : '#6c757d' ?>; color: #ffffff; border: none; z-index: 2;">
                                <i class="fas <?= $isActive ? 'fa-check-circle' : 'fa-pause-circle' ?> me-1"></i>
                                <?= $isActive ? 'Đang kinh doanh' : 'Tạm ngưng' ?>
                            </span>

                            <?php
                            $imageFile = !empty($pro['product_image']) ? $pro['product_image'] : 'default_shoe.png';
                            $imagePath = "assets/img_product/" . $imageFile;
                            ?>

                            <img src="<?= $imagePath ?>"
                                alt="<?= htmlspecialchars($pro['product_name']) ?>"
                                style="max-height: 160px; max-width: 90%; object-fit: contain; transition: all 0.3s; filter: <?= !$isActive ? 'grayscale(100%) opacity(0.5)' : 'none' ?>;"
                                onerror="this.src='https://via.placeholder.com/200x150?text=NO+IMAGE'">
                        </div>

                        <div class="card-body p-4 text-center bg-white">
                            <h5 class="fw-bold mb-1 <?= !$isActive ? 'text-muted' : 'text-dark' ?>">
                                <?= htmlspecialchars($pro['product_name']) ?>
                            </h5>

                            <?php if (isset($pro['price'])): ?>
                                <h5 class="fw-bold mb-3" style="color: #61839D;">
                                    <?= number_format($pro['price'], 0, ',', '.') ?>đ
                                </h5>
                            <?php endif; ?>

                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <button class="btn btn-outline-dark btn-sm px-3 rounded-pill fw-bold"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailModal<?= $pro['product_id'] ?>">
                                    Chi tiết
                                </button>

                                <?php if ($_SESSION['role'] === 'MANAGER'): ?>
                                    <form action="index.php?page=products&category_id=<?= $pro['category_id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Đổi trạng thái kinh doanh của đôi này nhé bồ?')">
                                        <input type="hidden" name="product_id" value="<?= $pro['product_id'] ?>">
                                        <input type="hidden" name="category_id" value="<?= $pro['category_id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $pro['status'] ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-link p-0 shadow-none ms-1"
                                            style="color: <?= $isActive ? '#61839D' : '#adb5bd' ?>; text-decoration: none;" title="Đổi trạng thái">
                                            <i class="fas <?= $isActive ? 'fa-toggle-on' : 'fa-toggle-off' ?> fa-2x"></i>
                                        </button>
                                    </form>
                                    <form action="index.php?page=products&category_id=<?= $pro['category_id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Bồ chắc chắn muốn xóa đôi này không?')">
                                        <input type="hidden" name="product_id" value="<?= $pro['product_id'] ?>">
                                        <button type="submit" name="delete_product" class="btn btn-link" style="color: grey; margin-bottom: 3px;" title="Xóa sản phẩm">
                                            <i class="fas fa-trash-alt fa-lg"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="detailModal<?= $pro['product_id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header border-bottom-0 p-4">
                                <h5 class="modal-title fw-bold text-dark">
                                    Quản lý biến thể: <?= htmlspecialchars($pro['product_name']) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body p-4 pt-0">
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 100px;">Ảnh</th>
                                                <th>Mã SKU</th>
                                                <th>Màu sắc</th>
                                                <th class="text-center">Size</th>
                                                <th class="text-center">Số lượng</th>
                                                <th class="text-center">Vị trí kệ</th>
                                                <th class="text-center pe-4">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($pro['variants'])): ?>
                                                <?php foreach ($pro['variants'] as $var):
                                                    $vid = (string)$var['variant_id'];
                                                    $locs = $pro['locations'][$vid] ?? [];
                                                    $locsBase64 = base64_encode(json_encode($locs));
                                                ?>
                                                    <tr id="variant-row-<?= $vid ?>">
                                                        <td class="ps-4"><img src="<?= $imagePath ?>" class="rounded border shadow-sm" style="width: 50px; height: 35px; object-fit: cover;"></td>
                                                        <td class="small fw-bold"><?= $var['sku'] ?></td>
                                                        <td><?= htmlspecialchars($var['color']) ?></td>
                                                        <td class="text-center fw-bold"><span><?= $var['size'] ?></span></td>
                                                        <td class="text-center text-primary fw-bold"><?= $var['stock'] ?></td>
                                                        
                                                        <td class="text-center">
                                                            <?php if (empty($locs)): ?>
                                                                <span class="badge bg-secondary">Chưa cất</span>
                                                            <?php else: ?>
                                                                <div class="d-flex flex-column gap-1 align-items-center">
                                                                    <?php foreach ($locs as $l): ?>
                                                                        <span class="badge text-dark border border-secondary" style="font-size:12px;">
                                                                            <?= $l['str'] ?>
                                                                        </span>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>

                                                        <td class="text-center pe-4">
                                                            <div class="d-flex justify-content-center gap-2 align-items-center">
                                                                <?php if ($_SESSION['role'] === 'STAFF'): ?>
                                                                    <form action="index.php?page=products&action=export" method="POST" class="d-flex align-items-center gap-1" onsubmit="return confirmExport(this);">
                                                                        <input type="hidden" name="variant_id" value="<?= $var['variant_id'] ?>">
                                                                        <input type="hidden" name="current_stock" value="<?= $var['stock'] ?>">
                                                                        <input type="hidden" name="category_id" value="<?= $pro['category_id'] ?>">
                                                                        <input type="number" name="quantity" class="form-control form-control-sm text-center"
                                                                            style="width: 55px; border-color: #61839D;" value="1" min="1" max="<?= $var['stock'] ?>" required>
                                                                        <button type="submit" name="export_stock" class="btn btn-sm shadow-sm fw-bold btn-outline-dark">Xuất</button>
                                                                    </form>

                                                                <?php elseif ($_SESSION['role'] === 'MANAGER'): ?>
                                                                    <?php $is_active = ($var['variant_status'] === 't' || $var['variant_status'] === true || $var['variant_status'] === '1'); ?>

                                                                    <?php if (!empty($locs)): ?>
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Điều chuyển nội bộ"
                                                                            onclick="toggleMoveMap(<?= $vid ?>, '<?= $var['sku'] ?>', '<?= $locsBase64 ?>')">
                                                                            <i class="fas fa-exchange-alt"></i>
                                                                        </button>
                                                                    <?php endif; ?>

                                                                    <form action="index.php?page=products&action=toggle_variant_status" method="POST" class="m-0"
                                                                        onsubmit="return confirm('<?= $is_active ? 'Bạn có chắc chắn muốn TẠM NGƯNG biến thể này?' : 'Bạn muốn KÍCH HOẠT LẠI biến thể này để kinh doanh?' ?>');">
                                                                        <input type="hidden" name="variant_id" value="<?= $var['variant_id'] ?>">
                                                                        <input type="hidden" name="current_status" value="<?= $is_active ? 1 : 0 ?>">
                                                                        <button type="submit" class="btn btn-sm <?= $is_active ? 'btn-success' : 'btn-secondary' ?>"
                                                                            title="<?= $is_active ? 'Đang hoạt động - Nhấn để tắt' : 'Đã tắt - Nhấn để bật' ?>">
                                                                            <i class="fas <?= $is_active ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                                                        </button>
                                                                    </form>

                                                                    <form action="index.php?page=products&action=delete_variant" method="POST" class="m-0"
                                                                        onsubmit="return confirm('⚠️ CẢNH BÁO: Bạn có chắc chắn muốn XÓA biến thể này không? (Dữ liệu sẽ được đưa vào thùng rác)');">
                                                                        <input type="hidden" name="variant_id" value="<?= $var['variant_id'] ?>">
                                                                        <button type="submit" class="btn btn-sm btn-danger" title="Xóa biến thể">
                                                                            <i class="fas fa-trash-alt"></i>
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <tr id="map-row-<?= $vid ?>" class="d-none" style="background-color: #f8f9fa;">
                                                        <td colspan="7" class="p-0 border-0">
                                                            <div class="collapse" id="collapseMap-<?= $vid ?>">
                                                                <div class="card card-body m-3 border-primary shadow-sm bg-white" style="border-width: 2px !important;">
                                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                                        <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-map-marked-alt me-2"></i> Điều chuyển nội bộ: <?= $var['sku'] ?></h6>
                                                                        <button type="button" class="btn-close btn-sm" onclick="closeMoveMap(<?= $vid ?>)"></button>
                                                                    </div>
                                                                    
                                                                    <div id="moveMapStatus-<?= $vid ?>" class="mb-3 position-sticky top-0 shadow-sm" style="z-index: 100;"></div>
                                                                    
                                                                    <div id="moveMapContainer-<?= $vid ?>" class="dynamic-map-container p-3 bg-light rounded border shadow-inner">
                                                                        <div class="text-center py-4 text-muted"><div class="spinner-border text-primary" role="status"></div> Đang tải bản đồ...</div>
                                                                    </div>
                                                                    
                                                                    <div class="text-end mt-3 border-top pt-3">
                                                                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4 me-2" onclick="closeMoveMap(<?= $vid ?>)">Hủy</button>
                                                                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" id="btnConfirmMove-<?= $vid ?>" onclick="executeVisualMove(<?= $vid ?>)" disabled>
                                                                            <i class="fas fa-check me-1"></i> Xác nhận Điều Chuyển
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-5 text-muted">Chưa có biến thể!</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer border-top-0 p-4">
                                <button type="button" class="btn btn-dark px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-box-open fa-4x text-light mb-3"></i>
                <p class="text-muted">Hãng này hiện chưa có đôi giày nào!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['browser_alert'])): ?>
    <script>
        setTimeout(function() {
            alert("⚠️ " + <?= json_encode($_SESSION['browser_alert']) ?>);
        }, 100);
    </script>
    <?php unset($_SESSION['browser_alert']); ?>
<?php endif; ?>
<!-- 
<script>
    function confirmExport(form) {
        const qty = parseInt(form.quantity.value);
        const stock = parseInt(form.current_stock.value);
        if (qty > stock) { alert("Số lượng xuất (" + qty + ") không được lớn hơn tồn kho (" + stock + ")!"); return false; }
        if (qty <= 0) { alert("Số lượng xuất phải lớn hơn 0 nhé!"); return false; }
        return confirm("Xác nhận xuất " + qty + " đôi này khỏi kho?");
    }
</script>
<script>
    let currentOpenVid = null;
    let moveState = { vid: null, sku: null, locs: [], source: null, dest: null, qty: 1, sourceQty: 0 };

    // 1. SỔ ROW XUỐNG VÀ LOAD MAP
    function toggleMoveMap(vid, sku, locsBase64) {
        const mapRow = document.getElementById(`map-row-${vid}`);
        const bsCollapse = new bootstrap.Collapse(document.getElementById(`collapseMap-${vid}`), { toggle: false });

        // Nếu bấm lại chính nó đang mở -> thì đóng lại
        if (currentOpenVid === vid) {
            closeMoveMap(vid);
            return;
        }

        // Đóng row đang mở trước đó (nếu có) để giao diện gọn gàng
        if (currentOpenVid !== null) {
            closeMoveMap(currentOpenVid);
        }

        currentOpenVid = vid;
        moveState = {
            vid: vid,
            sku: sku,
            locs: JSON.parse(atob(locsBase64)),
            source: null,
            dest: null,
            qty: 1,
            sourceQty: 0
        };

        // Hiện row ẩn và vuốt mở (collapse)
        mapRow.classList.remove('d-none');
        bsCollapse.show();

        const statusDiv = document.getElementById(`moveMapStatus-${vid}`);
        const containerDiv = document.getElementById(`moveMapContainer-${vid}`);
        document.getElementById(`btnConfirmMove-${vid}`).disabled = true;

        statusDiv.innerHTML = "<div class='alert alert-info m-0 border-info py-2'>Bước 1: Vui lòng click chọn <b>Ô NGUỒN</b> đang chứa giày (Các ô đang nhấp nháy đỏ).</div>";
        containerDiv.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border text-primary" role="status"></div> Đang tải bản đồ...</div>';

        // Tận dụng API gọi map
        fetch('index.php?page=products&action=get_mini_heatmap')
            .then(r => r.text())
            .then(html => {
                containerDiv.innerHTML = html;
                setupMapInteractions(vid);
            });
    }

    function closeMoveMap(vid) {
        const bsCollapse = bootstrap.Collapse.getInstance(document.getElementById(`collapseMap-${vid}`));
        if (bsCollapse) bsCollapse.hide();
        setTimeout(() => document.getElementById(`map-row-${vid}`).classList.add('d-none'), 300);
        if (currentOpenVid === vid) currentOpenVid = null;
    }

    // 2. TƯƠNG TÁC SƠ ĐỒ ĐƯỢC GIỚI HẠN TRONG DÒNG ĐÓ
    function setupMapInteractions(vid) {
        const container = document.getElementById(`moveMapContainer-${vid}`);
        const cells = container.querySelectorAll('.shelf-cell');
        const validSources = moveState.locs.map(l => l.loc);
        const statusDiv = document.getElementById(`moveMapStatus-${vid}`);
        const btnConfirm = document.getElementById(`btnConfirmMove-${vid}`);

        cells.forEach(cell => {
            const code = cell.getAttribute('data-code');
            const occ = parseInt(cell.getAttribute('data-occupancy'));
            const maxCap = parseInt(cell.getAttribute('data-max')) || 4;

            if (validSources.includes(code)) {
                cell.classList.add('blinking-source');
            }

            cell.addEventListener('click', function() {
                if (!moveState.source) {
                    if (!validSources.includes(code)) {
                        alert("Vui lòng chọn ô ĐANG CHỨA GIÀY (các ô đang nhấp nháy)!");
                        return;
                    }
                    moveState.source = code;
                    moveState.sourceQty = moveState.locs.find(l => l.loc === code).qty;

                    cells.forEach(c => c.classList.remove('blinking-source'));
                    this.classList.add('selected-source');
                    
                    statusDiv.innerHTML = `<div class='alert alert-warning m-0 border-warning py-2'>Đã chọn nguồn: <b>${code}</b> (Sẵn ${moveState.sourceQty} đôi).<br>Bước 2: Click chọn <b>Ô ĐÍCH</b> muốn chuyển.</div>`;
                } 
                else if (!moveState.dest) {
                    if (code === moveState.source) {
                        moveState.source = null;
                        this.classList.remove('selected-source');
                        setupMapInteractions(vid); // reset nháy
                        statusDiv.innerHTML = "<div class='alert alert-info m-0 border-info py-2'>Bước 1: Vui lòng click chọn <b>Ô NGUỒN</b>.</div>";
                        return;
                    }

                    let qty = prompt(`Chuyển từ ${moveState.source} sang ${code}.\nNhập số lượng (Tối đa ${moveState.sourceQty}):`, 1);
                    qty = parseInt(qty);
                    
                    if (isNaN(qty) || qty <= 0 || qty > moveState.sourceQty) {
                        alert("Số lượng không hợp lệ!");
                        return;
                    }

                    moveState.dest = code;
                    moveState.qty = qty;
                    this.classList.add('selected-dest');

                    let isSwap = (maxCap - occ) < qty;
                    let msg = `Sẽ chuyển <b>${qty} đôi</b> từ <b>${moveState.source}</b> sang <b>${moveState.dest}</b>.`;
                    
                    if (isSwap) {
                        msg += `<br><span class='text-danger fw-bold'><i class='fas fa-exclamation-triangle'></i> Ô đích đã đầy (max ${maxCap}). Hệ thống sẽ HOÁN ĐỔI hàng đang có ở đích về lại ô nguồn!</span>`;
                    }

                    statusDiv.innerHTML = `<div class='alert alert-success m-0 border-success py-2'>${msg}</div>`;
                    btnConfirm.disabled = false;
                }
            });
        });
    }

    // 3. GỬI LỆNH ĐIỀU CHUYỂN
    async function executeVisualMove(vid) {
        if (!confirm(`Xác nhận thực hiện điều chuyển ${moveState.qty} đôi từ ${moveState.source} sang ${moveState.dest}?`)) return;

        try {
            const response = await fetch('index.php?page=products&action=move_location', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ variant_id: moveState.vid, from_loc: moveState.source, to_loc: moveState.dest, qty: moveState.qty })
            });
            const res = await response.json();
            if (res.status === 'success') {
                alert(res.message);
                location.reload();
            } else {
                alert("Lỗi: " + res.message);
            }
        } catch (e) {
            alert("Lỗi kết nối máy chủ.");
        }
    }
</script> -->
<script src="assets/js/product.js"></script>