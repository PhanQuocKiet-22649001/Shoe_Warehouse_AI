<?php
// app/controllers/WarehouseController.php
require_once __DIR__ . '/../models/WarehouseModel.php';

class WarehouseController
{
    private $warehouseModel;

    public function __construct()
    {
        $this->warehouseModel = new WarehouseModel();
    }

    public function index()
    {
        return $this->getWarehouseMapData();
    }

    // bảng đồ kho
    /**
     * Render HTML Sơ đồ kho mini (Dùng cho Form Nhập/Xuất AI qua AJAX)
     */
    public function getMiniWarehouseMap()
    {
        if (ob_get_length()) ob_clean();

        // CHUẨN MVC: Không gọi pg_query ở đây, uỷ quyền hoàn toàn cho Model
        $shelvesList = $this->warehouseModel->getAllShelves();

        ob_start();
        echo '<div class="d-flex flex-column gap-3">';

        foreach ($shelvesList as $shelf) {
            $shelfName = $shelf['shelf_name'];
            $layout = json_decode($shelf['layout'], true) ?: [];
            $slotMax = (int)$shelf['max_capacity_per_slot'];

            echo "<div id='mini_shelf_{$shelfName}' class='p-3 rounded-2' style='background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1);'>";
            echo "<h6 class='text-info fw-bold mb-3 text-center' style='letter-spacing: 1px;'>KỆ {$shelfName}</h6>";

            $tierKeys = array_keys($layout);
            rsort($tierKeys);
            $slotCount = count(current($layout) ?: [1, 2, 3, 4, 5, 6]); // Tự đếm số ô của 1 tầng để chia cột Grid

            echo '<div style="display: grid; grid-template-columns: 40px repeat(' . $slotCount . ', 1fr); gap: 6px; align-items: center;">';

            foreach ($tierKeys as $tier) {
                echo "<div class='text-white-50 fw-bold text-end pe-2' style='font-size: 12px;'>T{$tier}</div>";

                $slots = $layout[$tier] ?? [];
                $slotKeys = array_keys($slots);
                sort($slotKeys);

                foreach ($slotKeys as $slotKey) {
                    $slotCode = "{$shelfName}{$tier}-{$slotKey}";
                    $shoesInSlot = $slots[$slotKey] ?? [];
                    $occupancy = count($shoesInSlot);

                    // Tính phần trăm đổ màu an toàn (chống lỗi chia cho 0)
                    $fillPercent = ($slotMax > 0) ? ($occupancy / $slotMax) * 100 : 0;

                    echo "
                        <div class='shelf-cell mini-cell' 
                             style='height: 45px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.5); cursor: pointer; position: relative; overflow: hidden; background: linear-gradient(to top, rgba(255,255,255,0.95) {$fillPercent}%, rgba(0,0,0,0.6) {$fillPercent}%); display: flex; align-items: center; justify-content: center; transition: 0.2s;'
                             data-code='{$slotCode}' 
                             data-occupancy='{$occupancy}'
                             title='{$slotCode}'>
                             <span style='mix-blend-mode: difference; color: white; font-weight: bold; font-size: 13px; z-index: 2;'>{$occupancy}/{$slotMax}</span>
                        </div>
                    ";
                }
            }
            echo '</div></div>';
        }
        echo '</div>';
        echo ob_get_clean();
        exit;
    }

    // Tính toán sức chứa và đếm hãng cho Dashboard

    // Tính toán sức chứa và đếm hãng cho Dashboard
    public function getWarehouseMapData()
    {
        $rawShelves = $this->warehouseModel->getAllShelves();
        $brandMap = $this->warehouseModel->getVariantBrandMap();

        // BỔ SUNG: Lấy dictionary để truyền qua View cho Popover
        $variantDict = $this->warehouseModel->getVariantDict();

        $totalLoad = 0;
        $totalCap = 0;
        $processedShelves = [];

        foreach ($rawShelves as $shelf) {
            $layout = json_decode($shelf['layout'], true) ?: [];
            $slotMax = (int)$shelf['max_capacity_per_slot'];
            $shelfLoad = 0;
            $shelfCap = 0;
            $brandCounts = [];

            foreach ($layout as $tier => $slots) {
                foreach ($slots as $arr) {
                    $shelfCap += $slotMax;
                    $shelfLoad += count($arr);
                    foreach ($arr as $vid) {
                        $bName = $brandMap[$vid] ?? 'Khác';
                        $brandCounts[$bName] = ($brandCounts[$bName] ?? 0) + 1;
                    }
                }
            }
            $processedShelves[] = [
                'shelf_name' => $shelf['shelf_name'],
                'layout' => $layout,
                'slot_max' => $slotMax,
                'shelf_max_capacity' => $shelfCap,
                'current_load' => $shelfLoad,
                'brand_counts' => $brandCounts
            ];
            $totalLoad += $shelfLoad;
            $totalCap += $shelfCap;
        }

        return [
            'total_load' => $totalLoad,
            'total_capacity' => $totalCap,
            'processedShelves' => $processedShelves,
            'variantDict' => $variantDict // BỔ SUNG: Truyền biến này ra View
        ];
    }

    /**
     * API AJAX: Tìm vị trí giày để nhấp nháy trên bản đồ
     */
    public function ajaxSearchMap()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        if (empty($keyword)) {
            echo json_encode([]);
            exit;
        }

        // 1. Gọi đúng hàm bồ đang có trong Model: searchVariantsRaw
        $variants = $this->warehouseModel->searchVariantsRaw($keyword);

        // 2. Lấy danh sách kệ thô để quét tọa độ
        $rawShelves = $this->warehouseModel->getAllShelves();

        $results = [];

        foreach ($variants as $v) {
            $vid = (int)$v['variant_id']; // ID giày cần tìm
            $cells = [];

            // Quét qua từng kệ để tìm xem ID này nằm ở ô nào
            foreach ($rawShelves as $s) {
                $layout = json_decode($s['layout'], true) ?: [];
                foreach ($layout as $tier => $slots) {
                    foreach ($slots as $slotKey => $arr) {
                        // Ép kiểu các ID trong ô kệ sang số nguyên để in_array chạy chuẩn
                        $arrInt = array_map('intval', $arr);
                        if (in_array($vid, $arrInt)) {
                            // Nếu tìm thấy, nạp tọa độ vào (Ví dụ: A1-01)
                            $cells[] = "{$s['shelf_name']}{$tier}-{$slotKey}";
                        }
                    }
                }
            }

            // Chỉ trả về kết quả nếu đôi giày đó thực sự có trên kệ
            if (!empty($cells)) {
                $results[] = [
                    'name'  => $v['product_name'],
                    'brand' => $v['brand'],
                    'size'  => $v['size'],
                    'color' => $v['color'],
                    'image' => 'assets/img_product/' . $v['product_image'],
                    'cells' => array_unique($cells) // Tọa độ các ô chứa giày này
                ];
            }
        }

        // Trả về top 5 kết quả gợi ý
        echo json_encode(array_slice($results, 0, 5));
        exit;
    }
}
