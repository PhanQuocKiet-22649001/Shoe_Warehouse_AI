<div class="topbar mb-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">

    <!-- form tìm kiếm -->
    <div class="search-box flex-grow-1 me-4 position-relative">
        <input type="text" id="mainSearch" class="form-control" placeholder="Tìm tên giày, size, màu sắc..." style="max-width: 400px;" autocomplete="off">

        <div id="searchResults" class="list-group position-absolute w-100 shadow-lg mt-1 d-none"
            style="z-index: 1050; max-width: 400px; max-height: 400px; overflow-y: auto; border-radius: 8px;">
        </div>
    </div>

    <div class="user-info d-flex align-items-center">
        <button class="btn me-3 d-flex align-items-center" style="color: #F1F5F9; background-color: #1F2937; border-radius: 8px;">
            <i class="fas fa-robot me-2"></i> AI Trợ Lý Kho
        </button>

        <div class="dropdown">
            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-2 border cursor-pointer dropdown-toggle"
                id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                <i class="fas fa-user-circle me-2 text-secondary"></i>
                <span class="fw-bold text-dark"><?= $_SESSION['full_name'] ?? 'Người dùng' ?></span>
            </div>

            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenu">
                <li>
                    <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                        Xem thông tin cá nhân
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#updateProfileModal">
                        Cập nhật thông tin
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>


<!-- modal xem thông tin -->

<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="profileModalLabel">Hồ Sơ Của Tôi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php
                // Lấy dữ liệu user hiện tại
                $uModel = new UserModel();
                $uData = $uModel->getUserById($_SESSION['user_id']);
                ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 40%;">Tài khoản</th>
                                <td><?= $uData['username'] ?></td>
                            </tr>
                            <tr>
                                <th>Họ và tên</th>
                                <td><?= $uData['full_name'] ?></td>
                            </tr>
                            <tr>
                                <th>Chức vụ</th>
                                <td>
                                    <span style="color: black;">
                                        <?= $uData['role'] === 'MANAGER' ? 'Quản lý' : 'Nhân viên' ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Số điện thoại</th>
                                <td class="text-dark"><?= !empty($uData['phone_number']) ? $uData['phone_number'] : '<em>Chưa cập nhật</em>' ?></td>
                            </tr>
                            <tr>
                                <th>Địa chỉ</th>
                                <td class="text-dark small"><?= !empty($uData['address']) ? $uData['address'] : '<em>Chưa cập nhật</em>' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>



<!-- thông báo -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 mx-3 mt-2" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle me-2 fs-4"></i>
            <div>
                <strong>Thành công!</strong> <?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- modal cập nhật -->

<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="index.php?page=<?= $_GET['page'] ?? 'dashboard' ?>" method="POST" onsubmit="return confirm('Bồ chắc chắn muốn lưu các thay đổi này chứ?')">

                <div class="modal-header" style="background-color: #1F2937;">
                    <h5 class="modal-title fw-bold" style="color: white;">
                        Cập Nhật Hồ Sơ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color: white;"></button>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="row">
                        <div class="col-md-5 border-end">
                            <h6 class="mb-3 text-uppercase small">Thông tin hiện tại</h6>
                            <div class="bg-light p-3 rounded mb-3">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td style="width: 80px;">SĐT:</td>
                                            <td>
                                                <?= !empty($uData['phone_number']) ? htmlspecialchars($uData['phone_number']) : '<span class="text-muted fw-normal">Chưa có</span>' ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Địa chỉ:</td>
                                            <td>
                                                <?= !empty($uData['address']) ? htmlspecialchars($uData['address']) : '<span class="text-muted fw-normal">Chưa có</span>' ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <h6 class="fw-bold mb-3 text-uppercase small" style="color: #1F2937;">Nhập thông tin mới</h6>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Số điện thoại mới</label>
                                <input type="text" name="phone_number" class="form-control form-control-sm rounded-pill border-light-subtle">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Địa chỉ mới</label>
                                <textarea name="address" class="form-control form-control-sm border-light-subtle" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Mật khẩu mới</label>
                                <input type="password" name="new_password" class="form-control form-control-sm rounded-pill border-light-subtle">
                            </div>

                            <hr class="my-4 opacity-50">

                            <div class="p-3 rounded border"
                                style="background-color: #3b495e1b;  border-color: <?= isset($_SESSION['error']) ? '#1F2937' : '#1F2937' ?> !important;">

                                <label class="form-label small fw-bold" style="color: #1F2937;">
                                    <i class="fas fa-lock me-1 <?= isset($_SESSION['error']) ?: '' ?>"></i>
                                    Xác nhận mật khẩu cũ để lưu
                                </label>

                                <input type="password" name="old_password"
                                    class="form-control rounded-pill border-light-subtle <?= isset($_SESSION['error']) ? '' : '' ?>"
                                    required placeholder="Nhập pass hiện tại">

                                <?php if (isset($_SESSION['profile_error'])): ?>
                                    <div class="alert alert-danger ...">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?= $_SESSION['profile_error'];
                                        unset($_SESSION['profile_error']); ?>
                                    </div>
                                    <?php $hasUpdateError = true; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-white pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="btn_update_profile" class="btn px-4 rounded-pill fw-bold shadow-sm"
                        style="background-color: #1F2937; color: #F1F5F9;">
                        Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (isset($hasUpdateError)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var myModal = new bootstrap.Modal(document.getElementById('updateProfileModal'));
            myModal.show();
        });
    </script>
<?php endif; ?>


<!-- script tìm kiếm -->
<style>
    /* CSS cho hộp kết quả */
    #searchResults .list-group-item {
        border: none;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
    }

    #searchResults .list-group-item:hover {
        background-color: #f8fafc;
        padding-left: 20px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
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
                                resultsBox.innerHTML += `
                                <a href="index.php?page=products&category_id=${item.category_id}#product_${item.product_id}" 
                                   class="list-group-item list-group-item-action d-flex align-items-center">
                                    <img src="assets/img_product/${item.product_image || 'default_shoe.png'}" 
                                         style="width: 45px; height: 35px; object-fit: cover;" class="rounded me-3 border">
                                    <div>
                                        <div class="fw-bold small text-dark mb-0">${item.product_name}</div>
                                        <span class="text-muted" style="font-size: 11px;">Nhấn để xem chi tiết</span>
                                    </div>
                                </a>`;
                            });
                            resultsBox.classList.remove('d-none');
                        } else {
                            resultsBox.innerHTML = '<div class="list-group-item small text-muted text-center py-3">Không tìm thấy đôi nào bồ ơi!</div>';
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