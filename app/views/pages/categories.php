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

    <div class="category-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title fw-bold text-dark mb-1">Quản lý danh mục</h1>
            <p class="page-subtitle text-muted mb-0">Điều chỉnh thương hiệu và trạng thái kinh doanh</p>
        </div>
        <?php if ($_SESSION['role'] === 'MANAGER'): ?>
            <button class="btn btn-dark px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus me-2"></i> Thêm hãng mới
            </button>
        <?php endif; ?>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat):
                // Kiểm tra trạng thái (Postgres trả về 't'/'f' hoặc true/false)
                $isActive = ($cat['status'] == 't' || $cat['status'] === true || $cat['status'] == 1);
            ?>
                <div class="col">
                    <div class="card brand-card h-100 border-0 shadow-sm text-center <?= !$isActive ? 'opacity-75' : '' ?>">

                        <div class="brand-logo-wrapper py-4  d-flex align-items-center justify-content-center position-relative" style="height: 160px; border-radius: 12px 12px 0 0;">
                            <!-- trạng thái danh mục -->
                            <span class="position-absolute top-0 start-0 m-2 badge rounded-pill shadow-sm"
                                style="background-color: <?= $isActive ? '#ffffff' : '#6c757d' ?>; color: #000000; border: 1px solid black;">
                                <i class="fas <?= $isActive ? 'fa-check-circle' : 'fa-pause-circle' ?> me-1"></i>
                                <?= $isActive ? 'Đang kinh doanh' : 'Tạm ngưng' ?>
                            </span>

                            <?php
                            $logoFileName = !empty($cat['logo']) ? $cat['logo'] : 'default_brand.png';
                            $logoPath = "assets/img_logo/" . $logoFileName;
                            ?>
                            <img src="<?= $logoPath ?>"
                                alt="<?= htmlspecialchars($cat['category_name']) ?>"
                               
                                onerror="this.src='assets/img_logo/default_brand.png'">
                        </div>

                        <div class="card-body p-4">
                            <h5 class="fw-bold text-uppercase mb-3 <?= !$isActive ? 'text-muted' : 'text-dark' ?>">
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </h5>

                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <a href="index.php?page=products&category_id=<?= $cat['category_id'] ?>"
                                    class="btn btn-glass-status btn-sm px-4 rounded-pill fw-bold">
                                    Kho hàng
                                </a>
                                <?php if ($_SESSION['role'] === 'MANAGER'): ?>
                                    <form action="index.php?page=categories" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái kinh doanh của hãng này?')">
                                        <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $cat['status'] ?>">

                                        <!-- nút bật/tắt trang thái -->
                                        <button type="submit" name="toggle_status"
                                            class="btn p-0 ms-2 shadow-none"
                                            style="color: <?= $isActive ? '#61839D' : '#adb5bd' ?>; text-decoration: none;"
                                            title="Đổi trạng thái">
                                            <i class="fas <?= $isActive ? 'fa-toggle-on' : 'fa-toggle-off' ?> fa-2x"></i>
                                        </button>
                                    </form>

                                    <!-- nút xóa -->
                                    <form action="index.php?page=categories" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?')">
                                        <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                                        <button type="submit" name="delete" class="btn btn-link" style="color: grey; margin-bottom: 3px;">
                                            <i class="fas fa-trash-alt fa-lg"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">Chưa có hãng giày nào. Nhấn "Thêm hãng mới" đi bồ!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="index.php?page=categories" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-tag me-2"></i>Thêm hãng giày mới</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Tên hãng giày</label>
                        <input type="text" name="category_name" class="form-control form-control-lg border-2" placeholder="Ví dụ: Nike, Adidas..." required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark">Logo thương hiệu (Tùy chọn)</label>
                        <input type="file" name="logo" class="form-control border-2" accept="image/*">
                        <div class="form-text mt-2">
                            <i class="fas fa-info-circle me-1"></i> Để trống để dùng logo mặc định.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_category" class="btn btn-dark px-5 fw-bold shadow">
                        Lưu lại ngay
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
