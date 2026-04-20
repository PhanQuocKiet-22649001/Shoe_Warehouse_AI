// File: public/assets/js/warehouse_map.js
document.addEventListener('DOMContentLoaded', function () {

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