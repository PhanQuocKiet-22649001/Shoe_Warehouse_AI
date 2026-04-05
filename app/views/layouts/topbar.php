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
                Quét Ảnh & Nhập Kho
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
                    <h5 class="modal-title fw-bold text-white text-uppercase">Phân Hệ Đối Soát Chứng Từ Kho</h5>
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

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show  shadow-sm mb-4 mx-3 mt-2 rounded-1" role="alert">
        <div class="d-flex align-items-center">
            <div><strong>Thành công:</strong> <?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show  shadow-sm mb-4 mx-3 mt-2 rounded-1" role="alert">
        <div class="d-flex align-items-center">
            <div><strong>Cảnh báo hệ thống:</strong> <?= $_SESSION['error'];
                                                        unset($_SESSION['error']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content  shadow">
            <form action="index.php?page=<?= $_GET['page'] ?? 'dashboard' ?>" method="POST" onsubmit="return confirm('Xác nhận lưu các thay đổi này vào hệ thống?')">
                <div class="modal-header  ">
                    <h5 class="modal-title fw-bold">Cập Nhật Thông Tin Bảo Mật</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-5">
                            <h6 class="mb-3 fw-bold text-dark  pb-2">Thông tin lưu trữ hiện tại</h6>
                            <div class="t p-3 rounded ">
                                <table class="table ">
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
                                <input type="text" name="phone_number" class="form-control rounded-1 ">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">Địa chỉ mới</label>
                                <textarea name="address" class="form-control rounded-1" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">Mật khẩu mới (Bỏ trống nếu không đổi)</label>
                                <input type="password" name="new_password" class="form-control rounded-1 ">
                            </div>

                            <div class="p-3 mt-4 rounded">
                                <label class="form-label fw-bold text-danger mb-2">XÁC THỰC QUYỀN TRUY CẬP</label>
                                <input type="password" name="old_password" class="form-control rounded-1 " required placeholder="Nhập mật khẩu hiện tại của bạn để lưu">
                                <small class="text-muted mt-1 d-block">Đây là bước bắt buộc để đảm bảo an toàn dữ liệu kho.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer  ">
                    <button type="button" class="btn btn-outline-secondary fw-bold rounded-1 px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" name="btn_update_profile" class="btn btn-outline-secondary fw-bold rounded-1 px-4">Lưu Dữ Liệu</button>
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

    // --- BIẾN TOÀN CỤC ---
    let globalScannedData = [];
    let currentSelectedIndex = 0;
    let selectedFilesArray = [];
    let importMode = 'ai'; // Mặc định là AI


    // BẮT SỰ KIỆN CHUYỂN TAB
    document.querySelectorAll('#importTabs .nav-link').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            // Đổi active
            document.querySelectorAll('#importTabs .nav-link').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            importMode = this.getAttribute('data-mode');

            // Reset toàn bộ UI
            selectedFilesArray = [];
            globalScannedData = [];
            currentSelectedIndex = 0;
            renderPreScanThumbnails();
            document.getElementById('post_scan_area').classList.add('d-none');
            document.getElementById('active_form_container').innerHTML = `
                <div class="text-center text-white-50 py-5 mt-4">
                    <h4 class="mb-3 opacity-50 fw-bold">CHƯA CÓ DỮ LIỆU</h4>
                    <p class="mb-0">Hệ thống đang chờ lệnh. Vui lòng tải tệp để tiếp tục.</p>
                </div>`;

            // Đổi text nút bấm & Gợi ý
            const btn = document.getElementById('btn-scan-batch');
            const note = document.querySelector('.upload-zone p');
            const btnSelect = document.querySelector('.upload-zone button');

            if (importMode === 'manual') {
                btn.innerHTML = '<i class="fas fa-edit me-2"></i>TIẾN HÀNH NHẬP LIỆU';
                btnSelect.innerHTML = 'CHỌN TỆP HÌNH ẢNH (TỐI ĐA 10)';
                note.innerHTML = '*Lưu ý: Chế độ thủ công cho phép nhập tối đa 10 ảnh cùng lúc.';
            } else {
                btn.innerHTML = '<i class="fas fa-magic me-2"></i>BẮT ĐẦU XỬ LÝ AI';
                btnSelect.innerHTML = 'CHỌN TỆP HÌNH ẢNH (TỐI ĐA 3)';
                note.innerHTML = '*Lưu ý: Để chọn nhiều ảnh, vui lòng giữ phím Ctrl và click chọn các ảnh cùng lúc.';
            }
        });
    });


    function previewSelectedImages(input) {
        const newFiles = Array.from(input.files);
        const maxLimit = importMode === 'ai' ? 3 : 10; // AI max 3, Thủ công max 10

        for (let file of newFiles) {
            if (selectedFilesArray.length < maxLimit) {
                selectedFilesArray.push(file);
            } else {
                alert(`Chế độ này chỉ hỗ trợ tối đa ${maxLimit} ảnh cùng lúc!`);
                break;
            }
        }
        input.value = '';
        renderPreScanThumbnails();
    }

    function renderPreScanThumbnails() {
        const container = document.getElementById('pre_scan_preview');
        container.innerHTML = '';
        selectedFilesArray.forEach((file, index) => {
            const fileUrl = URL.createObjectURL(file);
            container.innerHTML += `
                <div class="position-relative  rounded-1 " style="width: 120px; height: 120px;">
                    <img src="${fileUrl}" style="width: 100%; height: 100%; object-fit: contain; background-color: #f8f9fa;">
                    <button type="button" class="btn btn-danger p-0 position-absolute " 
                            style="top: 4px; right: 4px; width: 22px; height: 22px; font-size: 12px; font-weight: bold; border-radius: 2px;"
                            onclick="removeSelectedFile(${index})" title="Loại bỏ ảnh này">X</button>
                </div>`;
        });
    }

    function removeSelectedFile(index) {
        selectedFilesArray.splice(index, 1);
        renderPreScanThumbnails();
    }

    // async function executeBatchScan() {
    //     if (selectedFilesArray.length === 0) return alert("Vui lòng đính kèm tệp hình ảnh để xử lý.");

    //     const btn = document.getElementById('btn-scan-batch');
    //     const formContainer = document.getElementById('active_form_container');
    //     const preScanArea = document.getElementById('pre_scan_preview');

    //     // NẾU LÀ CHẾ ĐỘ THỦ CÔNG -> BỎ QUA GỌI AI, HIỆN FORM LUÔN
    //     if (importMode === 'manual') {
    //         btn.innerHTML = 'ĐANG TẠO BIỂU MẪU...';
    //         preScanArea.innerHTML = '';
    //         document.getElementById('post_scan_area').classList.add('d-none');

    //         // Tạo mảng dữ liệu rỗng (Không có AI matches)
    //         globalScannedData = selectedFilesArray.map(f => ({
    //             temp_image: "",
    //             vector: null,
    //             matches: [],
    //             similarity: 0,
    //             status: "manual",
    //             fileObj: f // Lưu lại file gốc để xíu gửi lên PHP
    //         }));

    //         renderThumbnails();
    //         loadFormForIndex(0);
    //         btn.innerHTML = '<i class="fas fa-edit me-2"></i>TIẾN HÀNH NHẬP LIỆU';
    //         return;
    //     }

    //     // --- NẾU LÀ AI THÌ CHẠY LOGIC CŨ Ở ĐÂY ---
    //     btn.disabled = true;
    //     btn.innerHTML = 'ĐANG XỬ LÝ...';
    //     preScanArea.innerHTML = '';
    //     document.getElementById('post_scan_area').classList.add('d-none');
    //     formContainer.innerHTML = '<div class="text-center py-5"><p class="fw-bold">Hệ thống đang truy xuất dữ liệu kho...</p></div>';

    //     const fd = new FormData();
    //     selectedFilesArray.forEach(f => fd.append('images[]', f));

    //     try {
    //         const response = await fetch('index.php?page=products&action=scan-ai', {
    //             method: 'POST',
    //             body: fd
    //         });
    //         const res = await response.json();
    //         if (res.status === 'success') {
    //             globalScannedData = res.data;
    //             renderThumbnails();
    //             if (globalScannedData.length > 0) loadFormForIndex(0);
    //         } else {
    //             alert("Lỗi xử lý: " + res.message);
    //             formContainer.innerHTML = '<div class="text-center py-5 fw-bold text-danger">Tiến trình thất bại.</div>';
    //         }
    //     } catch (e) {
    //         alert("Mất kết nối máy chủ.");
    //     } finally {
    //         btn.disabled = false;
    //         btn.innerHTML = '<i class="fas fa-magic me-2"></i>BẮT ĐẦU XỬ LÝ AI';
    //     }
    // }

    async function executeBatchScan() {
        if (selectedFilesArray.length === 0) return alert("Vui lòng đính kèm tệp hình ảnh để xử lý.");

        const btn = document.getElementById('btn-scan-batch');
        const formContainer = document.getElementById('active_form_container');
        const preScanArea = document.getElementById('pre_scan_preview');

        // NẾU LÀ CHẾ ĐỘ THỦ CÔNG -> BỎ QUA GỌI AI
        if (importMode === 'manual') {
            btn.innerHTML = 'ĐANG TẠO BIỂU MẪU...';
            preScanArea.innerHTML = '';
            document.getElementById('post_scan_area').classList.add('d-none');

            globalScannedData = selectedFilesArray.map(f => ({
                temp_image: "",
                vector: null,
                matches: [],
                similarity: 0,
                status: "manual",
                fileObj: f
            }));

            renderThumbnails();
            loadFormForIndex(0);
            btn.innerHTML = '<i class="fas fa-edit me-2"></i>TIẾN HÀNH NHẬP LIỆU';
            return;
        }

        // --- NẾU LÀ AI ---
        btn.disabled = true;
        btn.innerHTML = 'ĐANG XỬ LÝ...';
        preScanArea.innerHTML = '';
        document.getElementById('post_scan_area').classList.add('d-none');
        formContainer.innerHTML = '<div class="text-center py-5"><p class="fw-bold">Hệ thống đang truy xuất dữ liệu kho...</p></div>';

        const fd = new FormData();
        selectedFilesArray.forEach(f => fd.append('images[]', f));

        try {
            const response = await fetch('index.php?page=products&action=scan-ai', {
                method: 'POST',
                body: fd
            });

            // KỸ THUẬT DEBUG: Lấy text thô từ Server trước khi ép sang JSON
            const rawText = await response.text();

            try {
                // Thử ép sang JSON
                const res = JSON.parse(rawText);
                if (res.status === 'success') {
                    globalScannedData = res.data;
                    renderThumbnails();
                    if (globalScannedData.length > 0) loadFormForIndex(0);
                } else {
                    alert("Lỗi xử lý: " + res.message);
                    formContainer.innerHTML = '<div class="text-center py-5 fw-bold text-danger">Tiến trình thất bại.</div>';
                }
            } catch (parseError) {
                // CHÌA KHÓA Ở ĐÂY: Nếu PHP bị lỗi (dư dấu ngoặc, sai cú pháp), nó in ra HTML
                // JS sẽ in thẳng cục HTML đó ra màn hình để bồ biết đường mà sửa!
                console.error("LỖI PHP TRẢ VỀ:", rawText);
                formContainer.innerHTML = `
                    <div class="p-4 text-danger bg-dark rounded-1 text-start" style="overflow-y: auto; max-height: 400px;">
                        <h6 class="fw-bold text-warning"><i class="fas fa-bug me-2"></i>SERVER PHP BÁO LỖI:</h6>
                        <pre style="white-space: pre-wrap; font-size: 13px; color: #ff6b6b;">${rawText}</pre>
                    </div>`;
            }
        } catch (e) {
            alert("Lỗi mạng! Không thể gửi request đến server.");
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic me-2"></i>BẮT ĐẦU XỬ LÝ AI';
        }
    }

    function renderThumbnails() {
        const postScanArea = document.getElementById('post_scan_area');
        const container = document.getElementById('scanned_thumbnails');

        // NẾU HẾT DỮ LIỆU THÌ HIỆN THÔNG BÁO HOÀN TẤT
        if (globalScannedData.length === 0) {
            postScanArea.classList.add('d-none');
            document.getElementById('active_form_container').innerHTML = `
                <div class="text-center py-5">
                    <h4 class=" fw-bold">HOÀN TẤT!</h4>
                    <p>Đã nhập kho xong toàn bộ sản phẩm.</p>
                    <button class="btn btn-dark btn-sm rounded-1" onclick="window.location.reload()">CẬP NHẬT DANH SÁCH KHO</button>
                </div>`;
            return;
        }

        postScanArea.classList.remove('d-none');
        container.innerHTML = '';
        globalScannedData.forEach((item, index) => {
            let borderClass = (index === currentSelectedIndex) ? '  opacity-100 shadow' : ' opacity-50';
            let localUrl = URL.createObjectURL(selectedFilesArray[index]);
            container.innerHTML += `<img src="${localUrl}" onclick="loadFormForIndex(${index})" class="rounded-1  ${borderClass}" style="width: 100px; height: 100px; object-fit:contain; background-color:#f8f9fa; cursor:pointer; transition: 0.2s;">`;
        });
    }

    // --- HÀM LƯU AJAX (GIẢI QUYẾT VẤN ĐỀ CỦA BẠN) ---
    async function saveProductByAJAX(event, form) {
        event.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        formData.append('add_product', '1');

        // KỸ THUẬT QUAN TRỌNG: Nếu nhập thủ công, nhét file ảnh thực tế vào FormData để gửi lên PHP
        if (importMode === 'manual' && globalScannedData[currentSelectedIndex].fileObj) {
            formData.append('manual_image', globalScannedData[currentSelectedIndex].fileObj);
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> ĐANG LƯU...';

        try {
            const response = await fetch('index.php?page=products&action=add', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();

            if (res.status === 'success') {
                globalScannedData.splice(currentSelectedIndex, 1);
                selectedFilesArray.splice(currentSelectedIndex, 1);
                alert("Lưu thành công!");

                if (globalScannedData.length > 0) {
                    currentSelectedIndex = 0;
                    loadFormForIndex(0);
                } else {
                    renderThumbnails(); // Hiện Hoàn tất
                }
            } else {
                alert("Lỗi: " + res.message);
                btn.disabled = false;
                btn.innerHTML = 'XÁC NHẬN LƯU DỮ LIỆU';
            }
        } catch (e) {
            alert("Lỗi hệ thống, vui lòng thử lại.");
            btn.disabled = false;
        }
    }

    // --- GIỮ NGUYÊN TOÀN BỘ LOGIC FORM CŨ CỦA BẠN ---
    function loadFormForIndex(index) {
        currentSelectedIndex = index;
        renderThumbnails();

        const item = globalScannedData[index];
        const container = document.getElementById('active_form_container');
        let localUrl = URL.createObjectURL(selectedFilesArray[index]);

        const vectorStr = (item.vector && Array.isArray(item.vector)) ? JSON.stringify(item.vector) : "";
        const imageName = item.temp_image || ""; // Thủ công thì rỗng

        let alertMessage = "";
        let suggestionHtml = "";
        let defaultBrand = "";
        let defaultName = "";

        // --- KHỞI TẠO BIẾN CHO GIAO DIỆN MÀU SẮC ---
        let colorContainerHtml = `<input type="text" name="color" class="form-control fw-bold rounded-1" placeholder="Nhập màu sắc" required>`;

        // ============================================
        // PHÂN LOẠI: NẾU LÀ THỦ CÔNG HOẶC NẾU LÀ AI
        // ============================================
        if (importMode === 'manual') {
            alertMessage = `<div class="alert alert-glass-blink py-2 rounded-1 mb-3"><strong>NHẬP THỦ CÔNG:</strong> Vui lòng tự khai báo thông tin.</div>`;
        } else {
            const matches = item.matches || [];
            const topMatch = matches.length > 0 ? matches[0] : null;
            const topScore = topMatch ? Math.round(topMatch.similarity_score * 100) : 0;

            if (topScore >= 95) {
                alertMessage = `<div class="alert alert-glass-blink py-2 rounded-1 mb-3"><i class="fas fa-check-circle me-2"></i><strong>AI XÁC THỰC:</strong> Khớp tuyệt đối (${topScore}%).</div>`;
                defaultBrand = topMatch.brand;
                defaultName = topMatch.product_name;

                // NẾU KHỚP 100%, CHUYỂN Ô TEXT THÀNH COMBOBOX CÁC MÀU ĐÃ CÓ
                if (item.colors && item.colors.length > 0) {
                    let opts = `<option value="">-- Chọn màu kho --</option>`;
                    item.colors.forEach(c => {
                        opts += `<option value="${c.color}">${c.color}</option>`;
                    });
                    opts += `<option value="new" class="fw-bold"">+ TẠO MÀU MỚI KHÁC...</option>`;

                    colorContainerHtml = `
                        <select name="color" class="form-select fw-bold rounded-1 shadow-sm" onchange="toggleNewColorInput(this)" required>
                            ${opts}
                        </select>
                        <input type="text" name="new_color" id="input_color_new" class="form-control fw-bold rounded-1 mt-2 d-none" placeholder="Gõ tên màu mới...">
                    `;
                }

            } else if (topScore >= 80) {
                alertMessage = `<div class="alert alert-info py-2 rounded-1 mb-3 bg-transparent border border-info text-info"><i class="fas fa-lightbulb me-2"></i><strong>AI GỢI Ý:</strong> Mẫu gần giống (${topScore}%). Xem bên dưới.</div>`;
                suggestionHtml = `<div class="suggestion-list mb-3 p-2 rounded" style="max-height: 150px; overflow-y: auto;">`;
                matches.forEach((m) => {
                    // CHÚ Ý Ở ĐÂY: Truyền thêm m.product_id vào hàm click
                    suggestionHtml += `<div class="d-flex align-items-center p-2" style="cursor:pointer;" onclick="fillFormFromSuggestion('${m.brand}', '${m.product_name}', ${m.product_id})">
                                           <img src="assets/img_product/${m.product_image}" style="width:40px; height:40px; object-fit:cover;" class="me-2 rounded">
                                           <div style="font-size: 11px;">
                                               <span class="fw-bold d-block">${m.product_name}</span>
                                               <span class="text-muted">${m.brand} - Khớp ${Math.round(m.similarity_score * 100)}%</span>
                                           </div>
                                       </div>`;
                });
                suggestionHtml += `</div>`;
            } else {
                alertMessage = `<div class="alert alert-glass-blink rounded-1 mb-3"><i class="fas fa-plus-circle me-2"></i><strong>MẪU MỚI:</strong> Không tìm thấy dữ liệu cũ.</div>`;
            }
        }

        // Tạo danh sách Hãng
        let categoryOptions = `<option value="">-- Chọn thương hiệu --</option>`;
        if (typeof categoriesList !== 'undefined') {
            categoriesList.forEach(cat => {
                let isSelected = (cat.category_name.toLowerCase() === defaultBrand.toLowerCase()) ? 'selected' : '';
                categoryOptions += `<option value="${cat.category_id}" ${isSelected}>${cat.category_name.toUpperCase()}</option>`;
            });
        }

        // RENDER FORM (Chú ý thẻ div chứa colorContainerHtml)
        container.innerHTML = `
        <form onsubmit="saveProductByAJAX(event, this)">
            ${alertMessage}
            ${suggestionHtml}
            <input type="hidden" name="vector" value='${vectorStr}'>
            <input type="hidden" name="temp_image_name" value="${imageName}">
            
            <div class="row mb-3 align-items-center">
                <div class="col-md-3"><label class="fw-bold small">ẢNH GỐC:</label></div>
                <div class="col-md-9">
                    <img src="${localUrl}" class="rounded-1" style="width: 80px; height: 80px; object-fit:contain; background-color: #f8f9fa;">
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold small mb-1">HÃNG SẢN XUẤT:</label>
                <select id="input_brand" name="category_id" class="form-select rounded-1 shadow-sm fw-bold" required>
                    ${categoryOptions}
                </select>
            </div>

            <div class="mb-3">
                <label class="fw-bold small mb-1">TÊN DÒNG SẢN PHẨM:</label>
                <input type="text" id="input_product_name" name="product_name" class="form-control fw-bold rounded-1 shadow-sm" value="${defaultName}" placeholder="Nhập tên giày" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="fw-bold small mb-1">MÀU SẮC:</label>
                    <div id="color_input_wrapper">
                        ${colorContainerHtml}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="fw-bold small mb-1">SIZE:</label>
                    <input type="number" name="size" class="form-control fw-bold text-center rounded-1" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="fw-bold small mb-1">SỐ LƯỢNG NHẬP KHO:</label>
                <input type="number" name="stock" class="form-control fw-bold text-center rounded-1" value="1" min="1" required>
            </div>

            <button type="submit" class="btn btn-glass-confirm w-100 fw-bold rounded-1 py-2 shadow-sm">XÁC NHẬN LƯU DỮ LIỆU</button>
        </form>`;
    }

    async function fillFormFromSuggestion(brandName, productName, productId) {
        const selectBrand = document.getElementById('input_brand');
        document.getElementById('input_product_name').value = productName;

        // Chọn Hãng
        for (let i = 0; i < selectBrand.options.length; i++) {
            if (selectBrand.options[i].text.toLowerCase() === brandName.toLowerCase()) {
                selectBrand.selectedIndex = i;
                break;
            }
        }

        // Hiệu ứng Highlight báo hiệu đã chọn
        const inputs = [selectBrand, document.getElementById('input_product_name')];
        inputs.forEach(el => {
            el.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
            setTimeout(() => el.style.backgroundColor = '', 500);
        });

        // --- GỌI API LẤY MÀU SẮC CỦA SẢN PHẨM VỪA CHỌN ---
        const colorWrapper = document.getElementById('color_input_wrapper');
        colorWrapper.innerHTML = '<span class="text-white-50 small"><i class="fas fa-spinner fa-spin"></i> Đang tải màu...</span>';

        try {
            // Chú ý: Đảm bảo URL này gọi đúng hàm getColorsAjax trong Controller của bồ
            const res = await fetch(`index.php?page=products&action=getColorsAjax&product_id=${productId}`);
            const colors = await res.json();

            let opts = `<option value="">-- Chọn màu kho --</option>`;
            if (colors && colors.length > 0) {
                colors.forEach(c => {
                    opts += `<option value="${c.color}">${c.color}</option>`;
                });
            }
            opts += `<option value="new" class="fw-bold" style="color: #00d2ff;">+ TẠO MÀU MỚI KHÁC...</option>`;

            // Render lại ô Select
            colorWrapper.innerHTML = `
                <select name="color" id="input_color_select" class="form-select fw-bold rounded-1 shadow-sm" onchange="toggleNewColorInput(this)" required>
                    ${opts}
                </select>
                <input type="text" name="new_color" id="input_color_new" class="form-control fw-bold rounded-1 mt-2 d-none" placeholder="Gõ tên màu mới...">
            `;

            // Highlight combo màu
            document.getElementById('input_color_select').style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
            setTimeout(() => document.getElementById('input_color_select').style.backgroundColor = '', 500);

        } catch (e) {
            // Lỗi mạng thì trả về ô text bình thường cho người dùng tự gõ
            colorWrapper.innerHTML = '<input type="text" name="color" class="form-control fw-bold rounded-1" placeholder="Màu sắc" required>';
        }
    }

    function toggleNewColorInput(selectObj) {
        const newColorInput = document.getElementById('input_color_new');
        if (selectObj.value === 'new') {
            // Bật ô text lên, ép buộc phải gõ
            newColorInput.classList.remove('d-none');
            newColorInput.setAttribute('required', 'true');
            newColorInput.focus();
        } else {
            // Tắt đi nếu chọn màu có sẵn
            newColorInput.classList.add('d-none');
            newColorInput.removeAttribute('required');
            newColorInput.value = ''; // Xóa rác
        }
    }
</script>