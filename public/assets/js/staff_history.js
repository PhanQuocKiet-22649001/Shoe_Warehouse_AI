document.addEventListener('DOMContentLoaded', function() {
    window.viewTicketDetails = async function(ticketId, ticketCode) {
        // Cập nhật mã phiếu lên UI
        document.getElementById('modalTicketCode').innerText = ticketCode;
        const tbody = document.getElementById('ticketDetailBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-dark me-2"></div> Đang tải...</td></tr>';
        
        // Mở Modal
        const modal = new bootstrap.Modal(document.getElementById('staffTicketDetailModal'));
        modal.show();

        try {
            const response = await fetch(`index.php?page=tickets&action=get_ticket_details&ticket_id=${ticketId}`);
            const data = await response.json();

            if (data.status === 'error') {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">${data.message}</td></tr>`;
                return;
            }

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Phiếu trống.</td></tr>';
                return;
            }

            let rows = '';
            data.forEach(item => {
                rows += `
                    <tr>
                        <td class="ps-4 fw-bold">${item.brand}</td>
                        <td class="text-dark fw-bold">${item.product_name}</td>
                        <td>${item.color}</td>
                        <td class="text-center fw-bold">${item.size}</td>
                        <td class="text-center pe-4 fw-bold text-primary">${item.quantity}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = rows;

        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Lỗi kết nối máy chủ!</td></tr>';
        }
    };
});