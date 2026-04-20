document.addEventListener('DOMContentLoaded', function () {
    const colorPrimary = '#4e73df';
    const colorSuccess = '#1cc88a';

    // 1. VẼ BIỂU ĐỒ 
    if (typeof dayData !== 'undefined' && document.getElementById('daysChart') && dayData.length > 0) {
        new Chart(document.getElementById('daysChart'), {
            type: 'bar',
            data: {
                labels: dayData.map(d => new Date(d.work_date).toLocaleDateString('vi-VN')),
                datasets: [
                    {
                        label: 'Nhập kho',
                        data: dayData.map(d => d.total_import),
                        backgroundColor: '#4e73df',
                        borderRadius: 4,
                        barPercentage: 0.8,
                        categoryPercentage: 0.8
                    },
                    {
                        label: 'Xuất kho',
                        data: dayData.map(d => d.total_export),
                        backgroundColor: '#1cc88a',
                        borderRadius: 4,
                        barPercentage: 0.8,
                        categoryPercentage: 0.8
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'bottom' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Số lượng (đôi)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    if (typeof brandData !== 'undefined' && document.getElementById('brandChart') && brandData.length > 0) {
        new Chart(document.getElementById('brandChart'), {
            type: 'doughnut',
            data: {
                labels: brandData.map(d => d.brand),
                datasets: [{
                    data: brandData.map(d => d.total_stock),
                    backgroundColor: [colorPrimary, colorSuccess, '#36b9cc', '#f6c23e', '#e74a3b']
                }]
            },
            options: { maintainAspectRatio: false, cutout: '70%' }
        });
    }

    if (typeof flowData !== 'undefined' && document.getElementById('productFlowChart') && flowData.length > 0) {
        new Chart(document.getElementById('productFlowChart'), {
            type: 'bar',
            data: {
                labels: flowData.map(p => p.product_name),
                datasets: [
                    { label: 'Nhập', data: flowData.map(p => p.total_import), backgroundColor: colorPrimary },
                    { label: 'Xuất', data: flowData.map(p => p.total_export), backgroundColor: colorSuccess }
                ]
            },
            options: { indexAxis: 'y', maintainAspectRatio: false }
        });
    }

    // 2. LOGIC MỞ MODAL XEM CHI TIẾT NGÀY (Đã sửa lỗi)
    document.querySelectorAll('.btn-open-report-detail').forEach(btn => {
        btn.addEventListener('click', async function () {
            const date = this.dataset.date;
            const modalEl = document.getElementById('modalReportDetail');
            
            // Dùng getOrCreateInstance để tránh lỗi Bootstrap
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl); 
            const tableBody = document.getElementById('reportDetailBody');
            const totalCount = document.getElementById('totalItemsCount');

            document.getElementById('reportDetailDate').innerText = "Chi tiết giao dịch ngày: " + new Date(date).toLocaleDateString('vi-VN');
            totalCount.innerText = "Đang tải dữ liệu...";
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>';
            
            modal.show();

            try {
                const response = await fetch(`index.php?page=report-detail&date=${date}`);
                const data = await response.json();
                
                if (data.length > 0) {
                    let html = '';
                    data.forEach(item => {
                        const time = new Date(item.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                        
                        // ÉP CSS CỨNG: Để không bị các file CSS khác phá màu
                        let badgeStyle = '';
                        if (item.transaction_type === 'IMPORT') {
                            // IMPORT: Nền đen, chữ trắng
                            badgeStyle = 'background-color: #000000 !important; color: #ffffff !important;';
                        } else {
                            // EXPORT: Nền trắng, chữ đen, viền đen
                            badgeStyle = 'background-color: #ffffff !important; color: #000000 !important; border: 1px solid #000000 !important;';
                        }

                        // Thêm style="color: black !important;" vào tất cả các cột chữ
                        html += `
                        <tr>
                            <td class="small" style="color: #000000 !important;">${time}</td>
                            <td><span class="badge" style="${badgeStyle}">${item.transaction_type}</span></td>
                            <td class="fw-bold" style="color: #000000 !important;">${item.brand}</td>
                            <td style="color: #000000 !important;">${item.product_name}</td>
                            <td style="color: #000000 !important;">Sz: ${item.size} | ${item.color}</td>
                            <td class="text-center fw-bold" style="color: #000000 !important;">${item.quantity} đôi</td>
                            <td class="small" style="color: #000000 !important;">${item.staff}</td>
                        </tr>`;
                    });
                    
                    tableBody.innerHTML = html;
                    totalCount.innerText = "Tổng cộng: " + data.length + " lượt giao dịch";
                    totalCount.style.color = "#000000"; // Ép chữ thống kê màu đen luôn
                    
                } else {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4" style="color: #000000 !important;">Không có giao dịch nào được ghi nhận.</td></tr>';
                    totalCount.innerText = "0 lượt giao dịch";
                }
            } catch (error) {
                console.error("Lỗi Fetch:", error);
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Lỗi kết nối máy chủ.</td></tr>';
                totalCount.innerText = "Lỗi tải dữ liệu";
            }
        });
    });

    // 3. LOGIC LỌC TỒN KHO THEO BRAND
    const brandFilter = document.getElementById('brandFilter');
    const inventoryTitle = document.getElementById('inventoryTitle');
    const productCards = document.querySelectorAll('.inv-product-card');

    function updateInventoryDisplay() {
        if (!brandFilter || !inventoryTitle) return;

        const selectedBrand = brandFilter.value;
        let totalStock = 0;

        productCards.forEach(card => {
            const cardBrand = card.getAttribute('data-brand');
            if (selectedBrand === 'ALL' || cardBrand === selectedBrand) {
                card.style.display = 'block';
                const variants = card.querySelectorAll('.variant-stock');
                variants.forEach(v => {
                    totalStock += parseInt(v.innerText);
                });
            } else {
                card.style.display = 'none';
            }
        });

        if (selectedBrand === 'ALL') {
            inventoryTitle.innerHTML = `
                <i class="bi bi-box-seam me-2"></i>Phân Tích Tồn Kho 
                <span class="ms-2 badge bg-primary-soft text-primary fw-bold" style="font-size: 0.9rem; padding: 5px 12px; border-radius: 50px; background-color: #e7f1ff;">
                    Tất cả: ${totalStock.toLocaleString()} đôi
                </span>`;
        } else {
            inventoryTitle.innerHTML = `
                Phân Tích Tồn Kho 
                <span class="ms-2 text-muted fw-normal" style="font-size: 0.85rem;">|</span>
                <span class="ms-2 badge bg-light text-dark border fw-medium" style="padding: 5px 12px; border-radius: 6px;">
                    Hãng: <b class="text-primary">${selectedBrand}</b>
                </span>
                <span class="ms-2 badge bg-light text-dark border fw-medium" style="padding: 5px 12px; border-radius: 6px;">
                    Tổng tồn: <b class="text-danger">${totalStock.toLocaleString()} đôi</b>
                </span>`;
        }
    }

    if (brandFilter) {
        updateInventoryDisplay();
        brandFilter.addEventListener('change', updateInventoryDisplay);
    }
});