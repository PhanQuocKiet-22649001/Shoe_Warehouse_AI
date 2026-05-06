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

    // 2. Alert Confirm trước khi submit form tạo phiếu
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

            // Lấy loại phiếu từ input hidden trong form
            let ticketType = document.querySelector('input[name="ticket_type"]').value;

            if (prodId) {
                fetch(`index.php?page=tickets&action=get_variants&product_id=${prodId}&type=${ticketType}`)
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

                        // Hiển thị danh sách
                        if (data.length === 0) {
                            varSelect.innerHTML = '<option value="">(Tạm hết hàng)</option>';
                            varSelect.disabled = true;
                        } else {
                            data.forEach(v => {
                                let stockInfo = `(Tồn: ${v.stock})`;
                                varSelect.innerHTML += `<option value="${v.variant_id}">${v.color} - Size ${v.size} ${stockInfo}</option>`;
                            });
                        }
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

    // =========================================================
    // 4. XỬ LÝ MODAL ĐỔI NHÂN VIÊN TRONG BẢNG LỊCH SỬ PHIẾU
    // =========================================================
    const staffButtons = document.querySelectorAll('.btn-change-staff');
    if (staffButtons.length > 0) {
        staffButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const ticketId = this.getAttribute('data-ticket-id');
                const ticketCode = this.getAttribute('data-ticket-code');
                const staffName = this.getAttribute('data-staff-name'); 

                document.getElementById('modal_ticket_id').value = ticketId;
                document.getElementById('modal_ticket_code').value = ticketCode;
                document.getElementById('modal_current_staff').value = staffName;
            });
        });
    }

    // =========================================================
    // 5. XỬ LÝ MODAL XEM CHI TIẾT PHIẾU BẰNG AJAX
    // =========================================================
    const viewButtons = document.querySelectorAll('.btn-view-details');
    if (viewButtons.length > 0) {
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const ticketId = this.getAttribute('data-ticket-id');
                const ticketCode = this.getAttribute('data-ticket-code');
                
                document.getElementById('view_ticket_code').textContent = ticketCode;
                const tbody = document.getElementById('detailModalBody');
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br>Đang tải dữ liệu...</td></tr>';
                
                fetch(`index.php?page=tickets&action=get_ticket_details&ticket_id=${ticketId}`)
                    .then(res => res.json())
                    .then(data => {
                        tbody.innerHTML = ''; 
                        
                        if (data.status === 'error') {
                            tbody.innerHTML = `<tr><td colspan="5" class="text-danger fw-bold">${data.message}</td></tr>`;
                            return;
                        }

                        if (data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" class="text-muted fst-italic">Phiếu này không có sản phẩm nào!</td></tr>';
                            return;
                        }

                        data.forEach(item => {
                            const imgSrc = item.product_image ? `assets/img_product/${item.product_image}` : 'assets/images/placeholder.png';
                            tbody.innerHTML += `
                                <tr>
                                    <td class="p-2">
                                        <img src="${imgSrc}" class="rounded shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td class="fw-bold">${item.brand}</td>
                                    <td class="text-start fw-bold text-primary">${item.product_name}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><i class="fas fa-palette text-info"></i> ${item.color}</span>
                                        <span class="badge bg-light text-dark border ms-1"><i class="fas fa-ruler text-warning"></i> Size ${item.size}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success fs-6 px-3">${item.quantity}</span>
                                    </td>
                                </tr>
                            `;
                        });
                    })
                    .catch(err => {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-danger fw-bold">Lỗi kết nối máy chủ! Không thể tải dữ liệu.</td></tr>';
                        console.error(err);
                    });
            });
        });
    }

    // =========================================================
    // 6. XỬ LÝ REAL-TIME PUSHER: TỰ ĐỘNG CẬP NHẬT BẢNG LỊCH SỬ
    // =========================================================
    if (typeof Pusher !== 'undefined') {
        const pusherManager = new Pusher('24a79cb74cfa666e1831', { cluster: 'ap1', forceTLS: true });
        const channelManager = pusherManager.subscribe('warehouse-channel');

        channelManager.bind('ticket-status-changed', function(data) {
            console.log("Đã nhận tín hiệu Pusher:", data); // Bật để soi lỗi

            // Tìm đúng dòng của phiếu vừa bị thay đổi
            const row = document.getElementById('row-ticket-' + data.ticket_id);
            if (row) {
                // 1. Cập nhật nhãn trạng thái (Đổi màu Badge)
                const statusCell = row.querySelector('.ticket-status-cell');
                if (statusCell) {
                    let bgClass = 'bg-secondary';
                    if (data.status === 'PENDING') bgClass = 'bg-warning text-dark';
                    if (data.status === 'PROCESSING') bgClass = 'bg-info text-dark';
                    if (data.status === 'PAUSED') bgClass = 'bg-danger';
                    if (data.status === 'COMPLETED') bgClass = 'bg-success';
                    statusCell.innerHTML = `<span class="badge ${bgClass}">${data.status}</span>`;
                }

                // 2. Cập nhật thời gian hoàn thành (NẾU CÓ)
                if (data.status === 'COMPLETED' && data.completed_at) {
                    const timeCell = row.querySelector('.ticket-time-cell');
                    if (timeCell) {
                        timeCell.innerHTML = `<span class="text-success fw-bold">${data.completed_at}</span>`;
                    }
                }

                // 3. Ẩn/Hiện nút Sửa NV và Xóa tùy theo trạng thái
                const btnChange = row.querySelector('.btn-change-staff');
                const formDelete = row.querySelector('.form-delete-ticket');
                
                // Trạng thái không phải PENDING thì "Tàng hình" 2 nút này
                if (data.status !== 'PENDING') {
                    if (btnChange) btnChange.classList.add('d-none');
                    if (formDelete) formDelete.classList.add('d-none');
                } else {
                    if (btnChange) btnChange.classList.remove('d-none');
                    if (formDelete) formDelete.classList.remove('d-none');
                }
            }
        });
    }
});