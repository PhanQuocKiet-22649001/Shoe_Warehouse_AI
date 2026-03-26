<aside class="sidebar">
    <div class="logo">
        SMART<br>WAREHOUSE
    </div>

    <div class="menu">
        <p class="menu-title">MENU CHÍNH (QUẢN LÝ)</p>

        <ul>
            <ul>
                <li class="<?= $page === 'dashboard' ? 'active' : '' ?>" style="margin-bottom: 15px;">
                    <a href="index.php?page=dashboard">
                        Tổng quan
                    </a>
                </li>

                <li class="<?= $page === 'categories' ? 'active' : '' ?>" style="margin-bottom: 15px;">
                    <a href="index.php?page=categories">
                        Quản lý danh mục
                    </a>
                </li>

                <?php if (isset($_SESSION['role']) && strtoupper($_SESSION['role']) === 'MANAGER'): ?>
                    <li class="<?= $page === 'employees' ? 'active' : '' ?>" style="margin-bottom: 15px;">
                        <a href="index.php?page=employees">
                            Quản lí Nhân viên
                        </a>
                    </li>
                <?php endif; ?>

                <li style="margin-bottom: 15px;">
                    <a href="#" class="<?= $page === '' ? 'active' : '' ?>">
                        Yêu cầu xuất kho
                    </a>
                </li>


                <li style="margin-bottom: 15px;">
                    <a href="#" class="<?= $page === '' ? 'active' : '' ?>">
                        Tìm kiếm & Tồn kho
                    </a>
                </li>
                <li style="margin-bottom: 15px;">
                    <a href="#" class="<?= $page === '' ? 'active' : '' ?>">
                        Báo cáo Nhập-Xuất
                    </a>
                </li>
                <li style="margin-bottom: 15px;">
                    <a href="#" class="<?= $page === '' ? 'active' : '' ?>">
                        AI Dự báo & Gợi ý
                    </a>
                </li>
            </ul>
        </ul>
    </div>

    <form method="POST" action="index.php" class="mt-3">
        <button type="submit" name="logout" class="logout w-100">Đăng xuất</button>
    </form>
</aside>