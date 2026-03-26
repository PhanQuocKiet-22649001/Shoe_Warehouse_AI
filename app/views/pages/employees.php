<?php
// Dữ liệu $users đã được index.php lấy từ Controller
$error = $error ?? '';
?>

<link rel="stylesheet" href="assets/css/employees.css">

<!-- thêm topbar -->
<?php include __DIR__ . '/../layouts/topbar.php'; ?>


<div class="employees-container">

    <div class="employees-header d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">QUẢN LÝ NHÂN VIÊN</h1>
        <?php if ($_SESSION['role'] === 'MANAGER'): ?>
            <button class="btn-add" onclick="openModal('addModal')">
                <i class="fas fa-plus me-2"></i> Thêm nhân viên mới
            </button>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong><?= $_SESSION['success']; ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><?= $_SESSION['error']; ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <table class="employees-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Họ tên</th>
                <th>Username</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['user_id'] ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><span class="badge" style="color: #374151;"><?= ucfirst($user['role']) ?></span></td>
                        <td>
                            <span class="badge <?= ($user['status'] === 't' || $user['status'] === true) ? 'active' : 'inactive' ?>">
                                <?= ($user['status'] === 't' || $user['status'] === true) ? 'Đang hoạt động' : 'Tạm ngưng' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                                <a href="index.php?page=profile" class="btn-profile"
                                    style="text-decoration: none; color: black;">
                                    Cập nhật thông tin
                                </a>
                            <?php else: ?>
                                <?php if ($_SESSION['role'] === 'MANAGER'): ?>
                                    <button class="btn-action btn-edit"
                                        onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                        Sửa
                                    </button>
                                <?php endif; ?>

                                <?php if ($_SESSION['role'] === 'MANAGER' && $user['user_id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="index.php?page=employees" style="display:inline;"
                                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?')">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <button type="submit" name="delete" class="btn-action btn-delete">Xóa</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;">Chưa có nhân viên nào.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- modal thêm nhân viên -->
<div class="modal" id="addModal" style="display:none;">
    <div class="modal-header">
        <h5>Thêm nhân viên mới</h5>
        <span class="modal-close" onclick="closeModal('addModal')">×</span>
    </div>
    <div class="modal-body">
        <form method="POST" action="index.php?page=employees">
            <input type="text" name="full_name" placeholder="Họ tên" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <select name="role">
                <option value="staff">Staff</option>
                <option value="manager">Manager</option>
            </select>
            <div class="form-check mb-3 d-flex align-items-center" style="gap: 10px;">
                <input type="checkbox" name="status" id="status_add" class="form-check-input" checked
                    style="width: 20px !important; height: 20px !important; cursor: pointer; margin: 0;">

                <label class="form-check-label" for="status_add"
                    style="cursor: pointer; margin: 0; white-space: nowrap; line-height: 1;">
                    Kích hoạt tài khoản
                </label>
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_user" class="btn-save">Lưu</button>
                <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Hủy</button>
            </div>
        </form>
    </div>
</div>


<!-- modal sửa nhân viên -->
<div class="modal" id="editModal" style="display:none;">
    <div class="modal-header">
        <h5>Cập nhật trạng thái nhân viên</h5>
        <span class="modal-close" onclick="closeModal('editModal')">×</span>
    </div>
    <div class="modal-body">
        <form method="POST" action="index.php?page=employees"
            onsubmit="return confirm('Bồ có chắc chắn muốn thay đổi trạng thái hoạt động của nhân viên này không?')">

            <input type="hidden" name="user_id" id="edit_user_id">

            <label>Họ tên nhân viên:</label>
            <input type="text" id="edit_full_name" readonly
                style="background-color: #f3f4f6; color: #6b7280; cursor: not-allowed; margin-bottom: 15px;">

            <label>Ngày tạo tài khoản:</label>
            <input type="text" id="edit_created_at" readonly
                style="background-color: #f3f4f6; color: #6b7280; cursor: not-allowed; margin-bottom: 15px;">

            <div class="form-check mb-3">
                <input type="checkbox" name="status" id="edit_status" style="width: 18px; height: 18px; cursor: pointer;">
                <label for="edit_status" style="font-weight: bold; cursor: pointer; margin-left: 8px;">Kích hoạt tài khoản</label>
            </div>

            <div class="modal-footer">
                <button type="submit" name="update" class="btn-save">Cập nhật</button>
                <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Hủy</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Hàm mở/đóng Modal chung
    function openModal(id) {
        document.getElementById(id).style.display = 'block';
        document.getElementById(id + '-backdrop').style.display = 'block';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
        document.getElementById(id + '-backdrop').style.display = 'none';
    }

    // Hàm đổ dữ liệu vào Modal Sửa
    function editUser(user) {
        document.getElementById('edit_user_id').value = user.user_id;
        document.getElementById('edit_full_name').value = user.full_name;
        if (user.created_at) {
            let date = new Date(user.created_at);
            let formattedDate = date.toLocaleDateString('vi-VN') + ' ' +
                date.toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            document.getElementById('edit_created_at').value = formattedDate;
        }
        document.getElementById('edit_status').checked = (user.status === 't' || user.status === true);
        openModal('editModal');
    }
</script>