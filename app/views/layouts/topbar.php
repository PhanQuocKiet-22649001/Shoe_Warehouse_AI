<link rel="stylesheet" href="assets/css/topbar.css">

<?php
$cModel = new CategoryModel();
// Lấy tất cả các hãng đang kinh doanh (status = true)
$allCategories = $cModel->getAll();
?>
<script>
    // Chuyển mảng PHP sang JSON để JavaScript sử dụng
    const categoriesList = <?= json_encode($allCategories) ?>;
</script>

<div class="topbar mb-4 d-flex justify-content-between align-items-center p-3 rounded shadow-sm ">

    <div class="search-box flex-grow-1 me-4 position-relative">
        <input type="text" id="mainSearch" class="form-control " placeholder="Tìm tên giày, size, mã SKU, màu sắc..." style="max-width: 400px;" autocomplete="off">

        <div id="searchResults" class="list-group position-absolute w-100 shadow mt-1 d-none"
            style="z-index: 1050; max-width: 400px; max-height: 400px; overflow-y: auto; border-radius: 4px;">
        </div>
    </div>

    <div class="user-info d-flex align-items-center">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'STAFF'): ?>
            <button class="btn me-3 fw-bold"
                data-bs-toggle="modal" data-bs-target="#addProductModal"
                style="border-radius: 7px; border: 1px solid #000000;">
                Nhập Kho (AI)
            </button>

            <button class="btn me-3 fw-bold"
                data-bs-toggle="modal" data-bs-target="#exportAIModal"
                style="border-radius: 7px; border: 1px solid #000000;">
                Xuất Kho (AI)
            </button>
        <?php endif; ?>

        <div class="dropdown">
            <div class="d-flex align-items-center  px-3 py-2  rounded cursor-pointer dropdown-toggle"
                id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                <span class="fw-bold "><?= $_SESSION['full_name'] ?? 'Người dùng hệ thống' ?></span>
            </div>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm boder mt-1" aria-labelledby="userMenu" style="border-radius: 4px;">
                <li>
                    <a class="dropdown-item py-2 " href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                        Thông tin tài khoản
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2 " href="#" data-bs-toggle="modal" data-bs-target="#updateProfileModal">
                        Cập nhật bảo mật
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow rounded-1">
            <div class="modal-header border-bottom flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                    <h5 class="modal-title fw-bold text-white text-uppercase">Nhập kho với AI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <ul class="nav nav-pills w-100 glass-tabs" id="importTabs" role="tablist">
                    <li class="nav-item flex-fill text-center" role="presentation">
                        <button class="nav-link active w-100 fw-bold" data-mode="ai" type="button">
                            <i class="fas fa-robot me-2"></i>QUÉT AI (TỰ ĐỘNG)
                        </button>
                    </li>
                    <li class="nav-item flex-fill text-center ms-2" role="presentation">
                        <button class="nav-link w-100 fw-bold" data-mode="manual" type="button">
                            <i class="fas fa-keyboard me-2"></i>NHẬP THỦ CÔNG
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-0">
                <div class="row g-0 d-flex">

                    <div class="col-md-6 p-4 border-end-glass">
                        <div class="text-start mb-4">
                            <h6 class="fw-bold text-white pb-2 mb-3 border-bottom-glass">1. TẢI DỮ LIỆU ĐẦU VÀO</h6>
                            <div class="upload-zone p-3 rounded-1 mb-2">
                                <input type="file" id="ai_multi_files" class="d-none" multiple accept="image/*" onchange="previewSelectedImages(this)">

                                <button type="button" class="btn btn-glass-confirm fw-bold w-100 mb-2 shadow-sm rounded-1" onclick="document.getElementById('ai_multi_files').click()">
                                    CHỌN TỆP HÌNH ẢNH (TỐI ĐA 3)
                                </button>
                                <p class="text-white-50 mb-3" style="font-size: 12px; line-height: 1.4;">
                                    *Lưu ý: Để chọn nhiều ảnh, vui lòng giữ phím Ctrl và click chọn các ảnh cùng lúc.
                                </p>

                                <button type="button" class="btn btn-glass-confirm w-100 fw-bold shadow-sm rounded-1" id="btn-scan-batch" onclick="executeBatchScan()">
                                    BẮT ĐẦU XỬ LÝ
                                </button>
                            </div>
                            <div id="pre_scan_preview" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>

                        <div id="post_scan_area" class="text-start d-none">
                            <h6 class="fw-bold text-white pb-2 mb-3 border-bottom-glass">2. DANH SÁCH ĐÃ XỬ LÝ</h6>
                            <p class="text-white-50 small fw-bold mb-2">Vui lòng click vào từng ảnh để thao tác:</p>
                            <div id="scanned_thumbnails" class="d-flex flex-wrap gap-3 p-3 rounded-1"></div>
                        </div>
                    </div>

                    <div class="col-md-6 p-4">
                        <h6 class="fw-bold text-white pb-2 mb-3 text-uppercase border-bottom-glass">3. Khai Báo Biểu Mẫu Nhập Kho</h6>
                        <div id="active_form_container" class="rounded-1 p-4 shadow-sm glass-inner-box" style="min-height: 400px;">
                            <div class="text-center text-white-50 py-5 mt-4">
                                <h4 class="mb-3 opacity-50 fw-bold">CHƯA CÓ DỮ LIỆU</h4>
                                <p class="mb-0">Hệ thống đang chờ lệnh. Vui lòng tải tệp để tiếp tục.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="exportAIModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow rounded-1">
            <div class="modal-header border-bottom flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                    <h5 class="modal-title fw-bold text-white text-uppercase">Xuất Kho Với AI </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body p-0">
                <div class="row g-0 d-flex">
                    <div class="col-md-6 p-4 border-end-glass">
                        <div class="text-start mb-4">
                            <h6 class="fw-bold text-white pb-2 mb-3 border-bottom-glass">1. QUÉT ẢNH SẢN PHẨM CẦN XUẤT</h6>

                            <div class="upload-zone p-3 rounded-1 mb-2" id="export_upload_zone">
                                <input type="file" id="export_ai_file" class="d-none" accept="image/*" multiple onchange="previewExportImages(this)">
                                <button type="button" class="btn btn-glass-confirm fw-bold w-100 mb-2 shadow-sm rounded-1" onclick="document.getElementById('export_ai_file').click()">
                                    <i class="fas fa-camera me-2"></i> CHỌN/CHỤP ẢNH SẢN PHẨM (TỐI ĐA 3)
                                </button>
                                <p class="text-white-50 mb-3" style="font-size: 12px; line-height: 1.4;">
                                    *Lưu ý: Để chọn nhiều ảnh, vui lòng giữ phím Ctrl và click chọn các ảnh cùng lúc.
                                </p>
                                <button type="button" class="btn btn-glass-confirm w-100 fw-bold shadow-sm rounded-1" id="btn-export-scan" onclick="executeExportScan()">
                                    BẮT ĐẦU XỬ LÝ
                                </button>
                            </div>

                            <div id="export_pre_scan_preview" class="d-flex flex-wrap gap-2 mt-2 mb-3"></div>

                            <div id="export_post_scan_area" class="text-start d-none">
                                <h6 class="fw-bold text-white pb-2 mb-3 border-bottom-glass">DANH SÁCH ĐÃ NHẬN DIỆN</h6>
                                <p class="text-white-50 small fw-bold mb-2">Click vào từng ảnh để khai báo số lượng xuất:</p>
                                <div id="export_scanned_thumbnails" class="d-flex flex-wrap gap-3 p-3 rounded-1 glass-inner-box"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 p-4 position-relative">
                        <h6 class="fw-bold text-white pb-2 mb-3 text-uppercase border-bottom-glass">2. Khai Báo Biểu Mẫu Xuất Kho</h6>

                        <div id="export_default_screen" class="rounded-1 p-4 shadow-sm glass-inner-box d-flex flex-column justify-content-center" style="min-height: 400px;">
                            <div class="text-center text-white-50">
                                <h4 class="mb-3 opacity-50 fw-bold">CHƯA CÓ DỮ LIỆU</h4>
                                <p class="mb-0">Hệ thống đang chờ lệnh. Vui lòng quét ảnh để tiếp tục.</p>
                            </div>
                        </div>

                        <div id="export_loading" class="rounded-1 p-4 shadow-sm glass-inner-box d-none flex-column justify-content-center align-items-center" style="min-height: 400px;">
                            <div class="spinner-border text-light mb-3"></div>
                            <h6 class="text-white fw-bold">AI ĐANG PHÂN TÍCH...</h6>
                        </div>

                        <div id="export_ai_form" class="rounded-1 p-4 shadow-sm glass-inner-box d-none" style="min-height: 400px;">
                            <div class="alert py-2 rounded-1 small mb-3 bg-transparent border border-success text-success" id="export_ai_alert"></div>

                            <h5 id="exp_product_name" class="fw-bold text-white mb-1">...</h5>
                            <p id="exp_brand_name" class="small mb-3 fw-bold text-uppercase">...</p>
                            <hr class="border-secondary">

                            <form id="final_export_form" onsubmit="executeExportStockAI(event)">
                                <input type="hidden" name="product_id" id="exp_product_id">

                                <div id="export_variants_container">
                                </div>

                                <button type="button" class="btn btn-glass-confirm fw-bold w-100 mb-4 shadow-sm rounded-1 border-dashed" onclick="addExportVariantRow()" style="border-style: dashed;">
                                    + THÊM PHÂN LOẠI XUẤT CÙNG MẪU NÀY
                                </button>

                                <button type="submit" class="btn btn-glass-confirm fw-bold w-100 mb-2" style="letter-spacing: 1px;">XÁC NHẬN TRỪ KHO MẪU NÀY</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header ">
                <h5 class="modal-title fw-bold" id="profileModalLabel">Thông Tin Tài Khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php
                $uModel = new UserModel();
                $uData = $uModel->getUserById($_SESSION['user_id']);
                ?>
                <div class="table-responsive  rounded">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 35%;" class="ps-3">Tài khoản truy cập</th>
                                <td class="fw-bold"><?= $uData['username'] ?></td>
                            </tr>
                            <tr>
                                <th class="ps-3">Họ và tên</th>
                                <td><?= $uData['full_name'] ?></td>
                            </tr>
                            <tr>
                                <th class="ps-3">Quyền hạn</th>
                                <td><span class="badge "><?= $uData['role'] === 'MANAGER' ? 'QUẢN LÝ KHU VỰC' : 'NHÂN VIÊN KHO' ?></span></td>
                            </tr>
                            <tr>
                                <th class="ps-3">Số điện thoại liên hệ</th>
                                <td><?= !empty($uData['phone_number']) ? $uData['phone_number'] : '<span class="text-muted">Chưa cập nhật</span>' ?></td>
                            </tr>
                            <tr>
                                <th class="ps-3">Địa chỉ thường trú</th>
                                <td class="small"><?= !empty($uData['address']) ? $uData['address'] : '<span class="text-muted">Chưa cập nhật</span>' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer  ">
                <button type="button" class="btn btn-outline-secondary fw-bold rounded-1 px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow">
            <form id="formUpdateProfile" action="index.php?page=<?= $_GET['page'] ?? 'dashboard' ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Cập Nhật Thông Tin Bảo Mật</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">

                    <div id="profile-msg-container"></div>

                    <div class="row">
                        <div class="col-md-5">
                            <h6 class="mb-3 fw-bold text-dark pb-2">Thông tin lưu trữ hiện tại</h6>
                            <div class="t p-3 rounded">
                                <table class="table">
                                    <tbody>
                                        <tr>
                                            <td class="text-secondary fw-bold">SĐT:</td>
                                            <td class="text-end"><?= !empty($uData['phone_number']) ? htmlspecialchars($uData['phone_number']) : '<span class="text-muted fw-normal">Trống</span>' ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-bold">Địa chỉ:</td>
                                            <td class="text-end"><?= !empty($uData['address']) ? htmlspecialchars($uData['address']) : '<span class="text-muted fw-normal">Trống</span>' ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-7 ps-4">
                            <h6 class="fw-bold mb-3 text-dark pb-2">Biểu mẫu thay đổi dữ liệu</h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">Số điện thoại mới</label>
                                <input type="text" name="phone_number" class="form-control rounded-1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">Địa chỉ mới</label>
                                <textarea name="address" class="form-control rounded-1" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">Mật khẩu mới (Bỏ trống nếu không đổi)</label>
                                <input type="password" name="new_password" class="form-control rounded-1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-danger mb-2">XÁC THỰC QUYỀN TRUY CẬP</label>
                                <input type="password" name="old_password" class="form-control rounded-1" required placeholder="Nhập mật khẩu hiện tại để lưu">
                                <small class="text-muted mt-1 d-block">Đây là bước bắt buộc để đảm bảo an toàn dữ liệu kho.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary fw-bold rounded-1 px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" name="btn_update_profile" class=" btn btn-outline-secondary fw-bold rounded-1 px-4">Lưu Dữ Liệu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* CSS Tinh gọn, rõ nét, chuẩn form nghiệp vụ */
    #searchResults .list-group-item {
        border: none;
        border-bottom: 1px solid #dee2e6;
        transition: background 0.1s;
    }

    #searchResults .list-group-item:hover {
        background-color: #f8f9fa;
        padding-left: 18px;
    }

    .upload-zone {
        transition: border-color 0.2s;
    }

    .upload-zone:hover {
        border-color: #ffffff !important;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- GIỮ NGUYÊN LOGIC TÌM KIẾM ---
        const searchInput = document.getElementById('mainSearch');
        const resultsBox = document.getElementById('searchResults');

        searchInput.addEventListener('input', function() {
            let keyword = this.value.trim();
            if (keyword.length > 0) {
                fetch(`index.php?page=search_ajax&keyword=${encodeURIComponent(keyword)}`)
                    .then(response => response.json())
                    .then(data => {
                        resultsBox.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(item => {
                                resultsBox.innerHTML += `<a href="index.php?page=products&category_id=${item.category_id}#product_${item.product_id}" class="list-group-item list-group-item-action d-flex align-items-center"><img src="assets/img_product/${item.product_image || 'default_shoe.png'}" style="width: 50px; height: 40px; object-fit: contain;" class="rounded-1  me-3"><div><div class="fw-bold text-dark mb-0">${item.product_name}</div><span class="text-secondary small">Xem chi tiết tồn kho</span></div></a>`;
                            });
                            resultsBox.classList.remove('d-none');
                        } else {
                            resultsBox.innerHTML = '<div class="list-group-item text-secondary text-center py-3 fw-bold">Không tìm thấy thông tin sản phẩm khớp.</div>';
                            resultsBox.classList.remove('d-none');
                        }
                    });
            } else {
                resultsBox.classList.add('d-none');
            }
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) resultsBox.classList.add('d-none');
        });
    });

</script>
<script src="assets/js/import_export_product.js"></script>
<script src="assets/js/profile.js"></script>
