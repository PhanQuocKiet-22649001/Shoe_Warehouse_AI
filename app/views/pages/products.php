<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<div class="container-fluid p-0">
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
        <a href="index.php?page=categories" class="btn btn-dark px-4 rounded-pill fw-bold shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Quay lại
        </a>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $pro): ?>
                <div class="col">
                    <div class="card brand-card h-100 border-0 shadow-sm">
                        <div class="brand-logo-wrapper py-4 d-flex align-items-center justify-content-center position-relative"
                            style="height: 220px; background-color: #ffffff; border-radius: 12px 12px 0 0;">

                            <?php
                            $isActive = ($pro['status'] == 't' || $pro['status'] === true || $pro['status'] == 1);
                            ?>

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
                                        <input type="hidden" name="current_status" value="<?= $pro['status'] ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-link p-0 shadow-none ms-1"
                                            style="color: <?= $isActive ? '#61839D' : '#adb5bd' ?>; text-decoration: none;" title="Đổi trạng thái">
                                            <i class="fas <?= $isActive ? 'fa-toggle-on' : 'fa-toggle-off' ?> fa-2x"></i>
                                        </button>
                                    </form>

                                    <form action="index.php?page=products&category_id=<?= $pro['category_id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Bồ chắc chắn muốn cho đôi này vào kho lưu trữ (Xóa mềm) không?')">
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

                <!-- modal xem biến thể -->
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
                                                            <img src="<?= $imagePath ?>" 
                                                                 class="rounded border shadow-sm" style="width: 60px; height: 45px; object-fit: cover;">
                                                        </td>
                                                        <td><?= $var['sku'] ?></td>
                                                        <td class="small"><?= htmlspecialchars($pro['product_name']) ?></td>
                                                        <td><?= htmlspecialchars($var['color']) ?></td>
                                                        <td class="text-center">
                                                            <span><?= $var['size'] ?></span>
                                                        </td>
                                                        <td><?= $var['stock'] ?></td>
                                                        <td class="text-center pe-4">
                                                            <div class="d-flex justify-content-center gap-2">
                                                                <button class="btn btn-sm btn-light border shadow-sm" title="Bật/Tắt"><i class="fas fa-toggle-on"></i></button>
                                                                <button class="btn btn-sm btn-light border shadow-sm" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-5 text-muted">
                                                        <i class="fas fa-info-circle me-1"></i> Đôi này hiện chưa có thông tin size/màu trong kho!
                                                    </td>
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
                <p class="text-muted">Hãng này hiện chưa có đôi giày nào trong kho bồ ơi!</p>
            </div>
        <?php endif; ?>
    </div>
</div>