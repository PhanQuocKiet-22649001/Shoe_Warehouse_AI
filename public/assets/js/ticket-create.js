document.addEventListener('DOMContentLoaded', function () {
    // 1. Thêm dòng động
    const btnAddRow = document.getElementById('addRow');
    if (btnAddRow) {
        btnAddRow.addEventListener('click', function () {
            let tbody = document.querySelector('#detailTable tbody');
            let newRow = tbody.rows[0].cloneNode(true);

            // Xóa dữ liệu cũ trên dòng mới copy
            newRow.querySelector('.brand-select').value = '';

            // Đặt lại ảnh về mặc định
            let imgPreview = newRow.querySelector('.img-preview');
            if (imgPreview) imgPreview.src = 'assets/images/placeholder.png';

            let prodSelect = newRow.querySelector('.product-select');
            prodSelect.innerHTML = '<option value="">Chọn Hãng trước</option>';
            prodSelect.disabled = true;

            let varSelect = newRow.querySelector('.variant-select');
            varSelect.innerHTML = '<option value="">Chọn Mẫu trước</option>';
            varSelect.disabled = true;

            newRow.querySelector('input[type="number"]').value = '';

            // Nối dòng mới vào bảng
            tbody.appendChild(newRow);

            // Gắn lại sự kiện cho các dropdown của dòng mới
            attachEvents(newRow);
        });
    }

    // 2. Alert Confirm trước khi submit
    const ticketForm = document.getElementById('ticketForm');
    if (ticketForm) {
        ticketForm.addEventListener('submit', function (e) {
            if (!confirm('Bạn có chắc chắn muốn LƯU phiếu này không? Dữ liệu phiếu sẽ không thể chỉnh sửa sau khi lưu.')) {
                e.preventDefault();
            }
        });
    }

    // 3. Xử lý AJAX liên hoàn Dropdowns (Hãng -> Mẫu -> Biến thể)
    function attachEvents(row) {
        // Sự kiện Xóa dòng
        row.querySelector('.btn-remove').addEventListener('click', function () {
            if (document.querySelectorAll('#detailTable tbody tr').length > 1) {
                row.remove();
            } else {
                alert('Phải có ít nhất 1 dòng sản phẩm trong phiếu!');
            }
        });

        let brandSelect = row.querySelector('.brand-select');
        let prodSelect = row.querySelector('.product-select');
        let varSelect = row.querySelector('.variant-select');
        let imgPreview = row.querySelector('.img-preview'); // Bắt thẻ ảnh

        // Khi chọn Hãng (Brand) -> Load danh sách Mẫu giày
        brandSelect.addEventListener('change', function () {
            let brandId = this.value;
            prodSelect.innerHTML = '<option value="">Đang tải...</option>';
            varSelect.innerHTML = '<option value="">Chọn Mẫu trước</option>';
            varSelect.disabled = true;

            // Nếu đổi hãng thì reset ảnh
            if (imgPreview) imgPreview.src = 'assets/images/placeholder.png';

            if (brandId) {
                fetch(`index.php?page=tickets&action=get_products&brand_id=${brandId}`)
                    .then(res => res.json())
                    .then(data => {
                        prodSelect.disabled = false;
                        prodSelect.innerHTML = '<option value="">Chọn Mẫu Giày</option>';
                        data.forEach(p => {
                            prodSelect.innerHTML += `<option value="${p.product_id}">${p.product_name}</option>`;
                        });
                    });
            } else {
                prodSelect.disabled = true;
            }
        });

        // Khi chọn Mẫu giày -> Load danh sách Biến thể VÀ LẤY ẢNH
        prodSelect.addEventListener('change', function () {
            let prodId = this.value;
            varSelect.innerHTML = '<option value="">Đang tải...</option>';

            if (prodId) {
                fetch(`index.php?page=tickets&action=get_variants&product_id=${prodId}`)
                    .then(res => res.json())
                    .then(data => {
                        varSelect.disabled = false;
                        varSelect.innerHTML = '<option value="">Chọn Màu & Size</option>';

                        // LẤY ẢNH GẮN VÀO GIAO DIỆN Ở ĐÂY NÈ
                        if (data && data.length > 0 && data[0].product_image) {
                            imgPreview.src = `assets/img_product/${data[0].product_image}`;
                        } else {
                            imgPreview.src = 'assets/images/placeholder.png';
                        }

                        data.forEach(v => {
                            let stockInfo = `(Tồn: ${v.stock})`;
                            varSelect.innerHTML += `<option value="${v.variant_id}">${v.color} - Size ${v.size} ${stockInfo}</option>`;
                        });
                    });
            } else {
                varSelect.disabled = true;
                if (imgPreview) imgPreview.src = 'assets/images/placeholder.png';
            }
        });
    }

    // Kích hoạt sự kiện cho dòng đầu tiên khi trang vừa load xong
    let firstRow = document.querySelector('#detailTable tbody tr');
    if (firstRow) {
        attachEvents(firstRow);
    }
});