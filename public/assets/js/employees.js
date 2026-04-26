document.addEventListener("DOMContentLoaded", function () {
    
    // Bắt sự kiện khi click vào nút "Sửa"
    const editButtons = document.querySelectorAll('.btn-edit-user');
    
    editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            // Lấy chuỗi JSON từ thuộc tính data-user
            const userDataString = this.getAttribute('data-user');
            const user = JSON.parse(userDataString);

            // Đổ dữ liệu vào các ô input trong Modal Edit
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_full_name').value = user.full_name;
            
            // Format ngày giờ
            if (user.created_at) {
                let date = new Date(user.created_at);
                let formattedDate = date.toLocaleDateString('vi-VN') + ' ' +
                    date.toLocaleTimeString('vi-VN', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                document.getElementById('edit_created_at').value = formattedDate;
            }

            // Xử lý Checkbox Trạng thái
            const isActive = (user.status === 't' || user.status === true || user.status === 1);
            document.getElementById('edit_status').checked = isActive;
        });
    });

});