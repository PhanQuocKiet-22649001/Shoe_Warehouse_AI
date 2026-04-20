document.addEventListener('DOMContentLoaded', function () {
    
    // ==========================================
    // 1. CHỨC NĂNG XUẤT KHO (STAFF)
    // ==========================================
    window.confirmExport = function (form) {
        const qty = parseInt(form.quantity.value);
        const stock = parseInt(form.current_stock.value);
        
        if (qty > stock) { 
            alert("Số lượng xuất (" + qty + ") không được lớn hơn tồn kho (" + stock + ")!"); 
            return false; 
        }
        if (qty <= 0) { 
            alert("Số lượng xuất phải lớn hơn 0 nhé!"); 
            return false; 
        }
        
        return confirm("Xác nhận xuất " + qty + " đôi này khỏi kho?");
    };

    // ==========================================
    // 2. CHỨC NĂNG ĐIỀU CHUYỂN NỘI BỘ KHO (MANAGER)
    // ==========================================
    window.currentOpenVid = null;
    window.moveState = { 
        vid: null, 
        sku: null, 
        locs: [], 
        source: null, 
        dest: null, 
        qty: 1, 
        sourceQty: 0 
    };

    // SỔ ROW XUỐNG VÀ LOAD BẢN ĐỒ
    window.toggleMoveMap = function (vid, sku, locsBase64) {
        const mapRow = document.getElementById(`map-row-${vid}`);
        const bsCollapse = new bootstrap.Collapse(document.getElementById(`collapseMap-${vid}`), { toggle: false });

        // Nếu bấm lại chính nó đang mở -> thì đóng lại
        if (window.currentOpenVid === vid) {
            closeMoveMap(vid);
            return;
        }

        // Đóng row đang mở trước đó (nếu có) để giao diện gọn gàng
        if (window.currentOpenVid !== null) {
            closeMoveMap(window.currentOpenVid);
        }

        window.currentOpenVid = vid;
        window.moveState = {
            vid: vid,
            sku: sku,
            locs: JSON.parse(atob(locsBase64)),
            source: null,
            dest: null,
            qty: 1,
            sourceQty: 0
        };

        // Hiện row ẩn và vuốt mở (collapse)
        mapRow.classList.remove('d-none');
        bsCollapse.show();

        const statusDiv = document.getElementById(`moveMapStatus-${vid}`);
        const containerDiv = document.getElementById(`moveMapContainer-${vid}`);
        document.getElementById(`btnConfirmMove-${vid}`).disabled = true;

        statusDiv.innerHTML = "<div class='alert alert-info m-0 border-info py-2'>Bước 1: Vui lòng click chọn <b>Ô NGUỒN</b> đang chứa giày (Các ô đang nhấp nháy đỏ).</div>";
        containerDiv.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border text-primary" role="status"></div> Đang tải bản đồ...</div>';

        // Tận dụng API gọi map
        fetch('index.php?page=products&action=get_mini_heatmap')
            .then(r => r.text())
            .then(html => {
                containerDiv.innerHTML = html;
                setupMapInteractions(vid);
            });
    };

    // ĐÓNG SƠ ĐỒ KHO
    window.closeMoveMap = function (vid) {
        const collapseEl = document.getElementById(`collapseMap-${vid}`);
        if (!collapseEl) return;
        
        const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
        if (bsCollapse) bsCollapse.hide();
        
        setTimeout(() => {
            const mapRow = document.getElementById(`map-row-${vid}`);
            if (mapRow) mapRow.classList.add('d-none');
        }, 300);
        
        if (window.currentOpenVid === vid) window.currentOpenVid = null;
    };

    // TƯƠNG TÁC SƠ ĐỒ ĐƯỢC GIỚI HẠN TRONG DÒNG ĐÓ
    window.setupMapInteractions = function (vid) {
        const container = document.getElementById(`moveMapContainer-${vid}`);
        const cells = container.querySelectorAll('.shelf-cell');
        const validSources = window.moveState.locs.map(l => l.loc);
        const statusDiv = document.getElementById(`moveMapStatus-${vid}`);
        const btnConfirm = document.getElementById(`btnConfirmMove-${vid}`);

        cells.forEach(cell => {
            const code = cell.getAttribute('data-code');
            const occ = parseInt(cell.getAttribute('data-occupancy'));
            const maxCap = parseInt(cell.getAttribute('data-max')) || 4;

            if (validSources.includes(code)) {
                cell.classList.add('blinking-source');
            }

            cell.addEventListener('click', function() {
                // CHƯA CHỌN NGUỒN
                if (!window.moveState.source) {
                    if (!validSources.includes(code)) {
                        alert("Vui lòng chọn ô ĐANG CHỨA GIÀY (các ô đang nhấp nháy)!");
                        return;
                    }
                    window.moveState.source = code;
                    window.moveState.sourceQty = window.moveState.locs.find(l => l.loc === code).qty;

                    cells.forEach(c => c.classList.remove('blinking-source'));
                    this.classList.add('selected-source');
                    
                    statusDiv.innerHTML = `<div class='alert alert-warning m-0 border-warning py-2'>Đã chọn nguồn: <b>${code}</b> (Sẵn ${window.moveState.sourceQty} đôi).<br>Bước 2: Click chọn <b>Ô ĐÍCH</b> muốn chuyển.</div>`;
                } 
                // ĐÃ CÓ NGUỒN, CHỌN ĐÍCH
                else if (!window.moveState.dest) {
                    if (code === window.moveState.source) {
                        window.moveState.source = null;
                        this.classList.remove('selected-source');
                        setupMapInteractions(vid); // reset nháy
                        statusDiv.innerHTML = "<div class='alert alert-info m-0 border-info py-2'>Bước 1: Vui lòng click chọn <b>Ô NGUỒN</b>.</div>";
                        return;
                    }

                    let qty = prompt(`Chuyển từ ${window.moveState.source} sang ${code}.\nNhập số lượng (Tối đa ${window.moveState.sourceQty}):`, 1);
                    qty = parseInt(qty);
                    
                    if (isNaN(qty) || qty <= 0 || qty > window.moveState.sourceQty) {
                        alert("Số lượng không hợp lệ!");
                        return;
                    }

                    window.moveState.dest = code;
                    window.moveState.qty = qty;
                    this.classList.add('selected-dest');

                    let isSwap = (maxCap - occ) < qty;
                    let msg = `Sẽ chuyển <b>${qty} đôi</b> từ <b>${window.moveState.source}</b> sang <b>${window.moveState.dest}</b>.`;
                    
                    if (isSwap) {
                        msg += `<br><span class='text-danger fw-bold'><i class='fas fa-exclamation-triangle'></i> Ô đích đã đầy (max ${maxCap}). Hệ thống sẽ HOÁN ĐỔI hàng đang có ở đích về lại ô nguồn!</span>`;
                    }

                    statusDiv.innerHTML = `<div class='alert alert-success m-0 border-success py-2'>${msg}</div>`;
                    btnConfirm.disabled = false;
                }
            });
        });
    };

    // GỬI LỆNH ĐIỀU CHUYỂN
    window.executeVisualMove = async function (vid) {
        if (!confirm(`Xác nhận thực hiện điều chuyển ${window.moveState.qty} đôi từ ${window.moveState.source} sang ${window.moveState.dest}?`)) return;

        try {
            const response = await fetch('index.php?page=products&action=move_location', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    variant_id: window.moveState.vid, 
                    from_loc: window.moveState.source, 
                    to_loc: window.moveState.dest, 
                    qty: window.moveState.qty 
                })
            });
            const res = await response.json();
            if (res.status === 'success') {
                alert(res.message);
                location.reload();
            } else {
                alert("Lỗi: " + res.message);
            }
        } catch (e) {
            alert("Lỗi kết nối máy chủ.");
        }
    };

    // ==========================================
    // 3. TỰ ĐỘNG BẬT MODAL & HIGHLIGHT THEO URL PARAMS
    // ==========================================
    const urlParams = new URLSearchParams(window.location.search);
    const openModalId = urlParams.get('open_modal');
    const highlightVid = urlParams.get('highlight_vid');

    if (openModalId) {
        // 1. Mở Modal chứa Product ID
        const targetModalEl = document.getElementById(`detailModal${openModalId}`);
        if (targetModalEl) {
            const myModal = new bootstrap.Modal(targetModalEl);
            myModal.show();

            // 2. Chờ Modal mở xong (animation) rồi mới cuộn chuột và nhấp nháy
            targetModalEl.addEventListener('shown.bs.modal', function () {
                if (highlightVid) {
                    const targetRow = document.getElementById(`variant-row-${highlightVid}`);
                    if (targetRow) {
                        // Trượt xuống dòng đó cho người dùng thấy
                        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        // Bật Class nhấp nháy viền vàng
                        targetRow.classList.add('highlight-variant-row');
                    }
                }
            });
        }
        
        // Dọn dẹp URL cho sạch sẽ (Xóa tham số đi để F5 không bị bật Modal lại)
        window.history.replaceState({}, document.title, window.location.pathname + "?page=products&category_id=" + urlParams.get('category_id'));
    }
});
