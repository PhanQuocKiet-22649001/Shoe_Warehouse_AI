// File: public/assets/js/warehouse_map.js
document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // 1. LOGIC ẨN/HIỆN SƠ ĐỒ KHO
    // ==========================================
    const mapCollapseArea = document.getElementById('mapCollapseArea');
    const mapToggleIcon = document.getElementById('mapToggleIcon');
    const btnToggleMap = document.getElementById('btnToggleMap');

    if (mapCollapseArea && mapToggleIcon) {
        mapCollapseArea.addEventListener('show.bs.collapse', function () {
            mapToggleIcon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            btnToggleMap.style.borderStyle = 'solid';
            btnToggleMap.querySelector('span').innerHTML = '<i class="fas fa-eye-slash text-secondary me-2"></i> ĐÓNG SƠ ĐỒ KHO';
        });

        mapCollapseArea.addEventListener('hide.bs.collapse', function () {
            mapToggleIcon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            btnToggleMap.style.borderStyle = 'dashed';
            btnToggleMap.querySelector('span').innerHTML = '<i class="fas fa-map-marked-alt text-info me-2"></i> BẤM ĐỂ HIỂN THỊ SƠ ĐỒ KHÔNG GIAN KHO';
        });
    }

    // ==========================================
    // 2. LOGIC TOOLTIP CUSTOM SIÊU MƯỢT
    // ==========================================
    const customTooltip = document.createElement('div');
    customTooltip.className = 'custom-map-tooltip d-none shadow-lg rounded-2 p-3'; // Đổi class
    customTooltip.style.position = 'absolute';
    customTooltip.style.zIndex = '9999';
    customTooltip.style.background = 'rgba(0, 0, 0, 0.95)';
    customTooltip.style.backdropFilter = 'blur(10px)';
    customTooltip.style.border = '1px solid rgba(255,255,255,0.2)';
    customTooltip.style.minWidth = '250px';
    customTooltip.style.pointerEvents = 'none';
    customTooltip.style.transition = 'opacity 0.1s ease';
    document.body.appendChild(customTooltip);

    // Tìm trong class warehouse-map-box mới
    const cells = document.querySelectorAll('.warehouse-map-box .shelf-cell');

    cells.forEach(cell => {
        cell.addEventListener('mouseenter', function (e) {
            const code = this.getAttribute('data-code');
            const occ = this.getAttribute('data-occupancy');
            const detailHtml = this.getAttribute('data-detail');

            customTooltip.innerHTML = `
                <div class="d-flex justify-content-between align-items-center border-bottom border-secondary pb-2 mb-3">
                    <span class="text-white fw-bold fs-5">${code}</span>
                    <span class="badge bg-white text-dark py-1">${occ}/4 đôi</span>
                </div>
                ${detailHtml}
            `;
            customTooltip.classList.remove('d-none');
        });

        cell.addEventListener('mousemove', function (e) {
            let x = e.pageX + 20;
            let y = e.pageY + 20;

            const tooltipRect = customTooltip.getBoundingClientRect();
            if (x + tooltipRect.width > window.innerWidth) x = e.pageX - tooltipRect.width - 20;
            if (y + tooltipRect.height > window.innerHeight) y = e.pageY - tooltipRect.height - 20;

            customTooltip.style.left = x + 'px';
            customTooltip.style.top = y + 'px';
        });

        cell.addEventListener('mouseleave', function () {
            customTooltip.classList.add('d-none');
        });
    });

    // ==========================================
    // 3. LOGIC CHI TIẾT THỐNG KÊ (THẺ CLICKABLE)
    // ==========================================
    const modalEl = document.getElementById('detailModal');
    if (!modalEl) return;

    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    const bsModal = new bootstrap.Modal(modalEl);

    // CLICK CARD CẤP 1
    document.querySelectorAll('.clickable-card').forEach(card => {
        card.addEventListener('click', function () {
            const type = this.getAttribute('data-type');
            const title = this.querySelector('p').innerText;

            modalTitle.innerText = 'Phân tích chi tiết: ' + title;
            modalTitle.style.color = '#ffffff';
            modalContent.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-info" role="status"></div><p class="mt-2 text-white-50">Đang nạp dữ liệu...</p></div>';
            bsModal.show();

            fetch(`index.php?page=get_brand_data&type=${type}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        modalContent.innerHTML = '<p class="text-center p-4 text-white-50">Không có dữ liệu cho mục này.</p>';
                        return;
                    }

                    let html = `
                    <table class="table align-middle text-white" style="border-color: rgba(255,255,255,0.1);">
                        <thead>
                            <tr>
                                <th>Thương hiệu</th>
                                <th class="text-end">Số lượng</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>`;

                    data.forEach(item => {
                        html += `
                        <tr class="brand-row" data-id="${item.category_id}" data-type="${type}">
                            <td class="fw-bold">${item.brand}</td>
                            <td class="text-end fw-bold text-info">${parseInt(item.total).toLocaleString()}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-info btn-get-products" data-id="${item.category_id}" data-type="${type}">
                                    Chi tiết <i class="fas fa-chevron-down ms-1"></i>
                                </button>
                            </td>
                        </tr>
                        <tr id="brand-detail-${item.category_id}" class="d-none">
                            <td colspan="3" class="p-3 bg-dark">
                                <div class="product-container p-2 rounded border border-secondary">Đang tải sản phẩm...</div>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    modalContent.innerHTML = html;
                });
        });
    });

    // CLICK CARD CẤP 2
    modalContent.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-get-products');
        if (!btn) return;

        const brandId = btn.getAttribute('data-id');
        const type = btn.getAttribute('data-type');
        const childBox = document.getElementById(`brand-detail-${brandId}`);
        const icon = btn.querySelector('i');

        if (childBox.classList.contains('d-none')) {
            childBox.classList.remove('d-none');
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');

            fetch(`index.php?page=get_product_data&brand_id=${brandId}&type=${type}`)
                .then(res => res.json())
                .then(products => {
                    let pTable = `
                    <table class="table table-sm table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-white-50">Mẫu giày</th>
                                <th class="text-end text-white-50">Tổng</th>
                                <th class="text-center text-white-50">Biến thể</th>
                            </tr>
                        </thead>
                        <tbody>`;
                    products.forEach(p => {
                        pTable += `
                        <tr class="product-row" data-id="${p.product_id}" data-type="${type}">
                            <td>${p.product_name}</td>
                            <td class="text-end text-warning fw-bold">${p.total}</td>
                            <td class="text-center">
                                <button class="btn btn-sm text-info p-0 btn-get-variants" style="text-decoration: underline;">Xem</button>
                            </td>
                        </tr>
                        <tr id="product-detail-${p.product_id}" class="d-none">
                            <td colspan="3" class="p-3" style="background: rgba(0,0,0,0.2);">
                                <div class="variant-container"></div>
                            </td>
                        </tr>`;
                    });
                    pTable += '</tbody></table>';
                    childBox.querySelector('.product-container').innerHTML = pTable;
                });
        } else {
            childBox.classList.add('d-none');
            icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
        }
    });

    // CLICK CARD CẤP 3
    modalContent.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-get-variants');
        if (!btn) return;

        const row = btn.closest('.product-row');
        const productId = row.getAttribute('data-id');
        const type = row.getAttribute('data-type');
        const variantBox = document.getElementById(`product-detail-${productId}`);

        if (variantBox.classList.contains('d-none')) {
            variantBox.classList.remove('d-none');
            btn.innerText = 'Đóng';

            fetch(`index.php?page=get_variant_data&product_id=${productId}&type=${type}`)
                .then(res => res.json())
                .then(variants => {
                    let vHtml = '<div class="row g-2">';
                    variants.forEach(v => {
                        vHtml += `
                        <div class="col-4">
                            <div class="p-2 border border-secondary rounded text-center small" style="background: rgba(255,255,255,0.05);">
                                <span class="text-white-50">Size ${v.size} - ${v.color}</span><br>
                                <strong class="text-white">${v.total} đôi</strong>
                            </div>
                        </div>`;
                    });
                    vHtml += '</div>';
                    variantBox.querySelector('.variant-container').innerHTML = vHtml;
                });
        } else {
            variantBox.classList.add('d-none');
            btn.innerHTML = 'Xem';
        }
    });
});