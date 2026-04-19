    // --- BIẾN TOÀN CỤC ---
    let globalScannedData = [];
    let currentSelectedIndex = 0;
    let selectedFilesArray = [];
    let importMode = 'ai'; // Mặc định là AI


    // BẮT SỰ KIỆN CHUYỂN TAB
    document.querySelectorAll('#importTabs .nav-link').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            // Đổi active
            document.querySelectorAll('#importTabs .nav-link').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            importMode = this.getAttribute('data-mode');

            // Reset toàn bộ UI
            selectedFilesArray = [];
            globalScannedData = [];
            currentSelectedIndex = 0;
            renderPreScanThumbnails();
            document.getElementById('post_scan_area').classList.add('d-none');
            document.getElementById('active_form_container').innerHTML = `
                <div class="text-center text-white-50 py-5 mt-4">
                    <h4 class="mb-3 opacity-50 fw-bold">CHƯA CÓ DỮ LIỆU</h4>
                    <p class="mb-0">Hệ thống đang chờ lệnh. Vui lòng tải tệp để tiếp tục.</p>
                </div>`;

            // Đổi text nút bấm & Gợi ý
            const btn = document.getElementById('btn-scan-batch');
            const note = document.querySelector('.upload-zone p');
            const btnSelect = document.querySelector('.upload-zone button');

            if (importMode === 'manual') {
                btn.innerHTML = '<i class="fas fa-edit me-2"></i>TIẾN HÀNH NHẬP LIỆU';
                btnSelect.innerHTML = 'CHỌN TỆP HÌNH ẢNH (TỐI ĐA 10)';
                note.innerHTML = '*Lưu ý: Chế độ thủ công cho phép nhập tối đa 10 ảnh cùng lúc.';
            } else {
                btn.innerHTML = '<i class="fas fa-magic me-2"></i>BẮT ĐẦU XỬ LÝ AI';
                btnSelect.innerHTML = 'CHỌN TỆP HÌNH ẢNH (TỐI ĐA 3)';
                note.innerHTML = '*Lưu ý: Để chọn nhiều ảnh, vui lòng giữ phím Ctrl và click chọn các ảnh cùng lúc.';
            }
        });
    });


    function previewSelectedImages(input) {
        const newFiles = Array.from(input.files);
        const maxLimit = importMode === 'ai' ? 3 : 10; // AI max 3, Thủ công max 10

        for (let file of newFiles) {
            if (selectedFilesArray.length < maxLimit) {
                selectedFilesArray.push(file);
            } else {
                alert(`Chế độ này chỉ hỗ trợ tối đa ${maxLimit} ảnh cùng lúc!`);
                break;
            }
        }
        input.value = '';
        renderPreScanThumbnails();
    }

    function renderPreScanThumbnails() {
        const container = document.getElementById('pre_scan_preview');
        container.innerHTML = '';
        selectedFilesArray.forEach((file, index) => {
            const fileUrl = URL.createObjectURL(file);
            container.innerHTML += `
                <div class="position-relative  rounded-1 " style="width: 120px; height: 120px;">
                    <img src="${fileUrl}" style="width: 100%; height: 100%; object-fit: contain; background-color: #f8f9fa;">
                    <button type="button" class="btn btn-danger p-0 position-absolute " 
                            style="top: 4px; right: 4px; width: 22px; height: 22px; font-size: 12px; font-weight: bold; border-radius: 2px;"
                            onclick="removeSelectedFile(${index})" title="Loại bỏ ảnh này">X</button>
                </div>`;
        });
    }

    function removeSelectedFile(index) {
        selectedFilesArray.splice(index, 1);
        renderPreScanThumbnails();
    }

    async function executeBatchScan() {
        if (selectedFilesArray.length === 0) return alert("Vui lòng đính kèm tệp hình ảnh để xử lý.");

        const btn = document.getElementById('btn-scan-batch');
        const formContainer = document.getElementById('active_form_container');
        const preScanArea = document.getElementById('pre_scan_preview');

        // NẾU LÀ CHẾ ĐỘ THỦ CÔNG -> BỎ QUA GỌI AI
        if (importMode === 'manual') {
            btn.innerHTML = 'ĐANG TẠO BIỂU MẪU...';
            preScanArea.innerHTML = '';
            document.getElementById('post_scan_area').classList.add('d-none');

            globalScannedData = selectedFilesArray.map(f => ({
                temp_image: "",
                vector: null,
                matches: [],
                similarity: 0,
                status: "manual",
                fileObj: f
            }));

            renderThumbnails();
            loadFormForIndex(0);
            btn.innerHTML = '<i class="fas fa-edit me-2"></i>TIẾN HÀNH NHẬP LIỆU';
            return;
        }

        // --- NẾU LÀ AI ---
        btn.disabled = true;
        btn.innerHTML = 'ĐANG XỬ LÝ...';
        preScanArea.innerHTML = '';
        document.getElementById('post_scan_area').classList.add('d-none');
        formContainer.innerHTML = '<div class="text-center py-5"><p class="fw-bold">Hệ thống đang truy xuất dữ liệu kho...</p></div>';

        const fd = new FormData();
        selectedFilesArray.forEach(f => fd.append('images[]', f));

        try {
            const response = await fetch('index.php?page=products&action=scan-ai', {
                method: 'POST',
                body: fd
            });

            // KỸ THUẬT DEBUG: Lấy text thô từ Server trước khi ép sang JSON
            const rawText = await response.text();

            try {
                // Thử ép sang JSON
                const res = JSON.parse(rawText);
                if (res.status === 'success') {
                    globalScannedData = res.data;
                    renderThumbnails();
                    if (globalScannedData.length > 0) loadFormForIndex(0);
                } else {
                    alert("Lỗi xử lý: " + res.message);
                    formContainer.innerHTML = '<div class="text-center py-5 fw-bold text-danger">Tiến trình thất bại.</div>';
                }
            } catch (parseError) {
                // CHÌA KHÓA Ở ĐÂY: Nếu PHP bị lỗi (dư dấu ngoặc, sai cú pháp), nó in ra HTML
                // JS sẽ in thẳng cục HTML đó ra màn hình để bồ biết đường mà sửa!
                console.error("LỖI PHP TRẢ VỀ:", rawText);
                formContainer.innerHTML = `
                    <div class="p-4 text-danger bg-dark rounded-1 text-start" style="overflow-y: auto; max-height: 400px;">
                        <h6 class="fw-bold text-warning"><i class="fas fa-bug me-2"></i>SERVER PHP BÁO LỖI:</h6>
                        <pre style="white-space: pre-wrap; font-size: 13px; color: #ff6b6b;">${rawText}</pre>
                    </div>`;
            }
        } catch (e) {
            alert("Lỗi mạng! Không thể gửi request đến server.");
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic me-2"></i>BẮT ĐẦU XỬ LÝ AI';
        }
    }

    function renderThumbnails() {
        const postScanArea = document.getElementById('post_scan_area');
        const container = document.getElementById('scanned_thumbnails');

        // NẾU HẾT DỮ LIỆU THÌ HIỆN THÔNG BÁO HOÀN TẤT
        if (globalScannedData.length === 0) {
            postScanArea.classList.add('d-none');
            document.getElementById('active_form_container').innerHTML = `
                <div class="text-center py-5">
                    <h4 class=" fw-bold">HOÀN TẤT!</h4>
                    <p>Đã nhập kho xong toàn bộ sản phẩm.</p>
                    <button class="btn btn-dark btn-sm rounded-1" onclick="window.location.reload()">CẬP NHẬT DANH SÁCH KHO</button>
                </div>`;
            return;
        }

        postScanArea.classList.remove('d-none');
        container.innerHTML = '';
        globalScannedData.forEach((item, index) => {
            let borderClass = (index === currentSelectedIndex) ? '  opacity-100 shadow' : ' opacity-50';
            let localUrl = URL.createObjectURL(selectedFilesArray[index]);
            container.innerHTML += `<img src="${localUrl}" onclick="loadFormForIndex(${index})" class="rounded-1  ${borderClass}" style="width: 100px; height: 100px; object-fit:contain; background-color:#f8f9fa; cursor:pointer; transition: 0.2s;">`;
        });
    }

    // --- HÀM LƯU AJAX (GIẢI QUYẾT VẤN ĐỀ CỦA BẠN) ---
    async function saveProductByAJAX(event, form) {
        event.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        const putawayData = JSON.parse(document.getElementById('putaway_data_input').value);
        const stockInput = parseInt(document.getElementById('input_stock_qty').value);
        let totalAssigned = putawayData.reduce((sum, item) => sum + item.quantity, 0);
        if (totalAssigned < stockInput) {
            alert(`Bạn chưa xếp vị trí! Cần xếp ${stockInput - totalAssigned} đôi nữa.`);
            if (document.getElementById('inline_putaway_area').classList.contains('d-none')) toggleInlinePutaway();
            return;
        }

        formData.append('add_product', '1');

        // KỸ THUẬT QUAN TRỌNG: Nếu nhập thủ công, nhét file ảnh thực tế vào FormData để gửi lên PHP
        if (importMode === 'manual' && globalScannedData[currentSelectedIndex].fileObj) {
            formData.append('manual_image', globalScannedData[currentSelectedIndex].fileObj);
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> ĐANG LƯU...';

        try {
            const response = await fetch('index.php?page=products&action=add', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();

            if (res.status === 'success') {
                globalScannedData.splice(currentSelectedIndex, 1);
                selectedFilesArray.splice(currentSelectedIndex, 1);
                alert("Lưu thành công!");

                if (globalScannedData.length > 0) {
                    currentSelectedIndex = 0;
                    loadFormForIndex(0);
                } else {
                    renderThumbnails(); // Hiện Hoàn tất
                }
            } else {
                alert("Lỗi: " + res.message);
                btn.disabled = false;
                btn.innerHTML = 'XÁC NHẬN LƯU DỮ LIỆU';
            }
        } catch (e) {
            alert("Lỗi hệ thống, vui lòng thử lại.");
            btn.disabled = false;
        }
    }

    // --- GIỮ NGUYÊN TOÀN BỘ LOGIC FORM CŨ CỦA BẠN ---
    function loadFormForIndex(index) {
        currentSelectedIndex = index;
        renderThumbnails();

        const item = globalScannedData[index];
        const container = document.getElementById('active_form_container');
        let localUrl = URL.createObjectURL(selectedFilesArray[index]);

        const vectorStr = (item.vector && Array.isArray(item.vector)) ? JSON.stringify(item.vector) : "";
        const imageName = item.temp_image || ""; // Thủ công thì rỗng

        let alertMessage = "";
        let suggestionHtml = "";
        let defaultBrand = "";
        let defaultName = "";

        // --- KHỞI TẠO BIẾN CHO GIAO DIỆN MÀU SẮC ---
        let colorContainerHtml = `<input type="text" name="color" class="form-control fw-bold rounded-1" placeholder="Nhập màu sắc" required>`;

        // ============================================
        // PHÂN LOẠI: NẾU LÀ THỦ CÔNG HOẶC NẾU LÀ AI
        // ============================================
        if (importMode === 'manual') {
            alertMessage = `<div class="alert alert-glass-blink py-2 rounded-1 mb-3"><strong>NHẬP THỦ CÔNG:</strong> Vui lòng tự khai báo thông tin.</div>`;
        } else {
            const matches = item.matches || [];
            const topMatch = matches.length > 0 ? matches[0] : null;
            const topScore = topMatch ? Math.round(topMatch.similarity_score * 100) : 0;

            if (topScore >= 95) {
                alertMessage = `<div class="alert alert-glass-blink py-2 rounded-1 mb-3"><i class="fas fa-check-circle me-2"></i><strong>AI XÁC THỰC:</strong> Khớp tuyệt đối (${topScore}%).</div>`;
                defaultBrand = topMatch.brand;
                defaultName = topMatch.product_name;

                // NẾU KHỚP 100%, CHUYỂN Ô TEXT THÀNH COMBOBOX CÁC MÀU ĐÃ CÓ
                if (item.colors && item.colors.length > 0) {
                    let opts = `<option value="">-- Chọn màu kho --</option>`;
                    item.colors.forEach(c => {
                        opts += `<option value="${c.color}">${c.color}</option>`;
                    });
                    opts += `<option value="new" class="fw-bold"">+ TẠO MÀU MỚI KHÁC...</option>`;

                    colorContainerHtml = `
                        <select name="color" class="form-select fw-bold rounded-1 shadow-sm" onchange="toggleNewColorInput(this)" required>
                            ${opts}
                        </select>
                        <input type="text" name="new_color" id="input_color_new" class="form-control fw-bold rounded-1 mt-2 d-none" placeholder="Gõ tên màu mới...">
                    `;
                }

            } else if (topScore >= 80) {
                alertMessage = `<div class="alert alert-info py-2 rounded-1 mb-3 bg-transparent border border-info text-info"><i class="fas fa-lightbulb me-2"></i><strong>AI GỢI Ý:</strong> Mẫu gần giống (${topScore}%). Xem bên dưới.</div>`;
                suggestionHtml = `<div class="suggestion-list mb-3 p-2 rounded" style="max-height: 150px; overflow-y: auto;">`;
                matches.forEach((m) => {
                    // CHÚ Ý Ở ĐÂY: Truyền thêm m.product_id vào hàm click
                    suggestionHtml += `<div class="d-flex align-items-center p-2" style="cursor:pointer;" onclick="fillFormFromSuggestion('${m.brand}', '${m.product_name}', ${m.product_id})">
                                           <img src="assets/img_product/${m.product_image}" style="width:40px; height:40px; object-fit:cover;" class="me-2 rounded">
                                           <div style="font-size: 11px;">
                                               <span class="fw-bold d-block">${m.product_name}</span>
                                               <span class="text-muted">${m.brand} - Khớp ${Math.round(m.similarity_score * 100)}%</span>
                                           </div>
                                       </div>`;
                });
                suggestionHtml += `</div>`;
            } else {
                alertMessage = `<div class="alert alert-glass-blink rounded-1 mb-3"><i class="fas fa-plus-circle me-2"></i><strong>MẪU MỚI:</strong> Không tìm thấy dữ liệu cũ.</div>`;
            }
        }

        // Tạo danh sách Hãng
        let categoryOptions = `<option value="">-- Chọn thương hiệu --</option>`;
        if (typeof categoriesList !== 'undefined') {
            categoriesList.forEach(cat => {
                let isSelected = (cat.category_name.toLowerCase() === defaultBrand.toLowerCase()) ? 'selected' : '';
                categoryOptions += `<option value="${cat.category_id}" ${isSelected}>${cat.category_name.toUpperCase()}</option>`;
            });
        }

        // RENDER FORM (Chú ý thẻ div chứa colorContainerHtml)
        container.innerHTML = `
        <form onsubmit="saveProductByAJAX(event, this)">
            ${alertMessage}
            ${suggestionHtml}
            <input type="hidden" name="vector" value='${vectorStr}'>
            <input type="hidden" name="temp_image_name" value="${imageName}">
            
            <div class="row mb-3 align-items-center">
                <div class="col-md-3"><label class="fw-bold small">ẢNH GỐC:</label></div>
                <div class="col-md-9">
                    <img src="${localUrl}" class="rounded-1" style="width: 80px; height: 80px; object-fit:contain; background-color: #f8f9fa;">
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold small mb-1">HÃNG SẢN XUẤT:</label>
                <select id="input_brand" name="category_id" class="form-select rounded-1 shadow-sm fw-bold" required>
                    ${categoryOptions}
                </select>
            </div>

            <div class="mb-3">
                <label class="fw-bold small mb-1">TÊN DÒNG SẢN PHẨM:</label>
                <input type="text" id="input_product_name" name="product_name" class="form-control fw-bold rounded-1 shadow-sm" value="${defaultName}" placeholder="Nhập tên giày" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="fw-bold small mb-1">MÀU SẮC:</label>
                    <div id="color_input_wrapper">
                        ${colorContainerHtml}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="fw-bold small mb-1">SIZE:</label>
                    <input type="number" name="size" class="form-control fw-bold text-center rounded-1" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="fw-bold small mb-1">SỐ LƯỢNG NHẬP KHO & VỊ TRÍ:</label>
                <div class="input-group">
                    <input type="number" id="input_stock_qty" name="stock" class="form-control fw-bold text-center rounded-start-1" value="1" min="1" required>
                    <button type="button" class="btn btn-info fw-bold text-dark px-3 rounded-end-1" onclick="toggleInlinePutaway()">
                        <i class="fas fa-map-marked-alt me-1"></i> CHỌN KỆ
                    </button>
                </div>
            </div>

            <div id="inline_putaway_area" class="d-none mb-4 p-3 rounded-2" style="background: rgba(0,0,0,0.3); border: 1px dashed #0dcaf0;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-info small"><i class="fas fa-location-arrow me-1"></i> BẢN ĐỒ KHO</span>
                    <span class="badge bg-warning text-dark" id="putaway_status_badge">Cần xếp: 0 đôi</span>
                </div>
                
                <div id="inline_heatmap_content" class="mb-2 p-2 bg-dark rounded" style="max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                    </div>

                <div class="bg-dark p-2 rounded border border-secondary small">
                    <span class="text-white-50">Vị trí đã chọn:</span>
                    <div id="putaway_selected_list" class="d-flex flex-wrap gap-1 mt-1">
                        <span class="text-muted">Chưa chọn</span>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="putaway_data" id="putaway_data_input" value="[]">

            <button type="submit" class="btn btn-glass-confirm w-100 fw-bold rounded-1 py-2 shadow-sm">XÁC NHẬN LƯU DỮ LIỆU</button>
        </form>`;
    }

    async function fillFormFromSuggestion(brandName, productName, productId) {
        const selectBrand = document.getElementById('input_brand');
        document.getElementById('input_product_name').value = productName;

        // Chọn Hãng
        for (let i = 0; i < selectBrand.options.length; i++) {
            if (selectBrand.options[i].text.toLowerCase() === brandName.toLowerCase()) {
                selectBrand.selectedIndex = i;
                break;
            }
        }

        // Hiệu ứng Highlight báo hiệu đã chọn
        const inputs = [selectBrand, document.getElementById('input_product_name')];
        inputs.forEach(el => {
            el.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
            setTimeout(() => el.style.backgroundColor = '', 500);
        });

        // --- GỌI API LẤY MÀU SẮC CỦA SẢN PHẨM VỪA CHỌN ---
        const colorWrapper = document.getElementById('color_input_wrapper');
        colorWrapper.innerHTML = '<span class="text-white-50 small"><i class="fas fa-spinner fa-spin"></i> Đang tải màu...</span>';

        try {
            // Chú ý: Đảm bảo URL này gọi đúng hàm getColorsAjax trong Controller của bồ
            const res = await fetch(`index.php?page=products&action=getColorsAjax&product_id=${productId}`);
            const colors = await res.json();

            let opts = `<option value="">-- Chọn màu kho --</option>`;
            if (colors && colors.length > 0) {
                colors.forEach(c => {
                    opts += `<option value="${c.color}">${c.color}</option>`;
                });
            }
            opts += `<option value="new" class="fw-bold" style="color: #00d2ff;">+ TẠO MÀU MỚI KHÁC...</option>`;

            // Render lại ô Select
            colorWrapper.innerHTML = `
                <select name="color" id="input_color_select" class="form-select fw-bold rounded-1 shadow-sm" onchange="toggleNewColorInput(this)" required>
                    ${opts}
                </select>
                <input type="text" name="new_color" id="input_color_new" class="form-control fw-bold rounded-1 mt-2 d-none" placeholder="Gõ tên màu mới...">
            `;

            // Highlight combo màu
            document.getElementById('input_color_select').style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
            setTimeout(() => document.getElementById('input_color_select').style.backgroundColor = '', 500);

        } catch (e) {
            // Lỗi mạng thì trả về ô text bình thường cho người dùng tự gõ
            colorWrapper.innerHTML = '<input type="text" name="color" class="form-control fw-bold rounded-1" placeholder="Màu sắc" required>';
        }
    }

    function toggleNewColorInput(selectObj) {
        const newColorInput = document.getElementById('input_color_new');
        if (selectObj.value === 'new') {
            // Bật ô text lên, ép buộc phải gõ
            newColorInput.classList.remove('d-none');
            newColorInput.setAttribute('required', 'true');
            newColorInput.focus();
        } else {
            // Tắt đi nếu chọn màu có sẵn
            newColorInput.classList.add('d-none');
            newColorInput.removeAttribute('required');
            newColorInput.value = ''; // Xóa rác
        }
    }


    // ==========================================
    // LOGIC XUẤT KHO AI (MULTI & DYNAMIC)
    // ==========================================
    let exportSelectedFilesArray = [];
    let exportGlobalScannedData = [];
    let exportCurrentSelectedIndex = 0;
    let exportCurrentColors = []; // Lưu trữ list màu của sản phẩm đang chọn

    function previewExportImages(input) {
        const newFiles = Array.from(input.files);
        for (let file of newFiles) {
            if (exportSelectedFilesArray.length < 3) {
                exportSelectedFilesArray.push(file);
            } else {
                alert("Chỉ hỗ trợ quét tối đa 3 ảnh xuất kho cùng lúc!");
                break;
            }
        }
        input.value = '';
        renderExportPreScanThumbnails();

        if (exportSelectedFilesArray.length > 0) {
            document.getElementById('btn-export-scan').classList.remove('d-none');
        } else {
            document.getElementById('btn-export-scan').classList.add('d-none');
        }
    }

    function renderExportPreScanThumbnails() {
        const container = document.getElementById('export_pre_scan_preview');
        container.innerHTML = '';
        exportSelectedFilesArray.forEach((file, index) => {
            const fileUrl = URL.createObjectURL(file);
            container.innerHTML += `
                <div class="position-relative rounded-1" style="width: 100px; height: 100px;">
                    <img src="${fileUrl}" style="width: 100%; height: 100%; object-fit: contain; background: rgba(255,255,255,0.1);">
                    <button type="button" class="btn btn-danger p-0 position-absolute" 
                            style="top: 4px; right: 4px; width: 22px; height: 22px; font-size: 12px; font-weight: bold;"
                            onclick="removeExportSelectedFile(${index})">X</button>
                </div>`;
        });
    }

    function removeExportSelectedFile(index) {
        exportSelectedFilesArray.splice(index, 1);
        renderExportPreScanThumbnails();
        if (exportSelectedFilesArray.length === 0) document.getElementById('btn-export-scan').classList.add('d-none');
    }

    async function executeExportScan() {
        if (exportSelectedFilesArray.length === 0) return;

        const btnScan = document.getElementById('btn-export-scan');
        const defaultScreen = document.getElementById('export_default_screen');
        const formArea = document.getElementById('export_ai_form');
        const loading = document.getElementById('export_loading');
        const preScanArea = document.getElementById('export_pre_scan_preview');

        btnScan.disabled = true;
        btnScan.innerHTML = 'ĐANG XỬ LÝ...';
        defaultScreen.classList.add('d-none');
        formArea.classList.add('d-none');
        loading.classList.remove('d-none');
        loading.classList.add('d-flex');
        preScanArea.innerHTML = '';

        const fd = new FormData();
        exportSelectedFilesArray.forEach(f => fd.append('images[]', f));

        try {
            const response = await fetch('index.php?page=products&action=scan-ai', {
                method: 'POST',
                body: fd
            });
            const res = await response.json();

            loading.classList.add('d-none');
            loading.classList.remove('d-flex');

            if (res.status === 'success') {
                exportGlobalScannedData = res.data;
                renderExportPostScanThumbnails();
                if (exportGlobalScannedData.length > 0) loadExportFormForIndex(0);
            } else {
                alert("Lỗi AI: " + res.message);
                defaultScreen.classList.remove('d-none');
            }
        } catch (e) {
            alert("Lỗi kết nối server AI.");
            defaultScreen.classList.remove('d-none');
            loading.classList.add('d-none');
        } finally {
            btnScan.classList.add('d-none');
            btnScan.disabled = false;
            btnScan.innerHTML = 'BẮT ĐẦU ĐỐI SOÁT AI';
        }
    }

    function renderExportPostScanThumbnails() {
        const postScanArea = document.getElementById('export_post_scan_area');
        const container = document.getElementById('export_scanned_thumbnails');

        if (exportGlobalScannedData.length === 0) {
            postScanArea.classList.add('d-none');
            document.getElementById('export_default_screen').classList.remove('d-none');
            document.getElementById('export_default_screen').innerHTML = `
                <div class="text-center py-5">
                    <h4 class="fw-bold text-white">ĐÃ XUẤT XONG!</h4>
                    <p>Hoàn tất danh sách quét xuất kho.</p>
                    <button class="btn btn-glass-confirm fw-bold w-100 mb-4" onclick="window.location.reload()">CẬP NHẬT TRANG</button>
                </div>`;
            document.getElementById('export_ai_form').classList.add('d-none');
            return;
        }

        postScanArea.classList.remove('d-none');
        container.innerHTML = '';
        exportGlobalScannedData.forEach((item, index) => {
            let borderClass = (index === exportCurrentSelectedIndex) ? ' opacity-100 shadow border border-2' : ' opacity-50';
            let localUrl = URL.createObjectURL(exportSelectedFilesArray[index]);
            container.innerHTML += `<img src="${localUrl}" onclick="loadExportFormForIndex(${index})" class="rounded-1 ${borderClass}" style="width: 100px; height: auto; object-fit:contain; cursor:pointer; transition: 0.2s;">`;
        });
    }

    async function loadExportFormForIndex(index) {
        exportCurrentSelectedIndex = index;
        renderExportPostScanThumbnails();

        const item = exportGlobalScannedData[index];
        const formArea = document.getElementById('export_ai_form');
        const alertBox = document.getElementById('export_ai_alert');
        const variantsContainer = document.getElementById('export_variants_container');
        const submitBtn = document.querySelector('#final_export_form button[type="submit"]');
        const addVariantBtn = document.querySelector('button[onclick="addExportVariantRow()"]');

        // --- BƯỚC 1: RESET SẠCH SẼ GIAO DIỆN TRƯỚC KHI NẠP DỮ LIỆU MỚI ---
        variantsContainer.innerHTML = ''; // Xóa sạch các dòng Màu/Size cũ
        document.getElementById('exp_product_id').value = "";
        document.getElementById('exp_product_name').innerText = "Đang tải...";
        document.getElementById('exp_brand_name').innerText = "...";

        // Đảm bảo form luôn hiện để người dùng thấy thông báo
        formArea.classList.remove('d-none');

        // --- BƯỚC 2: KIỂM TRA DỮ LIỆU TỪ AI ---
        if (item.matches && item.matches.length > 0) {
            // TRƯỜNG HỢP: TÌM THẤY SẢN PHẨM
            const topMatch = item.matches[0];

            // Nạp dữ liệu mới
            document.getElementById('exp_product_id').value = topMatch.product_id;
            document.getElementById('exp_product_name').innerText = topMatch.product_name;
            document.getElementById('exp_brand_name').innerText = topMatch.brand + ' - Khớp: ' + item.similarity + '%';

            // Hiện các nút thao tác
            if (submitBtn) submitBtn.style.display = 'block';
            if (addVariantBtn) addVariantBtn.style.display = 'block';

            // Cấu hình Alert Xanh
            alertBox.className = "alert py-2 rounded-1 small mb-3 border-success text-success bg-transparent alert-glass-blink";
            if (item.similarity < 95) {
                alertBox.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i><strong>AI GỢI Ý:</strong> Hãy kiểm tra kỹ tên giày (${item.similarity}%).`;
            } else {
                alertBox.innerHTML = `<i class="fas fa-check-circle me-2"></i><strong>AI XÁC THỰC:</strong> Khớp tuyệt đối (${item.similarity}%).`;
            }

            // Tải màu và size
            await cacheColorsForProduct(topMatch.product_id);
            addExportVariantRow();

        } else {
            // TRƯỜNG HỢP: KHÔNG TÌM THẤY (ẢNH LỖI HOẶC GIÀY MỚI)

            // 1. Ghi đè chữ cũ để tránh "râu ông nọ cắm cằm bà kia"
            document.getElementById('exp_product_name').innerText = "SẢN PHẨM LẠ";
            document.getElementById('exp_brand_name').innerText = "AI không nhận diện được mẫu này trong tổng kho.";

            // 2. Ẩn các nút để đảm bảo nhân viên không bấm nhầm
            if (submitBtn) submitBtn.style.display = 'none';
            if (addVariantBtn) addVariantBtn.style.display = 'none';

            // 3. Cấu hình Alert Đỏ kèm hiệu ứng nháy (alert-glass-blink)
            // Tui đã thêm 'alert-glass-blink' vào cuối chuỗi className
            alertBox.className = "alert py-2 rounded-1 small mb-3 border-danger text-danger bg-transparent alert-glass-blink";

            alertBox.innerHTML = `<i class="fas fa-times-circle me-2"></i><strong>LỖI ĐỐI SOÁT:</strong> Mẫu giày này chưa được khai báo hoặc ảnh quá mờ.`;
        }
    }

    // Tải mảng màu sắc lưu vào biến cục bộ
    async function cacheColorsForProduct(productId) {
        try {
            const res = await fetch(`index.php?page=products&action=getColorsAjax&product_id=${productId}`);
            exportCurrentColors = await res.json();
        } catch (e) {
            exportCurrentColors = [];
        }
    }

    // THÊM DÒNG BIẾN THỂ ĐỘNG
    function addExportVariantRow() {
        const container = document.getElementById('export_variants_container');

        // Tạo HTML cho list màu
        let colorOpts = '<option value="">-- Chọn màu --</option>';
        exportCurrentColors.forEach(c => {
            colorOpts += `<option value="${c.color}">${c.color}</option>`;
        });

        const rowHtml = `
            <div class="row gx-2 mb-3 align-items-end export-variant-row p-2 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                <div class="col-4">
                    <label class="small fw-bold text-white-50 mb-1" style="font-size: 10px; white-space: nowrap;">MÀU SẮC</label>
                    <select name="colors[]" class="form-select form-select-sm bg-dark text-white fw-bold exp-color" required onchange="loadSizesForExportRow(this)">
                        ${colorOpts}
                    </select>
                </div>

                <div class="col-5">
                    <label class="small fw-bold text-white-50 mb-1" style="font-size: 10px; white-space: nowrap;">SIZE KHẢ DỤNG</label>
                    <select name="sizes[]" class="form-select form-select-sm bg-dark text-white fw-bold exp-size" required>
                        <option value="">-- Trống --</option>
                    </select>
                </div>

                <div class="col-2">
                    <label class="small fw-bold text-white-50 mb-1" style="font-size: 10px; white-space: nowrap;">SL XUẤT</label>
                    <input type="number" name="quantities[]" class="form-control form-select-sm bg-dark text-white text-center fw-bold exp-qty" value="1" min="1" required>
                </div>

                <div class="col-1 text-end pb-1">
                    <button type="button" class="btn btn-sm text-white border-0 p-0" onclick="removeExportVariantRow(this)" title="Xóa dòng này">
                        <i class="fas fa-trash" style="color: white;"></i>
                    </button>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', rowHtml);
    }

    function removeExportVariantRow(btn) {
        const row = btn.closest('.export-variant-row');
        // Không cho xóa nếu chỉ còn 1 dòng
        if (document.querySelectorAll('.export-variant-row').length > 1) {
            row.remove();
        } else {
            alert("Phải xuất ít nhất 1 biến thể!");
        }
    }

    // Tải Size cho TỪNG DÒNG độc lập
    async function loadSizesForExportRow(colorSelectObj) {
        const productId = document.getElementById('exp_product_id').value;
        const color = colorSelectObj.value;

        // Tìm cái ô Size nằm cùng dòng với ô Màu vừa đổi
        const row = colorSelectObj.closest('.export-variant-row');
        const sizeSelect = row.querySelector('.exp-size');

        if (!color) {
            sizeSelect.innerHTML = '<option value="">-- Trống --</option>';
            return;
        }

        sizeSelect.innerHTML = '<option value="">Đang tải...</option>';
        try {
            const res = await fetch(`index.php?page=products&action=getSizesAjax&product_id=${productId}&color=${encodeURIComponent(color)}`);
            const variants = await res.json();

            sizeSelect.innerHTML = '<option value="">-- Chọn size --</option>';
            if (variants.length === 0) {
                sizeSelect.innerHTML = '<option value="">(Hết hàng)</option>';
                return;
            }
            variants.forEach(v => {
                sizeSelect.innerHTML += `<option value="${v.size}" data-stock="${v.stock}">Size ${v.size} (Tồn: ${v.stock})</option>`;
            });
        } catch (e) {
            sizeSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        }
    }


    // XUẤT KHO

    async function executeExportStockAI(event) {
        event.preventDefault();

        // Frontend Validate: Duyệt qua tất cả các dòng để check Tồn kho
        const rows = document.querySelectorAll('.export-variant-row');
        for (let i = 0; i < rows.length; i++) {
            const sizeSelect = rows[i].querySelector('.exp-size');
            if (!sizeSelect.value) return alert("Vui lòng chọn Size cho tất cả các dòng!");

            const selectedOption = sizeSelect.options[sizeSelect.selectedIndex];
            const maxStock = parseInt(selectedOption.getAttribute('data-stock'));
            const qtyInput = parseInt(rows[i].querySelector('.exp-qty').value);

            if (qtyInput > maxStock) {
                return alert(`Dòng thứ ${i+1}: Không đủ hàng! Chỉ còn ${maxStock} đôi.`);
            }
        }

        if (!confirm("Xác nhận trừ kho cho tất cả các biến thể vừa chọn?")) return;

        const form = event.target;
        const fd = new FormData(form);
        fd.append('export_stock_ai_multi', '1'); // Gửi cờ báo xử lý mảng

        const btn = form.querySelector('button[type="submit"]');
        const oldText = btn.innerHTML; // Lưu lại chữ gốc "XÁC NHẬN TRỪ KHO..."

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>ĐANG TRỪ KHO...';

        try {
            const response = await fetch('index.php?page=products&action=export-ai', {
                method: 'POST',
                body: fd
            });
            const res = await response.json();

            if (res.status === 'success') {
                // Xóa sản phẩm vừa xuất xong khỏi mảng và render lại
                exportGlobalScannedData.splice(exportCurrentSelectedIndex, 1);
                exportSelectedFilesArray.splice(exportCurrentSelectedIndex, 1);
                alert("Xuất kho thành công!");

                // ==========================================
                // FIX LỖI Ở ĐÂY: Mở khóa nút bấm cho đôi thứ 2
                // ==========================================
                btn.disabled = false;
                btn.innerHTML = oldText;

                if (exportGlobalScannedData.length > 0) {
                    exportCurrentSelectedIndex = 0;
                    loadExportFormForIndex(0); // Nạp đôi thứ 2 lên
                } else {
                    renderExportPostScanThumbnails(); // Hiện màn hình Hoàn Tất
                }
            } else {
                alert("Lỗi: " + res.message);
                btn.disabled = false;
                btn.innerHTML = oldText;
            }
        } catch (e) {
            alert("Lỗi hệ thống khi trừ kho.");
            btn.disabled = false;
            btn.innerHTML = oldText;
        }
    }

    let putawayNeededQty = 0;
    let currentPutawaySelections = {};

    async function toggleInlinePutaway() {
        const area = document.getElementById('inline_putaway_area');
        const inputQty = document.getElementById('input_stock_qty');
        const brand = document.getElementById('input_brand').value;
        const name = document.getElementById('input_product_name').value;

        if (!brand || !name) return alert("Vui lòng nhập Hãng và Tên giày trước khi chọn vị trí!");

        if (!area.classList.contains('d-none')) {
            area.classList.add('d-none');
            return;
        }

        putawayNeededQty = parseInt(inputQty.value);
        if (isNaN(putawayNeededQty) || putawayNeededQty <= 0) return alert("Số lượng không hợp lệ!");

        currentPutawaySelections = {};
        updatePutawayBadge();
        area.classList.remove('d-none');

        const heatmapContent = document.getElementById('inline_heatmap_content');
        heatmapContent.innerHTML = '<div class="text-center text-info my-3"><i class="fas fa-spinner fa-spin me-2"></i> AI đang phân tích dữ liệu...</div>';

        try {
            // TẢI UI SƠ ĐỒ KHO (Bồ có file php vẽ HTML lưới kho thì trỏ URL vào đây, hoặc tui giả lập code HTML lưới kho)
            // Lưu ý: Đảm bảo có route action=get_mini_heatmap trả về cục HTML kệ kho
            const resHtml = await fetch('index.php?page=products&action=get_mini_heatmap');
            heatmapContent.innerHTML = await resHtml.text();

            // GỌI DRY RUN LẤY GỢI Ý
            const formElement = document.getElementById('active_form_container').querySelector('form');
            const formData = new FormData(formElement);
            const resAjax = await fetch('index.php?page=products&action=getPutawaySuggestions', {
                method: 'POST',
                body: formData
            });
            const aiData = await resAjax.json();
            if (aiData.status === 'success' && aiData.suggestions && aiData.suggestions.length > 0) {
                // Biến lưu phần tử ô vuông đầu tiên được gợi ý
                let firstSuggestedCell = null;

                aiData.suggestions.forEach((code, index) => {
                    let cell = heatmapContent.querySelector(`.shelf-cell[data-code="${code}"]`);
                    if (cell) {
                        cell.classList.add('slot-suggested'); // Nháy sáng
                        if (index === 0) firstSuggestedCell = cell; // Lưu lại ô đầu tiên
                    }
                });

                // LOGIC AUTO-SCROLL CỰC MƯỢT
                if (firstSuggestedCell) {
                    // Tìm khung chứa có thanh cuộn (overflow-y: auto)
                    const scrollContainer = document.getElementById('inline_heatmap_content');

                    // Tính toán vị trí cuộn để ô được chọn nằm ngay giữa khung nhìn
                    const cellTop = firstSuggestedCell.offsetTop;
                    const containerHalfHeight = scrollContainer.clientHeight / 2;

                    scrollContainer.scrollTo({
                        top: cellTop - containerHalfHeight,
                        behavior: 'smooth' // Cuộn từ từ cho người dùng kịp nhìn
                    });
                }
            }
            if (aiData.status === 'success' && aiData.suggestions) {
                aiData.suggestions.forEach(code => {
                    let cell = heatmapContent.querySelector(`.shelf-cell[data-code="${code}"]`);
                    if (cell) cell.classList.add('slot-suggested'); // Móc CSS class chớp nháy vô đây
                });
            }

            bindMiniHeatmapClicks();
        } catch (e) {
            heatmapContent.innerHTML = '<div class="text-danger small text-center py-3">Lỗi tải bản đồ kho!</div>';
        }
    }

    function bindMiniHeatmapClicks() {
        document.querySelectorAll('#inline_heatmap_content .shelf-cell').forEach(cell => {
            cell.addEventListener('click', function() {
                if (putawayNeededQty <= 0) return alert("Đã xếp đủ số lượng!");

                let code = this.getAttribute('data-code');
                let occ = parseInt(this.getAttribute('data-occupancy') || 0);
                if (occ >= 4) return alert("Ô này đã đầy!");

                let takeQty = Math.min(putawayNeededQty, 4 - occ);
                putawayNeededQty -= takeQty;
                this.setAttribute('data-occupancy', occ + takeQty);

                if (!currentPutawaySelections[code]) currentPutawaySelections[code] = 0;
                currentPutawaySelections[code] += takeQty;

                // UI Đổi màu xanh dương
                this.classList.remove('slot-suggested');
                this.style.background = '#0dcaf0';
                this.style.color = '#000';
                this.style.borderColor = '#fff';
                updatePutawayBadge();
            });
        });
    }

    function updatePutawayBadge() {
        const badge = document.getElementById('putaway_status_badge');
        const list = document.getElementById('putaway_selected_list');
        const hiddenInput = document.getElementById('putaway_data_input');

        badge.innerText = `Cần xếp: ${putawayNeededQty} đôi`;
        badge.className = putawayNeededQty === 0 ? "badge bg-success" : "badge bg-warning text-dark";

        let html = [],
            jsonArray = [];
        for (const [code, qty] of Object.entries(currentPutawaySelections)) {
            html.push(`<span class="badge bg-info text-dark">${code}: ${qty} đôi</span>`);
            jsonArray.push({
                location: code,
                quantity: qty
            });
        }
        list.innerHTML = html.length > 0 ? html.join('') : '<span class="text-muted">Chưa có vị trí</span>';
        hiddenInput.value = JSON.stringify(jsonArray);
    }
