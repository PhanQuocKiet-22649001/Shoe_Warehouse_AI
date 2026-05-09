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

// 4. SO KHỚP AI VÀ HIGHLIGHT PHIẾU (ĐÃ FIX LỖI AUTO-FILL)
function processAIResult(index) {
    activeScannedIndex = index;
    renderImportPostScan(); 
    
    const aiItem = importScannedData[index];
    const matches = aiItem.matches || [];
    if (matches.length === 0) return alert("AI không nhận diện được mẫu giày này!");

    const topMatch = matches[0]; 

    // BƯỚC 1: LỌC TRƯỚC - Tìm xem mẫu này có trong phiếu không
    let matchedTicketItems = importTicketItems.filter(item => 
        item.brand.toLowerCase() === topMatch.brand.toLowerCase() && 
        item.product_name.toLowerCase() === topMatch.product_name.toLowerCase() &&
        parseInt(item.processed_qty) < parseInt(item.quantity)
    );

    // BƯỚC 2: KIỂM TRA - Nếu không có thì dừng luôn, không điền form
    if (matchedTicketItems.length === 0) {
        alert("Sản phẩm AI nhận diện được KHÔNG CÓ trong phiếu nhập này!");
        // Reset form để tránh râu ông nọ chắp cằm bà kia
        document.getElementById('import_form_zone').classList.add('d-none');
        document.getElementById('import_brand').value = '';
        document.getElementById('import_name').value = '';
        return; 
    }

    // BƯỚC 3: HỢP LỆ THÌ MỚI ĐIỀN VÀO FORM
    document.getElementById('import_form_zone').classList.remove('d-none');
    document.getElementById('import_form_zone').classList.add('d-flex');
    document.getElementById('import_temp_image').value = aiItem.temp_image;
    document.getElementById('import_brand').value = topMatch.brand;
    document.getElementById('import_name').value = topMatch.product_name;

    // Reset Dropdowns
    document.getElementById('import_color').innerHTML = '<option value="">-- Chọn màu --</option>';
    document.getElementById('import_size').innerHTML = '<option value="">-- Chọn size --</option>';
    document.getElementById('import_size').disabled = true;

    // BƯỚC 4: HIGHLIGHT TẤT CẢ BIẾN THỂ CỦA MẪU NÀY (Sáng vùng rộng)
    // Xóa highlight cũ của các mẫu khác
    document.querySelectorAll('[id^="import_row_"]').forEach(el => {
        el.classList.remove('bg-info', 'bg-opacity-25', 'border-info');
    });

    matchedTicketItems.forEach(item => {
        let row = document.getElementById(`import_row_${item.variant_id}`);
        if(row) {
            row.classList.add('bg-info', 'bg-opacity-25', 'border-info');
        }
    });

    // Nạp màu vào dropdown
    let uniqueColors = [...new Set(matchedTicketItems.map(i => i.color))];
    uniqueColors.forEach(c => {
        document.getElementById('import_color').innerHTML += `<option value="${c}">${c}</option>`;
    });

    window.currentMatchedItems = matchedTicketItems;
}

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


function handleAIResult(resAI) {
    // 1. Kiểm tra xem variant_id này có nằm trong bảng danh sách sản phẩm trên UI không
    // Giả sử mỗi dòng <tr> trong bảng của bồ có attr: data-variant-id
    const rowInTable = $(`tr[data-variant-id="${resAI.variant_id}"]`);

    if (rowInTable.length === 0) {
        // TRƯỜNG HỢP: Ảnh có trong DB nhưng không có trong phiếu này
        toast('Cảnh báo', 'Sản phẩm này không nằm trong danh sách cần nhập của phiếu!', 'error');
        return; // Dừng lại, không thực hiện lưu hay điền gì cả
    }

    // 2. Nếu có trong phiếu, tiến hành gọi AJAX lưu vào bảng tạm
    $.post('index.php?page=tickets&action=updateTempImportAjax', {
        ticket_id: currentTicketId,
        variant_id: resAI.variant_id,
        image_url: resAI.image_path
    }, function(res) {
        let data = JSON.parse(res);
        if(data.success) {
            // Thêm vào mảng quản lý ảnh cục bộ
            scannedList.push({
                variant_id: resAI.variant_id, 
                url: resAI.image_path,
                brand: resAI.brand,
                model: resAI.model,
                color: resAI.color
            });
            renderScannedImages();
            
            // Highlight các dòng khớp Brand/Mẫu/Màu (chưa xét size)
            highlightMatchingRows(resAI.brand, resAI.model, resAI.color);
        }
    });
}

// Hàm 1: Khi vừa quét xong hoặc click vào ảnh (Sáng tất cả dòng cùng mẫu)
function highlightMatchingRows(brand, model, color) {
    // Reset tất cả về trạng thái bình thường
    $('tr.product-row').removeClass('row-match-exact row-dimmed').addClass('row-normal');

    // Tìm các dòng khớp Brand, Model, Color
    $('tr.product-row').each(function() {
        let row = $(this);
        if (row.data('brand') == brand && row.data('model') == model && row.data('color') == color) {
            row.addClass('row-match-exact');
        }
    });
}

// Hàm 2: Khi người dùng chọn một Size cụ thể
function onSizeSelected(selectedVariantId) {
    // Lấy thông tin của variant được chọn để biết nó thuộc mẫu nào
    let targetRow = $(`tr[data-variant-id="${selectedVariantId}"]`);
    let brand = targetRow.data('brand');
    let model = targetRow.data('model');
    let color = targetRow.data('color');

    // Quét lại bảng
    $('tr.product-row').each(function() {
        let row = $(this);
        // Nếu cùng mẫu nhưng KHÁC variant_id (khác size) -> Làm mờ
        if (row.data('brand') == brand && row.data('model') == model && row.data('color') == color) {
            if (row.data('variant-id') == selectedVariantId) {
                row.removeClass('row-dimmed').addClass('row-match-exact');
            } else {
                row.removeClass('row-match-exact').addClass('row-dimmed');
            }
        }
    });
}

// Hàm 3: Khi click lại vào ảnh đã quét (Sáng lại toàn bộ các size của mẫu đó)
$(document).on('click', '.scan-item-wrap img', function() {
    let index = $(this).parent().data('index');
    let item = scannedList[index];
    
    // Gọi lại hàm highlight mẫu (sáng tất cả các size)
    highlightMatchingRows(item.brand, item.model, item.color);
});
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
// BỔ SUNG QUAN TRỌNG: Thêm tham số othersJsonStr vào cuối
async function autoFillExportForm(detailId) {
    // Tìm data trực tiếp từ mảng, an toàn 100%
    const item = currentTicketItems.find(i => i.detail_id == detailId);
    if(!item) return;

    const remainingQty = parseInt(item.quantity) - parseInt(item.processed_qty || 0);
    currentRequiredQty = remainingQty; // Gán để dùng lúc validate

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
    inputQty.readOnly = true; // KHÓA LẠI - BẮT NHẬP TỪ KỆ
    document.getElementById('pick_qty_max').innerText = `/ ${remainingQty}`;

    // Hiển thị biến thể cùng phiếu
    const othersArea = document.getElementById('other_variants_area');
    const othersList = document.getElementById('other_variants_list');
    if (item.other_variants && item.other_variants.length > 0) {
        othersArea.classList.remove('d-none');
        othersList.innerHTML = item.other_variants.map(o => `• Màu ${o.color}, Size ${o.size} (Cần lấy: ${o.quantity})`).join('<br>');
    } else {
        othersArea.classList.add('d-none');
    }

    // GỌI AJAX HIỆN VỊ TRÍ KỆ VÀ TẠO Ô INPUT
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
        validatePickTotal(); // Gọi để vô hiệu hóa nút xác nhận lúc đầu
    } catch (e) {
        locContainer.innerHTML = '<span class="text-danger small">Lỗi truy xuất sơ đồ kho.</span>';
    }
}

// 3. THÊM HÀM MỚI NÀY ĐỂ ÉP ĐỦ SỐ LƯỢNG MỚI MỞ NÚT
function validatePickTotal() {
    let totalPicked = 0;
    const inputs = document.querySelectorAll('.pick-input');
    
    inputs.forEach(input => {
        let max = parseInt(input.max) || 0;
        let val = parseInt(input.value) || 0;
        
        // Chống gõ bậy số âm hoặc lố số tồn vật lý trên kệ đó
        if (val < 0) { val = 0; input.value = 0; }
        if (val > max) { val = max; input.value = max; }
        
        totalPicked += val;
    });

    const totalInput = document.getElementById('pick_qty_input');
    totalInput.value = totalPicked;
    
    const btnConfirm = document.querySelector('#export_right_action button');

    // --- LOGIC TẠO & CẬP NHẬT DÒNG THÔNG BÁO ---
    let warningMsg = document.getElementById('pick_warning_msg');
    
    // Nếu chưa có thẻ thông báo thì tự động tạo và chèn vào trên nút Xác Nhận
    if (!warningMsg) {
        warningMsg = document.createElement('div');
        warningMsg.id = 'pick_warning_msg';
        warningMsg.className = 'small fw-bold mt-2 text-center mb-2';
        btnConfirm.parentNode.insertBefore(warningMsg, btnConfirm);
    }

    // --- KIỂM TRA ĐIỀU KIỆN ĐỂ MỞ NÚT VÀ HIỆN CHỮ ---
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