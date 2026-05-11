// =========================================================
// LOGIC NHẬP KHO AI (THEO PHIẾU CHỈ ĐỊNH & BẢNG TẠM)
// =========================================================

let currentImportTicketId = null;
let currentImportTicketCode = "";
let importTicketItems = []; // Danh sách giày trong phiếu nhập
let importScannedData = []; // Kết quả AI trả về
let importSelectedFiles = [];
let activeScannedIndex = -1; // Tấm ảnh đang được thao tác

document.getElementById('addProductModal').addEventListener('show.bs.modal', loadMyImportTickets);

// 1. TẢI DANH SÁCH PHIẾU NHẬP ĐANG CHỜ
async function loadMyImportTickets() {
    const list = document.getElementById('import_ticket_list');
    list.innerHTML = '<div class="text-center text-white-50 py-3"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...</div>';
    
    document.getElementById('import_step_1').classList.remove('d-none');
    document.getElementById('import_step_2').classList.add('d-none');
    document.getElementById('import_ai_upload_zone').classList.add('d-none');
    document.getElementById('import_form_zone').classList.add('d-none');
    document.getElementById('import_right_default').classList.remove('d-none');

    try {
        const res = await fetch('index.php?page=tickets&action=get_my_imports');
        const tickets = await res.json();

        if (!tickets || tickets.length === 0) {
            list.innerHTML = '<div class="alert alert-glass-blink text-center text-warning border-0">Không có phiếu nhập nào được giao.</div>';
            return;
        }

        list.innerHTML = tickets.map(t => `
            <div class="list-group-item bg-dark text-white border-secondary mb-2 rounded-1 p-3 cursor-pointer" 
                 onclick="openImportTicket(${t.ticket_id}, '${t.ticket_code}')">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-info"><i class="fas fa-truck-loading me-2"></i>${t.ticket_code}</span>
                    <span class="badge ${t.status === 'PAUSED' ? 'bg-warning text-dark' : 'bg-primary'}">${t.status}</span>
                </div>
            </div>
        `).join('');
    } catch (e) { list.innerHTML = '<div class="text-danger small">Lỗi kết nối.</div>'; }
}

// 2. MỞ PHIẾU CHI TIẾT
async function openImportTicket(ticketId, ticketCode) {
    currentImportTicketId = ticketId;
    currentImportTicketCode = ticketCode;

    document.getElementById('import_current_ticket_code').innerText = `MÃ PHIẾU: ${ticketCode}`;
    document.getElementById('import_step_1').classList.add('d-none');
    document.getElementById('import_step_2').classList.remove('d-none');
    
    document.getElementById('import_right_default').classList.add('d-none');
    document.getElementById('import_ai_upload_zone').classList.remove('d-none');

    // Chuyển status sang PROCESSING
    fetch(`index.php?page=tickets&action=update_status&ticket_id=${ticketId}&status=PROCESSING`, { method: 'POST' });
    loadImportTicketItems();
}

async function loadImportTicketItems() {
    const container = document.getElementById('import_ticket_items');
    container.innerHTML = '<div class="text-center text-white-50"><i class="fas fa-spinner fa-spin"></i> Đang tải giày...</div>';
    
    try {
        // Gọi chung API get_ticket_details của Export
        const res = await fetch(`index.php?page=tickets&action=get_ticket_details&ticket_id=${currentImportTicketId}`);
        importTicketItems = await res.json();
        renderImportItemsUI();
    } catch (e) { container.innerHTML = '<div class="text-danger small">Lỗi tải dữ liệu phiếu.</div>'; }
}

// Render danh sách bên trái (Cột phiếu)
function renderImportItemsUI() {
    const container = document.getElementById('import_ticket_items');
    let allDone = true;

    container.innerHTML = importTicketItems.map(item => {
        const qty = parseInt(item.quantity);
        const processed = parseInt(item.processed_qty) || 0;
        const isDone = processed >= qty;
        if (!isDone) allDone = false;

        return `
            <div id="import_row_${item.variant_id}" class="p-3 rounded border ${isDone ? 'bg-success bg-opacity-25 border-success' : 'bg-dark border-secondary'} transition-all">
                <div class="d-flex align-items-center">
                    <img src="assets/img_product/${item.product_image || 'default_shoe.png'}" class="rounded me-3 border border-secondary bg-white" style="width: 50px; height: 50px; object-fit: contain;">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold small ${isDone ? 'text-success' : 'text-white'}">${item.product_name}</span>
                            <span class="badge ${isDone ? 'bg-success' : 'bg-danger'}">${processed} / ${qty}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="small text-white-50">
                                Size: <strong class="text-white">${item.size}</strong> | Màu: <strong class="text-white">${item.color}</strong>
                            </div>
                            ${isDone ? '<span class="text-success small fw-bold"><i class="fas fa-check-circle"></i> Xong</span>' : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    if (allDone && importTicketItems.length > 0) {
        document.getElementById('btn_complete_import').classList.remove('d-none');
    } else {
        document.getElementById('btn_complete_import').classList.add('d-none');
    }
}

function backToImportTickets() {
    fetch(`index.php?page=tickets&action=update_status&ticket_id=${currentImportTicketId}&status=PAUSED`, { method: 'POST' });
    loadMyImportTickets();
}

// 3. XỬ LÝ ẢNH & QUÉT AI
function previewImportImages(input) {
    importSelectedFiles = Array.from(input.files).slice(0, 3); // Lấy tối đa 3 ảnh
    const container = document.getElementById('import_pre_scan_preview');
    container.innerHTML = '';
    
    if(importSelectedFiles.length > 0) {
        document.getElementById('btn-scan-import').classList.remove('d-none');
        importSelectedFiles.forEach(file => {
            container.innerHTML += `<img src="${URL.createObjectURL(file)}" class="rounded" style="width: 60px; height: 60px; object-fit:cover;">`;
        });
    }
}

async function executeImportScan() {
    const btn = document.getElementById('btn-scan-import');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    const fd = new FormData();
    importSelectedFiles.forEach(f => fd.append('images[]', f));

    try {
        const response = await fetch('index.php?page=products&action=scan-ai', { method: 'POST', body: fd });
        const res = await response.json();
        
        if (res.status === 'success') {
            importScannedData = res.data;
            document.getElementById('import_pre_scan_preview').innerHTML = ''; // Clear ảnh nháp
            btn.classList.add('d-none');
            renderImportPostScan();
            if(importScannedData.length > 0) processAIResult(0); // Tự động load ảnh đầu tiên
        } else { alert("Lỗi quét AI: " + res.message); }
    } catch (e) { alert("Lỗi kết nối AI."); }
    finally {
        btn.innerHTML = '<i class="fas fa-magic me-1"></i>QUÉT AI';
        btn.disabled = false;
    }
}

function renderImportPostScan() {
    const container = document.getElementById('import_post_scan_area');
    container.innerHTML = importScannedData.map((item, idx) => {
        let isDoneClass = item.is_done ? 'opacity-25' : ''; // Làm xám nếu đã nhập đủ các biến thể của ảnh này
        let activeClass = (idx === activeScannedIndex) ? 'border-warning shadow' : 'border-secondary';
        return `<img src="${URL.createObjectURL(importSelectedFiles[idx])}" 
                     class="rounded border border-2 cursor-pointer ${activeClass} ${isDoneClass}" 
                     style="width: 80px; height: 80px; object-fit:contain;" 
                     onclick="processAIResult(${idx})">`;
    }).join('');
}

// 4. SO KHỚP AI, ĐÁNH GIÁ % VÀ HIGHLIGHT PHIẾU
function processAIResult(index) {
    activeScannedIndex = index;
    renderImportPostScan(); 
    
    const aiItem = importScannedData[index];
    const matches = aiItem.matches || [];
    if (matches.length === 0) return alert("AI không nhận diện được bất kỳ mẫu giày nào!");

    // ÉP SẮP XẾP GIẢM DẦN BẢO ĐẢM TỈ LỆ CAO NHẤT NẰM TRÊN CÙNG
    matches.sort((a, b) => (parseFloat(b.similarity_score || b.score || 0)) - (parseFloat(a.similarity_score || a.score || 0)));

    // Ẩn Form điền và xóa vùng gợi ý cũ (nếu có)
    document.getElementById('import_form_zone').classList.remove('d-flex');
    document.getElementById('import_form_zone').classList.add('d-none');
    let suggestionContainer = document.getElementById('import_suggestion_container');
    if(suggestionContainer) suggestionContainer.innerHTML = ''; 

    // Tắt hết highlight màu xanh của các dòng
    document.querySelectorAll('[id^="import_row_"]').forEach(el => {
        el.classList.remove('bg-info', 'bg-opacity-25', 'border-info');
    });

    const topMatch = matches[0]; 
    
    const topScore = parseFloat(topMatch.similarity_score || 0) * 100;

    // Dưới 80% -> Từ chối
    if (topScore < 80) {
        alert(`Tỉ lệ khớp quá thấp (${topScore.toFixed(1)}%). Vui lòng chụp lại ảnh rõ nét hơn!`);
        return;
    }

    // Từ 95% trở lên -> Khớp 100%
    if (topScore >= 95) {
        let isSuccess = autoProcessMatch(topMatch, aiItem.temp_image);
        if (isSuccess) {
            alert(`Thành công! Khớp ảnh kho 100% (Gốc: ${topScore.toFixed(1)}%)`);
        }
    } 
    // Gợi ý (80% - 94.9%)
    else {
        renderSuggestionDropdown(matches, aiItem.temp_image);
    }
}

// =========================================================================
// BỔ SUNG: 3 HÀM HỖ TRỢ XỬ LÝ % KHỚP ẢNH BỊ THIẾU
// Tác dụng: Xử lý điền form tự động và render danh sách gợi ý
// =========================================================================

// 4.1 HÀM TỰ ĐỘNG ĐIỀN FORM VÀ HIGHLIGHT (Có chặn hàng ngoài phiếu)
function autoProcessMatch(matchData, tempImage) {
    const aiBrand = (matchData.brand || "").toLowerCase();
    const aiName = (matchData.product_name || matchData.model || matchData.name || "").toLowerCase();

    let matchedTicketItems = importTicketItems.filter(item => 
        (item.brand || "").toLowerCase() === aiBrand && 
        (item.product_name || "").toLowerCase() === aiName &&
        parseInt(item.processed_qty || 0) < parseInt(item.quantity || 0)
    );

    if (matchedTicketItems.length === 0) {
        alert("Sản phẩm AI nhận diện được KHÔNG CÓ trong phiếu nhập này, hoặc đã nhập đủ số lượng!");
        return false; 
    }

    document.getElementById('import_form_zone').classList.remove('d-none');
    document.getElementById('import_form_zone').classList.add('d-flex');
    document.getElementById('import_temp_image').value = tempImage;
    document.getElementById('import_brand').value = matchData.brand || "";
    document.getElementById('import_name').value = matchData.product_name || matchData.model || matchData.name || "";

    matchedTicketItems.forEach(item => {
        let row = document.getElementById(`import_row_${item.variant_id}`);
        if(row) row.classList.add('bg-info', 'bg-opacity-25', 'border-info');
    });

    const colorDropdown = document.getElementById('import_color');
    document.getElementById('import_size').innerHTML = '<option value="">-- Chọn size --</option>';
    document.getElementById('import_size').disabled = true;
    colorDropdown.innerHTML = '<option value="">-- Chọn màu --</option>';

    let uniqueColors = [...new Set(matchedTicketItems.map(i => i.color))];
    uniqueColors.forEach(c => {
        colorDropdown.innerHTML += `<option value="${c}">${c}</option>`;
    });

    window.currentMatchedItems = matchedTicketItems;
    return true; 
}

// 4.2 HÀM TẠO MENU DROPDOWN VÀ SHOW TOÀN BỘ ẢNH BÊN TRONG DROPDOWN (BOOTSTRAP CUSTOM)
function renderSuggestionDropdown(matches, tempImage) {
    let validMatches = matches.filter(m => (parseFloat(m.similarity_score || m.score || 0) * 100) >= 80);
    validMatches.sort((a, b) => (parseFloat(b.similarity_score || b.score || 0)) - (parseFloat(a.similarity_score || a.score || 0)));
    
    let container = document.getElementById('import_suggestion_container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'import_suggestion_container';
        container.className = 'w-100 mb-3';
        document.getElementById('import_form_zone').parentNode.insertBefore(container, document.getElementById('import_form_zone'));
    }

    let imagesHtml = '';
    let listItemsHtml = '';

    validMatches.forEach((m, idx) => {
        let score = (parseFloat(m.similarity_score || m.score || 0) * 100).toFixed(1);
        let mBrand = (m.brand || "").toLowerCase();
        let mName = (m.product_name || m.model || m.name || "").toLowerCase();
        
        let inTicket = importTicketItems.some(ti => (ti.brand || "").toLowerCase() === mBrand && (ti.product_name || "").toLowerCase() === mName);
        let mark = inTicket ? "⭐ CÓ TRONG PHIẾU" : "Ngoài phiếu";
        
        let cleanObj = { brand: m.brand, product_name: m.product_name || m.model || m.name, product_image: m.product_image || 'default_shoe.png' };
        let valStr = JSON.stringify(cleanObj).replace(/'/g, "&#39;"); 
        let displayText = `[${score}%] - ${m.brand} ${cleanObj.product_name} (${mark})`;

        // TẠO HTML CÁC DÒNG BÊN TRONG DROPDOWN (CÓ KÈM HÌNH ẢNH)
        listItemsHtml += `
            <li>
                <a class="dropdown-item text-white d-flex align-items-center py-2 border-bottom border-secondary bg-dark custom-hover-dropdown" 
                   href="javascript:void(0)" 
                   id="suggestion_item_${idx}"
                   data-val='${valStr}'
                   onclick="selectCustomSuggestion(this)">
                    <img src="assets/img_product/${cleanObj.product_image}" class="rounded bg-white me-3 border border-secondary" style="width: 45px; height: 45px; object-fit: contain;">
                    <span class="text-wrap small fw-bold" style="white-space: normal;">${displayText}</span>
                </a>
            </li>
        `;
        
        let borderClass = inTicket ? 'border-success' : 'border-secondary';
        imagesHtml += `
            <div class="text-center me-2 mb-2 p-1 rounded border ${borderClass} bg-dark" 
                 onclick="selectSuggestionImage(${idx})" style="width: 80px; cursor: pointer;">
                <img src="assets/img_product/${cleanObj.product_image}" class="rounded bg-white mb-1 w-100" style="height: 50px; object-fit: contain;">
                <div class="small fw-bold text-warning" style="font-size: 0.75rem;">${score}%</div>
            </div>
        `;
    });

    // Thêm CSS hover cho dropdown item cho đẹp
    const customStyle = `<style>.custom-hover-dropdown:hover { background-color: #343a40 !important; }</style>`;

    container.innerHTML = customStyle + `
    <div class="p-3 bg-black border border-warning rounded" style="border-style: dashed !important;">
        <div class="text-warning small fw-bold mb-2"><i class="fas fa-exclamation-triangle me-1"></i> AI phân vân. Danh sách ảnh gần giống (Xếp % giảm dần):</div>
        
        <div class="d-flex flex-wrap align-items-center mb-3 pb-2 border-bottom border-secondary">
            ${imagesHtml}
        </div>

        <div class="dropdown w-100 mb-3">
            <button class="btn btn-dark border-secondary w-100 dropdown-toggle d-flex justify-content-between align-items-center text-start px-3 py-2" type="button" id="customSuggestionBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="text-white-50">-- Bấm vào đây để chọn mẫu gần giống --</span>
            </button>
            <ul class="dropdown-menu w-100 bg-dark border border-secondary p-0 shadow" style="max-height: 250px; overflow-y: auto;">
                ${listItemsHtml}
            </ul>
        </div>
        
        <input type="hidden" id="import_suggestion_value" value="">

        <button class="btn btn-warning btn-sm w-100 fw-bold" onclick="confirmSuggestedMatch('${tempImage}')">XÁC NHẬN MẪU ĐÃ CHỌN</button>
    </div>`;
}

// 4.3 CÁC HÀM XỬ LÝ LỰA CHỌN DROPDOWN
window.selectCustomSuggestion = function(element) {
    const btn = document.getElementById('customSuggestionBtn');
    const hiddenInput = document.getElementById('import_suggestion_value');
    
    // Lưu value JSON vào ô ẩn
    hiddenInput.value = element.getAttribute('data-val');
    
    // Copy luôn nguyên cục HTML (ảnh + chữ) từ option được chọn bỏ lên mặt Nút Dropdown
    btn.innerHTML = `<div class="d-flex align-items-center w-100 pe-3">${element.innerHTML}</div>`;
};

window.selectSuggestionImage = function(index) {
    // Khi bấm vào cái hình nhỏ ở trên thì tự động kích hoạt cái Dropdown ở dưới luôn
    const targetItem = document.getElementById(`suggestion_item_${index}`);
    if(targetItem) targetItem.click();
};

window.confirmSuggestedMatch = function(tempImage) {
    const hiddenInput = document.getElementById('import_suggestion_value');
    if (!hiddenInput.value) return alert("Vui lòng chọn 1 mẫu từ danh sách hoặc click vào hình!");
    
    let isSuccess = autoProcessMatch(JSON.parse(hiddenInput.value), tempImage); 
    if(isSuccess) {
        document.getElementById('import_suggestion_container').innerHTML = ''; 
    }
}
// =========================================================================

// 5. DROPDOWN SIZE DỰA THEO MÀU
function updateImportSizeDropdown() {
    const color = document.getElementById('import_color').value;
    const sizeDropdown = document.getElementById('import_size');
    
    sizeDropdown.innerHTML = '<option value="">-- Chọn size --</option>';
    
    if (!color) {
        sizeDropdown.disabled = true;
        return;
    }

    sizeDropdown.disabled = false;
    
    // Tắt hết highlight cũ
    window.currentMatchedItems.forEach(item => {
        let row = document.getElementById(`import_row_${item.variant_id}`);
        if(row) row.classList.remove('bg-info', 'bg-opacity-25', 'border-info');
    });

    // Lọc ra các size thuộc về màu đã chọn và SÁNG LẠI các dòng đó
    let availableSizes = window.currentMatchedItems.filter(i => i.color === color);
    availableSizes.forEach(item => {
        sizeDropdown.innerHTML += `<option value="${item.size}" data-detail-id="${item.detail_id}" data-variant-id="${item.variant_id}" data-qty="${item.quantity}">Size ${item.size}</option>`;
        
        // Sáng lại vùng của Màu này
        let row = document.getElementById(`import_row_${item.variant_id}`);
        if(row) row.classList.add('bg-info', 'bg-opacity-25', 'border-info');
    });
}

// 6. AUTO FILL SỐ LƯỢNG & THU HẸP HIGHLIGHT (3 YẾU TỐ)
function autoFillImportQty() {
    const sizeSelect = document.getElementById('import_size');
    const selectedOption = sizeSelect.options[sizeSelect.selectedIndex];
    
    if (selectedOption.value) {
        const expectedQty = selectedOption.getAttribute('data-qty');
        const targetVariantId = selectedOption.getAttribute('data-variant-id');

        document.getElementById('import_expected_qty').value = expectedQty;
        document.getElementById('import_actual_qty').value = expectedQty; 
        document.getElementById('import_detail_id').value = selectedOption.getAttribute('data-detail-id');
        document.getElementById('import_variant_id').value = targetVariantId;

        // --- LOGIC TẮT SÁNG CÁC DÒNG KHÔNG KHỚP TUYỆT ĐỐI ---
        
        // 1. Tắt hết highlight của cả mẫu này trước
        window.currentMatchedItems.forEach(item => {
            let row = document.getElementById(`import_row_${item.variant_id}`);
            if(row) {
                row.classList.remove('bg-info', 'bg-opacity-25', 'border-info');
            }
        });

        // 2. Chỉ cho dòng khớp tuyệt đối 3 yếu tố sáng lên
        let activeRow = document.getElementById(`import_row_${targetVariantId}`);
        if(activeRow) {
            activeRow.classList.add('bg-info', 'bg-opacity-25', 'border-info');
        }
        
        checkImportDiscrepancy();
    }
}

// KIỂM TRA DƯ/THIẾU
function checkImportDiscrepancy() {
    const exp = parseInt(document.getElementById('import_expected_qty').value) || 0;
    const act = parseInt(document.getElementById('import_actual_qty').value) || 0;
    const badge = document.getElementById('import_status_badge');
    const typeInput = document.getElementById('import_discrepancy_type');

    if (act === exp) {
        badge.className = 'badge bg-success w-100 p-2';
        badge.innerText = 'KHỚP SỐ LƯỢNG';
        typeInput.value = 'MATCH';
    } else if (act < exp) {
        badge.className = 'badge bg-warning text-dark w-100 p-2';
        badge.innerText = `THIẾU ${exp - act} ĐÔI`;
        typeInput.value = 'SHORT';
    } else {
        badge.className = 'badge bg-danger w-100 p-2';
        badge.innerText = `DƯ ${act - exp} ĐÔI`;
        typeInput.value = 'OVER';
    }
}

// 7. LƯU BẢNG TẠM
async function saveTempImport(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('btn_save_temp');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ĐANG LƯU...';
    btn.disabled = true;

    const fd = new FormData(form);
    fd.append('ticket_id', currentImportTicketId);

    try {
        const res = await fetch('index.php?page=tickets&action=save_temp_import', { method: 'POST', body: fd });
        const data = await res.json();
        
        if(data.status === 'success') {
            // Cập nhật mảng local và UI bên trái
            loadImportTicketItems(); 
            
            // Đánh dấu ảnh này đã nhập xong nếu không còn size/màu nào của model này
            window.currentMatchedItems = window.currentMatchedItems.filter(i => i.detail_id != fd.get('detail_id'));
            if(window.currentMatchedItems.length === 0) {
                importScannedData[activeScannedIndex].is_done = true;
            }
            
            // Xóa form, ép chọn ảnh tiếp
            document.getElementById('import_form_zone').classList.remove('d-flex');
            document.getElementById('import_form_zone').classList.add('d-none');
            renderImportPostScan();
            
        } else { alert("Lỗi: " + data.message); }
    } catch(err) { alert("Lỗi mạng!"); }
    finally {
        btn.innerHTML = 'LƯU NHÁP BIẾN THỂ NÀY';
        btn.disabled = false;
    }
}

// 8. CHỐT SỔ CUỐI CÙNG
async function completeImportTicket() {
    if (!confirm("Hệ thống sẽ cộng tồn kho chính thức, đóng phiếu và xóa bảng tạm. Bạn chắc chắn chứ?")) return;

    const btn = document.getElementById('btn_complete_import');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ĐANG XỬ LÝ...';
    btn.disabled = true;

    try {
        const fd = new FormData();
        fd.append('ticket_id', currentImportTicketId);
        
        const res = await fetch('index.php?page=tickets&action=complete_import', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.status === 'success') {
            alert("NHẬP KHO THÀNH CÔNG!");
            window.location.reload();
        } else {
            alert("Lỗi: " + data.message);
        }
    } catch (e) {
        alert("Lỗi hệ thống.");
    } finally {
        btn.innerHTML = 'XÁC NHẬN HOÀN TẤT PHIẾU NÀY';
        btn.disabled = false;
    }
}

// ==========================================
// LOGIC XUẤT KHO THEO PHIẾU (THỦ CÔNG)
// ==========================================
let currentExportTicketId = null;
let currentExportTicketCode = "";
let currentTicketItems = [];

// Khi mở Modal Xuất Kho -> Load danh sách phiếu
document.getElementById('exportAIModal').addEventListener('show.bs.modal', loadMyExportTickets);

async function loadMyExportTickets() {
    const list = document.getElementById('export_ticket_list');
    list.innerHTML = '<div class="text-center text-white-50 py-3"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...</div>';

    document.getElementById('export_step_1').classList.remove('d-none');
    document.getElementById('export_step_2').classList.add('d-none');
    document.getElementById('export_right_default').classList.remove('d-none');
    document.getElementById('export_right_action').classList.add('d-none');

    try {
        const res = await fetch('index.php?page=tickets&action=get_my_exports');
        const tickets = await res.json();

        if (!tickets || tickets.length === 0) {
            list.innerHTML = '<div class="alert alert-glass-blink text-center text-warning border-0">Không có phiếu xuất nào đang chờ.</div>';
            return;
        }

        list.innerHTML = tickets.map(t => `
            <div class="list-group-item bg-dark text-white border-secondary mb-2 rounded-1 p-3 cursor-pointer" 
                 onclick="openExportTicket(${t.ticket_id}, '${t.ticket_code}')">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-info"><i class="fas fa-ticket-alt me-2"></i>${t.ticket_code}</span>
                    <span class="badge ${t.status === 'PAUSED' ? 'bg-warning text-dark' : 'bg-primary'}">${t.status}</span>
                </div>
            </div>
        `).join('');
    } catch (e) {
        list.innerHTML = '<div class="text-danger small">Lỗi kết nối.</div>';
    }
}

async function openExportTicket(ticketId, ticketCode) {
    currentExportTicketId = ticketId;
    currentExportTicketCode = ticketCode;

    document.getElementById('export_current_ticket_code').innerText = `MÃ PHIẾU: ${ticketCode}`;
    document.getElementById('export_step_1').classList.add('d-none');
    document.getElementById('export_step_2').classList.remove('d-none');

    const itemsContainer = document.getElementById('export_ticket_items');
    itemsContainer.innerHTML = '<div class="text-center text-white-50 py-3"><i class="fas fa-spinner fa-spin"></i> Đang tải giày...</div>';

    // Cập nhật trạng thái phiếu sang PROCESSING 
    fetch(`index.php?page=tickets&action=update_status&ticket_id=${ticketId}&status=PROCESSING`, { method: 'POST' });

    try {
        const res = await fetch(`index.php?page=tickets&action=get_ticket_details&ticket_id=${ticketId}`);
        currentTicketItems = await res.json();
        renderExportItems();
    } catch (e) {
        itemsContainer.innerHTML = '<div class="text-danger small">Lỗi tải danh sách sản phẩm.</div>';
    }
}

function renderExportItems() {
    const container = document.getElementById('export_ticket_items');
    let allDone = true;

    container.innerHTML = currentTicketItems.map(item => {
        const qty = parseInt(item.quantity) || 0;
        const processed = parseInt(item.processed_qty) || 0;
        const remaining = qty - processed;
        const isDone = remaining <= 0;

        if (!isDone) allDone = false;

        return `
            <div class="p-3 rounded border ${isDone ? 'bg-success bg-opacity-25 border-success' : 'bg-dark border-secondary'}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small ${isDone ? 'text-success' : 'text-white'}">${item.product_name}</span>
                    <span class="badge ${isDone ? 'bg-success' : 'bg-danger'}">${processed} / ${qty}</span>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <div class="small text-white-50">
                        Size: <strong class="text-white">${item.size}</strong> | Màu: <strong class="text-white">${item.color}</strong>
                    </div>
                    ${!isDone ? `
                        <button class="btn btn-sm btn-info fw-bold py-0 px-3" 
                            onclick="autoFillExportForm(${item.detail_id})">
                            CHỌN
                        </button>
                    ` : '<span class="text-success small fw-bold"><i class="fas fa-check-circle me-1"></i>Đã xong</span>'}
                </div>
            </div>
        `;
    }).join('');

    if (allDone && currentTicketItems.length > 0) {
        document.getElementById('btn_complete_export').classList.remove('d-none');
        document.getElementById('export_right_default').classList.remove('d-none');
        document.getElementById('export_right_action').classList.add('d-none');
    } else {
        document.getElementById('btn_complete_export').classList.add('d-none');
    }
}

function backToExportTickets() {
    fetch(`index.php?page=tickets&action=update_status&ticket_id=${currentExportTicketId}&status=PAUSED`, { method: 'POST' });
    loadMyExportTickets();
}

let currentRequiredQty = 0;
async function autoFillExportForm(detailId) {
    const item = currentTicketItems.find(i => i.detail_id == detailId);
    if(!item) return;

    const remainingQty = parseInt(item.quantity) - parseInt(item.processed_qty || 0);
    currentRequiredQty = remainingQty; 

    document.getElementById('export_right_default').classList.add('d-none');
    document.getElementById('export_right_action').classList.remove('d-none');
    document.getElementById('export_right_action').classList.add('d-flex');

    document.getElementById('autofill_ticket_code').value = currentExportTicketCode;
    document.getElementById('autofill_brand').value = item.brand;
    document.getElementById('autofill_name').value = item.product_name;
    document.getElementById('autofill_color').value = item.color;
    document.getElementById('autofill_size').value = item.size;
    document.getElementById('autofill_image').src = `assets/img_product/${item.product_image || 'default_shoe.png'}`;

    document.getElementById('pick_detail_id').value = item.detail_id;
    document.getElementById('pick_variant_id').value = item.variant_id;

    const inputQty = document.getElementById('pick_qty_input');
    inputQty.value = 0;
    inputQty.readOnly = true; 
    document.getElementById('pick_qty_max').innerText = `/ ${remainingQty}`;

    const othersArea = document.getElementById('other_variants_area');
    const othersList = document.getElementById('other_variants_list');
    if (item.other_variants && item.other_variants.length > 0) {
        othersArea.classList.remove('d-none');
        othersList.innerHTML = item.other_variants.map(o => `• Màu ${o.color}, Size ${o.size} (Cần lấy: ${o.quantity})`).join('<br>');
    } else {
        othersArea.classList.add('d-none');
    }

    const locContainer = document.getElementById('pick_locations_container');
    locContainer.innerHTML = '<span class="text-white-50 small"><i class="fas fa-spinner fa-spin"></i> Đang dò vị trí kệ...</span>';

    try {
        const res = await fetch(`index.php?page=products&action=get_locations&variant_id=${item.variant_id}`);
        const locs = await res.json();

        if (!locs || locs.length === 0) {
            locContainer.innerHTML = '<span class="badge bg-danger p-2">Mẫu này hiện không có sẵn trên kệ!</span>';
            return;
        }

        let html = '';
        locs.forEach(l => {
            html += `
            <div class="w-100 d-flex justify-content-between align-items-center bg-black bg-opacity-25 p-2 rounded mb-2 border border-secondary">
                <span class="text-white fw-bold small">
                    Kệ ${l.shelf_name} - Ô ${l.slot_code} <span class="text-warning">(Tồn: ${l.qty_in_slot})</span>
                </span>
                <input type="number" class="form-control form-control-sm text-center pick-input fw-bold bg-dark text-info border-info" 
                       style="width: 80px;" data-shelf-id="${l.shelf_id}" data-slot="${l.slot_code}"
                       min="0" max="${l.qty_in_slot}" value="0" oninput="validatePickTotal()">
            </div>`;
        });
        locContainer.innerHTML = html;
        validatePickTotal(); 
    } catch (e) {
        locContainer.innerHTML = '<span class="text-danger small">Lỗi truy xuất sơ đồ kho.</span>';
    }
}

function validatePickTotal() {
    let totalPicked = 0;
    const inputs = document.querySelectorAll('.pick-input');
    
    inputs.forEach(input => {
        let max = parseInt(input.max) || 0;
        let val = parseInt(input.value) || 0;
        
        if (val < 0) { val = 0; input.value = 0; }
        if (val > max) { val = max; input.value = max; }
        
        totalPicked += val;
    });

    const totalInput = document.getElementById('pick_qty_input');
    totalInput.value = totalPicked;
    
    const btnConfirm = document.querySelector('#export_right_action button');

    let warningMsg = document.getElementById('pick_warning_msg');
    if (!warningMsg) {
        warningMsg = document.createElement('div');
        warningMsg.id = 'pick_warning_msg';
        warningMsg.className = 'small fw-bold mt-2 text-center mb-2';
        btnConfirm.parentNode.insertBefore(warningMsg, btnConfirm);
    }

    if (totalPicked === currentRequiredQty) {
        totalInput.classList.replace('text-white', 'text-success');
        btnConfirm.disabled = false;
        warningMsg.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Đã chọn đủ số lượng yêu cầu!</span>';
    } 
    else if (totalPicked < currentRequiredQty) {
        totalInput.classList.replace('text-success', 'text-white');
        btnConfirm.disabled = true;
        let thieu = currentRequiredQty - totalPicked;
        warningMsg.innerHTML = `<span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> Bạn đang lấy THIẾU ${thieu} đôi. Vui lòng chọn thêm ở kệ khác!</span>`;
    } 
    else {
        totalInput.classList.replace('text-success', 'text-white');
        btnConfirm.disabled = true;
        let du = totalPicked - currentRequiredQty;
        warningMsg.innerHTML = `<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Bạn đang chọn DƯ ${du} đôi so với phiếu. Vui lòng giảm bớt!</span>`;
    }
}

async function confirmPickItem() {
    const detailId = document.getElementById('pick_detail_id').value;
    const totalPicked = parseInt(document.getElementById('pick_qty_input').value);

    let pickedLocations = [];
    document.querySelectorAll('.pick-input').forEach(input => {
        let val = parseInt(input.value) || 0;
        if (val > 0) {
            pickedLocations.push({
                shelf_id: input.getAttribute('data-shelf-id'),
                slot_code: input.getAttribute('data-slot'),
                qty: val
            });
        }
    });

    const btn = document.querySelector('#export_right_action button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>ĐANG LƯU...';

    try {
        const fd = new FormData();
        fd.append('detail_id', detailId);
        fd.append('picked_qty', totalPicked);
        fd.append('picked_locations', JSON.stringify(pickedLocations));

        const res = await fetch('index.php?page=tickets&action=update_export_progress', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();

        if (data.status === 'success') {
            const item = currentTicketItems.find(i => i.detail_id == detailId);
            if (item) item.processed_qty = parseInt(item.processed_qty || 0) + totalPicked;

            renderExportItems(); 
            document.getElementById('export_right_default').classList.remove('d-none');
            document.getElementById('export_right_action').classList.add('d-none');
            document.getElementById('export_right_action').classList.remove('d-flex');
        } else {
            alert("Lỗi: " + data.message);
        }
    } catch (e) {
        alert("Lỗi mạng khi lưu tiến độ.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'XÁC NHẬN ĐÃ LẤY HÀNG NÀY';
    }
}

async function completeExportTicket() {
    if (!confirm("Hệ thống sẽ trừ kho chính thức và hoàn tất phiếu. Bạn chắc chắn chứ?")) return;

    try {
        const res = await fetch(`index.php?page=tickets&action=complete_export&ticket_id=${currentExportTicketId}`, { method: 'POST' });
        const data = await res.json();

        if (data.status === 'success') {
            alert("Xuất kho thành công!");
            window.location.reload();
        } else {
            alert("Lỗi hoàn tất: " + data.message);
        }
    } catch (e) {
        alert("Lỗi hệ thống.");
    }
}