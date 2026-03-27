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
        <?php else: ?>
            <button class="btn btn-dark px-4 rounded-pill fw-bold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus me-2"></i> Thêm sản phẩm
            </button>
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
                                        <thead style="background-color: #1F2937; color: #ffffff;">
                                            <tr>
                                                <th class="ps-4" style="width: 100px;">Ảnh</th>
                                                <th>Mã SKU</th>
                                                <th>Tên giày</th>
                                                <th>Màu sắc</th>
                                                <th class="text-center">Size</th>
                                                <th class="text-center">Số lượng</th>
                                                <th class="text-center pe-4">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($pro['variants'])): ?>
                                                <?php foreach ($pro['variants'] as $var): ?>
                                                    <tr>
                                                        <td class="ps-4">
                                                            <img src="<?= $imagePath ?>" class="rounded border shadow-sm" style="width: 60px; height: 45px; object-fit: cover;">
                                                        </td>
                                                        <td><?= $var['sku'] ?></td>
                                                        <td class="small"><?= htmlspecialchars($pro['product_name']) ?></td>
                                                        <td><?= htmlspecialchars($var['color']) ?></td>
                                                        <td class="text-center"><span><?= $var['size'] ?></span></td>
                                                        <td class="text-center"><?= $var['stock'] ?></td>
                                                        <td class="text-center pe-4">
                                                            <div class="d-flex justify-content-center gap-2">
                                                                <button class="btn btn-sm btn-light border shadow-sm"><i class="fas fa-toggle-on"></i></button>
                                                                <button class="btn btn-sm btn-light border shadow-sm"><i class="fas fa-trash-alt"></i></button>
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


<!-- thông báo -->
<?php if (isset($_SESSION['browser_alert'])): ?>
    <script>
        // Sử dụng setTimeout để đảm bảo giao diện đã hiển thị xong xuôi
        setTimeout(function() {
            alert("⚠️ " + <?= json_encode($_SESSION['browser_alert']) ?>);
        }, 100);
    </script>
    <?php unset($_SESSION['browser_alert']); ?>
<?php endif; ?>


<!-- modal thêm sản phẩm -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form id="form-add-shoe" action="index.php?page=products&category_id=<?= $_GET['category_id'] ?>"
                method="POST" enctype="multipart/form-data">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-brain me-2"></i>AI Warehouse Assistant</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <input type="hidden" name="category_id" value="<?= $_GET['category_id'] ?>">
                    <input type="hidden" name="temp_image_name" id="temp_image_name">
                    <input type="hidden" name="product_id_hidden" id="product_id_hidden">

                    <div class="row">
                        <!-- Cột Trái: Upload & Nút Quét -->
                        <div class="col-md-5 border-end">
                            <label class="form-label fw-bold text-secondary">Hình ảnh sản phẩm</label>
                            <div class="upload-zone text-center p-3 border-2 border-dashed rounded-3 bg-light mb-3">
                                <img id="preview_img" src="assets/img/upload-placeholder.png" class="img-fluid rounded shadow-sm mb-3" style="max-height: 200px; display: block; margin: 0 auto;">
                                <input type="file" id="product_image_input" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <button type="button" class="btn btn-primary w-100 fw-bold py-2 shadow-sm" id="btn-scan-ai" onclick="handleScanAI()">
                                <i class="fas fa-expand me-2"></i> QUÉT AI & ĐỐI SOÁT
                            </button>
                            <div id="scan-loading" class="text-center mt-2" style="display: none;">
                                <div class="spinner-border spinner-border-sm text-primary"></div>
                                <span class="small ms-1 text-muted">Đang nén & nhận diện...</span>
                            </div>
                        </div>

                        <!-- Cột Phải: Thông tin sản phẩm -->
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Hãng sản xuất</label>
                                <input type="text" id="input_brand" class="form-control border-2" placeholder="Ví dụ: Nike">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">Tên sản phẩm đầy đủ</label>
                                <div class="input-group">
                                    <input type="text" name="product_name" id="new_product_name" class="form-control border-2 fw-bold" placeholder="Nhập tên sản phẩm...">
                                    <span class="input-group-text bg-white border-2" id="match-status" style="display: none;">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </span>
                                </div>
                                <div id="ai-msg" class="form-text mt-1 small"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Màu sắc</label>
                                <select id="color_select" class="form-select border-2 mb-2" onchange="toggleColorInput()">
                                    <option value="new_color">-- Nhập màu mới --</option>
                                </select>
                                <input type="text" name="color" id="product_color_input" class="form-control border-2" placeholder="Nhập màu sắc..." required>
                            </div>

                            <!-- KHU VỰC SIZE VÀ SỐ LƯỢNG (Đã được khôi phục) -->
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold">Size</label>
                                    <input type="number" name="size" class="form-control border-2" placeholder="42" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold">Số lượng nhập</label>
                                    <input type="number" name="stock" class="form-control border-2" value="1" min="1" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_product" class="btn btn-dark px-4 fw-bold shadow">
                        <i class="fas fa-save me-2"></i> XÁC NHẬN NHẬP KHO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // 1. HÀM NÉN ẢNH (Giữ nguyên)
    async function compressImage(file) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = (e) => {
                const img = new Image();
                img.src = e.target.result;
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const MAX_WIDTH = 800;
                    let width = img.width;
                    let height = img.height;
                    if (width > MAX_WIDTH) {
                        height *= MAX_WIDTH / width;
                        width = MAX_WIDTH;
                    }
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.7);
                };
            };
        });
    }

    // 2. HÀM BẬT/TẮT INPUT MÀU (Giữ nguyên)
    function toggleColorInput() {
        const sel = document.getElementById('color_select');
        const inp = document.getElementById('product_color_input');
        if (sel.value === 'new_color') {
            inp.style.display = 'block';
            inp.required = true;
        } else {
            inp.style.display = 'none';
            inp.required = false;
            inp.value = sel.value;
        }
    }

    // 3. HÀM CHÍNH: QUÉT AI & ĐỐI SOÁT
    async function handleScanAI() {
        const fileInput = document.getElementById('product_image_input');
        const btn = document.getElementById('btn-scan-ai');
        const loading = document.getElementById('scan-loading');

        const brandInp = document.getElementById('input_brand');
        const nameInp = document.getElementById('new_product_name');

        const colorSel = document.getElementById('color_select');
        const colorInp = document.getElementById('product_color_input');
        const tempImgInp = document.getElementById('temp_image_name');
        const productIdHidden = document.getElementById('product_id_hidden');
        const matchStatus = document.getElementById('match-status');
        const aiMsg = document.getElementById('ai-msg');

        if (!fileInput.files[0]) {
            alert('Bồ chưa chọn ảnh đôi giày nào cả!');
            return;
        }

        btn.disabled = true;
        loading.style.display = 'block';
        aiMsg.innerHTML = '<span class="text-muted small">AI đang phân tích...</span>';

        try {
            const blob = await compressImage(fileInput.files[0]);
            const fd = new FormData();
            fd.append('image', blob, 'scan.jpg');

            const response = await fetch('index.php?page=products&action=scan-ai', {
                method: 'POST',
                body: fd
            });

            // Nếu server lỗi 500 hoặc 404
            if (!response.ok) throw new Error(`Server báo lỗi HTTP: ${response.status}`);

            const res = await response.json();

            if (res.error) {
                alert('❌ Lỗi AI: ' + res.error);
                return;
            }

            document.getElementById('preview_img').src = 'assets/img_product/' + res.temp_image;
            tempImgInp.value = res.temp_image;

            if (res.status === 'exists') {
                // --- TRƯỜNG HỢP: ĐÃ CÓ TRONG KHO ---
                brandInp.value = res.product.category_name;
                nameInp.value = res.product.product_name;

                brandInp.readOnly = true;
                nameInp.readOnly = true;
                productIdHidden.value = res.product.product_id;
                matchStatus.style.display = 'flex';
                aiMsg.innerHTML = `<span class="text-success fw-bold"><i class="fas fa-check"></i> Đã khớp với DB</span>`;

                // --- BỌC TRY...CATCH RIÊNG CHO PHẦN GET MÀU ---
                try {
                    const colorRes = await fetch(`index.php?page=products&action=get-colors&product_id=${res.product.product_id}`);
                    const colorText = await colorRes.text(); // Lấy dạng text trước để check lỗi
                    const colors = colorText ? JSON.parse(colorText) : []; // Parse an toàn

                    if (colors && colors.length > 0) {
                        colorSel.innerHTML = colors.map(c => `<option value="${c.color}">${c.color}</option>`).join('') + '<option value="new_color">-- Thêm màu mới --</option>';
                    } else {
                        colorSel.innerHTML = '<option value="new_color">-- Thêm màu mới --</option>';
                    }

                    // Tự động chọn màu AI quét
                    if (colors && colors.some(c => c.color === res.detected_color)) {
                        colorSel.value = res.detected_color;
                    } else {
                        colorSel.value = 'new_color';
                        colorInp.value = res.detected_color;
                    }
                } catch (colorErr) {
                    console.error("Lỗi khi kéo danh sách màu từ DB (Router/Controller lỗi):", colorErr);
                    // DÙ LỖI API MÀU, VẪN PHẢI ĐIỀN ĐƯỢC MÀU AI QUÉT VÀO Ô
                    colorSel.innerHTML = '<option value="new_color">-- Thêm màu mới --</option>';
                    colorSel.value = 'new_color';
                    colorInp.value = res.detected_color; 
                }
                
                toggleColorInput();
                
            } else {
                // --- TRƯỜNG HỢP: SẢN PHẨM MỚI ---
                brandInp.value = res.suggestion.brand;
                nameInp.value = res.suggestion.brand + ' ' + res.suggestion.model;

                brandInp.readOnly = false;
                nameInp.readOnly = false;
                productIdHidden.value = '';
                matchStatus.style.display = 'none';
                aiMsg.innerHTML = `<span class="text-primary fw-bold"><i class="fas fa-magic"></i> AI gợi ý tên mới</span>`;

                colorSel.innerHTML = '<option value="new_color">-- Nhập màu mới --</option>';
                colorSel.value = 'new_color';
                colorInp.value = res.suggestion.color; // Điền màu AI quét
                toggleColorInput();
            }
        } catch (e) {
            console.error("Lỗi toàn cục:", e);
            alert('Lỗi kết nối server: ' + e.message);
        } finally {
            loading.style.display = 'none';
            
            // TÍNH NĂNG CHỐNG SPAM API (COOLDOWN 8 GIÂY)
            let cooldown = 8;
            const originalText = '<i class="fas fa-expand me-2"></i> QUÉT AI & ĐỐI SOÁT';
            
            const timer = setInterval(() => {
                btn.innerHTML = `<i class="fas fa-hourglass-half me-2"></i> Đợi ${cooldown}s...`;
                cooldown--;
                
                if (cooldown < 0) {
                    clearInterval(timer);
                    btn.disabled = false; // Mở khóa nút lại
                    btn.innerHTML = originalText;
                }
            }, 1000);
        }
    }

    // Reset khi chọn ảnh mới
    document.getElementById('product_image_input').onchange = e => {
        if (e.target.files[0]) {
            document.getElementById('preview_img').src = URL.createObjectURL(e.target.files[0]);
            document.getElementById('input_brand').readOnly = false;
            document.getElementById('new_product_name').readOnly = false;
            document.getElementById('match-status').style.display = 'none';
            document.getElementById('ai-msg').innerText = '';
        }
    };
</script>