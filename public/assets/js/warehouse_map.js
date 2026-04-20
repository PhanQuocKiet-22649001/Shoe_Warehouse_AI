// File: public/assets/js/warehouse_map.js
document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // LOGIC TOOLTIP CUSTOM SIÊU MƯỢT
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
    // LOGIC TÌM KIẾM VÀ NHÁY SÁNG Ô CHỨA (BẢN AJAX CHUẨN)
    // ==========================================
    const searchInput = document.getElementById('mapSearchInput');
    const searchSuggestions = document.getElementById('mapSearchSuggestions');
    let searchTimeout = null;

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const keyword = this.value.trim();
            
            // 1. Xóa tất cả highlight cũ mỗi khi gõ từ khóa mới
            document.querySelectorAll('.shelf-cell').forEach(c => c.classList.remove('slot-highlighted'));
            searchSuggestions.innerHTML = '';

            if (keyword.length < 1) {
                searchSuggestions.classList.add('d-none');
                return;
            }

            // 2. Chống spam request (Debounce 300ms)
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchSuggestions.classList.remove('d-none');
                searchSuggestions.innerHTML = '<li class="list-group-item bg-dark text-info"><i class="fas fa-spinner fa-spin me-2"></i>Đang tìm...</li>';

                // 3. Gọi AJAX lên Controller
                fetch(`index.php?page=products&action=search_map&keyword=${encodeURIComponent(keyword)}`)
                    .then(res => res.json())
                    .then(results => {
                        searchSuggestions.innerHTML = '';
                        if (results.length > 0) {
                            results.forEach(item => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item list-group-item-action bg-dark text-white border-secondary d-flex align-items-center cursor-pointer';
                                li.style.cursor = 'pointer';
                                li.innerHTML = `
                                    <img src="${item.image}" class="rounded me-3 border border-secondary" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1 lh-sm">
                                        <strong class="d-block text-truncate" style="max-width: 250px;">${item.name}</strong>
                                        <small class="text-info">${item.brand} | Size: ${item.size}</small>
                                    </div>
                                    <span class="badge bg-secondary">${item.cells.length} Ô</span>
                                `;

                                // Khi click vào một kết quả gợi ý
                                li.addEventListener('click', function () {
                                    searchInput.value = item.name;
                                    searchSuggestions.classList.add('d-none');

                                    // Nháy sáng tất cả các ô chứa đôi giày này
                                    item.cells.forEach((code, index) => {
                                        const targetCell = document.querySelector(`.shelf-cell[data-code="${code}"]`);
                                        if (targetCell) {
                                            targetCell.classList.add('slot-highlighted');
                                            // Cuộn màn hình đến ô đầu tiên tìm thấy
                                            if (index === 0) {
                                                targetCell.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                            }
                                        }
                                    });
                                });
                                searchSuggestions.appendChild(li);
                            });
                        } else {
                            searchSuggestions.innerHTML = '<li class="list-group-item bg-dark text-white-50 border-secondary text-center">Không tìm thấy giày này...</li>';
                        }
                    })
                    .catch(err => {
                        console.error('Lỗi tìm kiếm:', err);
                        searchSuggestions.classList.add('d-none');
                    });
            }, 300);
        });
    }
});