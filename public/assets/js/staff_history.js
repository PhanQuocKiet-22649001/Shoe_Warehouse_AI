document.addEventListener('DOMContentLoaded', function () {
    window.viewTicketDetails = async function (ticketId, ticketCode) {
        // Cập nhật mã phiếu lên UI
        document.getElementById('modalTicketCode').innerText = ticketCode;
        const tbody = document.getElementById('ticketDetailBody');
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br>Đang tải dữ liệu...</td></tr>';

        // Mở Modal
        const modal = new bootstrap.Modal(document.getElementById('staffTicketDetailModal'));
        modal.show();

        try {
            const response = await fetch(`index.php?page=tickets&action=get_ticket_details&ticket_id=${ticketId}`);
            const data = await response.json();

            tbody.innerHTML = '';

            if (data.status === 'error') {
                tbody.innerHTML = `<tr><td colspan="8" class="text-danger fw-bold">${data.message}</td></tr>`;
                return;
            }

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-muted fst-italic">Phiếu này không có sản phẩm nào!</td></tr>';
                return;
            }

            let rows = '';
            data.forEach(item => {
                const imgSrc = item.product_image ? `assets/img_product/${item.product_image}` : 'assets/images/placeholder.png';
                let expected = parseInt(item.quantity) || 0;
                let actual = parseInt(item.processed_qty) || 0;
                let diffNum = actual - expected;

                let diffHtml = '';
                let noteHtml = item.note ? `<span class="small text-muted">${item.note}</span>` : `<span class="text-black-50">-</span>`;

                if (diffNum !== 0) {
                    let diffStr = diffNum > 0 ? `+${diffNum}` : `${diffNum}`;
                    let colorClass = diffNum > 0 ? 'text-info' : 'text-danger';
                    diffHtml = `<span class="fw-bold ${colorClass}">${diffStr}</span>`;
                } else {
                    diffHtml = `<span class="text-success fw-bold"><i class="fas fa-check"></i> Khớp</span>`;
                }

                rows += `
                    <tr>
                        <td class="p-2">
                            <img src="${imgSrc}" class="rounded shadow-sm border" style="width: 85px; height: 85px; object-fit: cover;">
                        </td>
                        <td class="fw-bold text-secondary text-uppercase" style="font-size: 0.8rem;">${item.brand}</td>
                        <td>
                            <div class="fw-bold text-primary" style="white-space: normal; line-height: 1.3;">
                                ${item.product_name}
                            </div>
                        </td>
                        <td>
                            <div class="variant-tag">${item.color}</div>
                            <div class="variant-tag mt-1">Size ${item.size}</div>
                        </td>
                        <td><span class="fw-bold fs-5">${expected}</span></td>
                        <td><span class="fw-bold fs-5 ${diffNum !== 0 ? 'text-danger' : 'text-success'}">${actual}</span></td>
                        <td>${diffHtml}</td>
                        <td>
                            <div style="white-space: normal; word-break: break-word; line-height: 1.4;">
                                ${noteHtml}
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = rows;

        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-danger fw-bold">Lỗi kết nối máy chủ! Không thể tải dữ liệu.</td></tr>';
            console.error(error);
        }
    };
});
