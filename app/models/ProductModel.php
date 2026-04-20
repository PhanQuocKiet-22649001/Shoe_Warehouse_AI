<?php
// app/models/ProductModel.php

class ProductModel
{
    private $conn;

    /**
     * Khởi tạo kết nối CSDL toàn cục
     */
    public function __construct()
    {
        $this->conn = getConnection();
    }

    public function getConnection()
    {
        return $this->conn;
    }
    /**
     * Lấy danh sách sản phẩm theo ID Hãng (Loại trừ sản phẩm đã xóa)
     */
    public function getByCategory($category_id)
    {
        $sql = "SELECT product_id, category_id, product_name, product_image, status, is_deleted 
                FROM products 
                WHERE category_id = $1 AND is_deleted = false 
                ORDER BY product_id DESC";
        $result = pg_query_params($this->conn, $sql, [$category_id]);
        return $result ? pg_fetch_all($result) : [];
    }

    /**
     * Lấy tên hãng để hiển thị tiêu đề UI
     */
    public function getCategoryName($category_id)
    {
        $sql = "SELECT category_name FROM categories WHERE category_id = $1";
        $result = pg_query_params($this->conn, $sql, [$category_id]);
        $row = pg_fetch_assoc($result);
        return $row ? $row['category_name'] : 'Sản phẩm';
    }

    /**
     * Xóa mềm sản phẩm
     */
    public function delete($product_id)
    {
        $sql = "UPDATE products SET is_deleted = true WHERE product_id = $1";
        return pg_query_params($this->conn, $sql, [$product_id]);
    }

    /**
     * Cập nhật trạng thái kinh doanh của 1 sản phẩm
     */
    public function updateStatus($product_id, $new_status)
    {
        $sql = "UPDATE products SET status = $1 WHERE product_id = $2";
        return pg_query_params($this->conn, $sql, [$new_status, $product_id]);
    }

    /**
     * Cập nhật trạng thái hàng loạt theo Hãng
     */
    public function updateStatusByCategory($category_id, $new_status)
    {
        $sql = "UPDATE products SET status = $1 WHERE category_id = $2";
        return pg_query_params($this->conn, $sql, [$new_status, $category_id]);
    }

    /**
     * Lấy danh sách biến thể (Size, Màu, Tồn kho) thuộc về 1 sản phẩm
     */
    public function getVariantsByProductId($product_id)
    {
        $sql = "SELECT v.variant_id, v.sku, v.size, v.color, v.stock, v.status as variant_status,
                   p.product_name, p.product_image
            FROM product_variants v
            JOIN products p ON v.product_id = p.product_id
            WHERE v.product_id = $1 AND v.is_deleted = false AND v.stock > 0
            ORDER BY v.size ASC";
        $result = pg_query_params($this->conn, $sql, [$product_id]);
        return $result ? pg_fetch_all($result) : [];
    }

    /**
     * Tìm kiếm sản phẩm linh hoạt (Tên, SKU, Size, Màu)
     */
    public function searchProducts($keyword)
    {
        $keyword = '%' . $keyword . '%';
        $sql = "SELECT DISTINCT p.product_id, p.product_name, p.product_image, p.category_id 
            FROM products p
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            WHERE (p.product_name ILIKE $1 
               OR v.sku ILIKE $1 
               OR v.size ILIKE $1 
               OR v.color ILIKE $1)
               AND p.is_deleted = false
            LIMIT 8";
        $result = pg_query_params($this->conn, $sql, [$keyword]);
        return $result ? pg_fetch_all($result) : [];
    }

    /**
     * Tạo bản ghi Sản phẩm Gốc (Cha)
     */
    public function create($name, $category_id, $image, $user_id)
    {
        $sql = "INSERT INTO products (product_name, category_id, product_image, created_by, status, is_deleted, created_at) 
            VALUES ($1, $2, $3, $4, 't', false, NOW()) 
            RETURNING product_id";
        $result = pg_query_params($this->conn, $sql, [$name, (int)$category_id, $image, (int)$user_id]);
        if ($result) {
            $row = pg_fetch_assoc($result);
            return $row['product_id'];
        }
        return false;
    }

    /**
     * Tạo bản ghi Biến thể (Con)
     */
    public function createVariant($product_id, $size, $color, $stock, $sku)
    {
        $sql = "INSERT INTO product_variants (product_id, size, color, stock, sku) 
            VALUES ($1, $2, $3, $4, $5)";
        return pg_query_params($this->conn, $sql, [(int)$product_id, (int)$size, $color, (int)$stock, $sku]);
    }

    /**
     * Đối soát văn bản: Tìm sản phẩm dựa theo Tên và Hãng
     */
    public function findExistingProduct($brandName, $modelName)
    {
        $brandName = trim($brandName);
        $modelName = trim($modelName);
        $sql = "SELECT p.product_id, p.product_name, c.category_id, c.category_name
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE (REPLACE(p.product_name, '\"', '') ILIKE $1) 
            AND c.category_name ILIKE $2 
            AND p.is_deleted = false 
            LIMIT 1";
        $cleanModelName = str_replace('"', '', $modelName);
        $res = pg_query_params($this->conn, $sql, ["%$cleanModelName%", "%$brandName%"]);
        return $res ? pg_fetch_assoc($res) : null;
    }

    /**
     * Kiểm tra tính duy nhất của Màu sắc trong 1 sản phẩm
     */
    public function checkColorExists($product_id, $color)
    {
        $sql = "SELECT color FROM product_variants 
            WHERE product_id = $1 AND color ILIKE $2 AND is_deleted = false LIMIT 1";
        $res = pg_query_params($this->conn, $sql, [$product_id, $color]);
        return (bool)pg_fetch_assoc($res);
    }

    /**
     * Đối soát Biến thể: Tìm chính xác ID, Size, Màu
     */
    public function findVariant($product_id, $size, $color)
    {
        $sql = "SELECT variant_id, stock FROM product_variants 
            WHERE product_id = $1 AND size = $2 AND color ILIKE $3 AND is_deleted = false LIMIT 1";
        $res = pg_query_params($this->conn, $sql, [(int)$product_id, (int)$size, $color]);
        return $res ? pg_fetch_assoc($res) : null;
    }

    /**
     * Cộng dồn số lượng tồn kho
     */
    public function addStock($variant_id, $quantity)
    {
        $sql = "UPDATE product_variants SET stock = stock + $1 WHERE variant_id = $2";
        return pg_query_params($this->conn, $sql, [(int)$quantity, (int)$variant_id]);
    }

    /**
     * Lấy mảng màu sắc hiện có của sản phẩm để hiện Combobox AI
     */
    public function getColorsByProduct($product_id)
    {
        $sql = "SELECT DISTINCT color FROM product_variants 
            WHERE product_id = $1 AND is_deleted = false 
            ORDER BY color ASC";
        $result = pg_query_params($this->conn, $sql, [(int)$product_id]);
        return $result ? pg_fetch_all($result) : [];
    }

    /**
     * Lưu trữ "ADN hình ảnh" (Mảng Vector 512 chiều) vào CSDL bằng pgvector
     */
    public function updateImageEmbedding($product_id, $vectorArray)
    {
        // pgvector yêu cầu chuỗi có dạng: "[0.1, 0.2, 0.3...]"
        $vectorString = '[' . implode(',', $vectorArray) . ']';

        $sql = "UPDATE products SET image_embedding = $1 WHERE product_id = $2";

        // Thực thi query với tham số an toàn
        $result = pg_query_params($this->conn, $sql, [$vectorString, (int)$product_id]);

        return $result ? true : false;
    }

    /**
     * Tìm kiếm hình ảnh bằng AI: Hỗ trợ kịch bản đối soát thông minh
     * Lấy danh sách các sản phẩm có độ tương đồng >= 80%
     */
    public function findTopMatchesByAI($vectorArray, $limit = 3)
    {
        // Định dạng lại mảng Vector cho PostgreSQL
        $vectorString = '[' . implode(',', $vectorArray) . ']';

        // SQL: Lấy thông tin sản phẩm + hãng + tính toán Similarity Score
        // (1 - Distance) = Similarity. Distance càng nhỏ, Similarity càng cao.
        $sql = "SELECT p.product_id, p.product_name, p.product_image, 
                       c.category_id, c.category_name as brand,
                       (1 - (p.image_embedding <=> $1)) AS similarity_score
                FROM products p
                JOIN categories c ON p.category_id = c.category_id
                WHERE p.is_deleted = false 
                  AND p.image_embedding IS NOT NULL
                  AND (1 - (p.image_embedding <=> $1)) >= 0.80 -- Chỉ lấy những mẫu giống trên 80%
                ORDER BY similarity_score DESC 
                LIMIT $2";

        $result = pg_query_params($this->conn, $sql, [$vectorString, (int)$limit]);

        return $result ? pg_fetch_all($result) : [];
    }


    /**
     * Trừ số lượng tồn kho (Xuất kho)
     */
    public function removeStock($variant_id, $quantity)
    {
        // Chỉ trừ nếu số lượng xuất <= số lượng tồn
        $sql = "UPDATE product_variants SET stock = stock - $1 
            WHERE variant_id = $2 AND stock >= $1";
        return pg_query_params($this->conn, $sql, [(int)$quantity, (int)$variant_id]);
    }


    /**
     * Ghi lại lịch sử giao dịch (Nhập/Xuất)
     */
    // Chú ý tham số thứ 5 là $createdAt
    public function logTransaction($type, $variant_id, $quantity, $user_id, $createdAt = null)
    {
        // Nếu có truyền $createdAt từ Google sang thì dùng $1, không thì dùng NOW()
        $sql = "INSERT INTO transactions (transaction_type, variant_id, quantity, user_id, created_at)
            VALUES ($1, $2, $3, $4, " . ($createdAt ? "$5" : "NOW()") . ")";

        $params = [$type, $variant_id, $quantity, $user_id];
        if ($createdAt) {
            $params[] = $createdAt;
        }

        return pg_query_params($this->conn, $sql, $params);
    }


    /**
     * Cập nhật trạng thái Bật/Tắt của biến thể (Status)
     */
    public function updateVariantStatus($variantId, $status)
    {
        // PostgreSQL hiểu chuỗi 'true' hoặc 'false' cho kiểu boolean
        $sql = "UPDATE product_variants 
                SET status = $1 
                WHERE variant_id = $2";

        $result = pg_query_params($this->conn, $sql, [$status, (int)$variantId]);
        return $result ? true : false;
    }

    /**
     * Xóa mềm biến thể (Đưa vào thùng rác)
     */
    public function softDeleteVariant($variantId)
    {
        // Xóa mềm: is_deleted = true và tắt luôn status = false
        $sql = "UPDATE product_variants 
                SET is_deleted = true, status = false 
                WHERE variant_id = $1";

        $result = pg_query_params($this->conn, $sql, [(int)$variantId]);
        return $result ? true : false;
    }








    // =========================================================================
    // THUẬT TOÁN THÁC NƯỚC (WATERFALL PUTAWAY) & LƯU JSONB
    // =========================================================================

    // 1. Hàm tính điểm độ Hot
    private function getVelocityRank($categoryId) {
        if (!$categoryId) return 'COLD';
        $sql = "WITH CategoryVelocity AS (
                    SELECT c.category_id, SUM(t.quantity) as total_sold
                    FROM transactions t JOIN product_variants pv ON t.variant_id = pv.variant_id
                    JOIN products p ON pv.product_id = p.product_id JOIN categories c ON p.category_id = c.category_id
                    WHERE t.transaction_type = 'EXPORT' AND t.created_at >= CURRENT_DATE - INTERVAL '30 days'
                    GROUP BY c.category_id
                )
                SELECT rank() OVER (ORDER BY total_sold DESC) as rank FROM CategoryVelocity WHERE category_id = $1";
        $res = pg_query_params($this->conn, $sql, [$categoryId]);
        $row = $res ? pg_fetch_assoc($res) : null;
        
        if (!$row) return 'COLD';
        if ($row['rank'] <= 2) return 'SUPER_HOT';
        if ($row['rank'] <= 5) return 'HOT';
        return 'MEDIUM';
    }

    // 2. Hàm Dry Run: Quét kho và trả về mảng các ô gợi ý
    public function findSuggestedPutawaySlots($productId, $categoryId, $variantId, $qtyNeeded) {
        $velocityRank = $this->getVelocityRank($categoryId);
        $sql = "SELECT shelf_name, layout FROM shelves";
        $shelves = pg_fetch_all(pg_query($this->conn, $sql)) ?: [];

        $scoredSlots = [];
        foreach ($shelves as $shelf) {
            $shelfName = $shelf['shelf_name'];
            $layout = json_decode($shelf['layout'], true) ?: [];
            
            foreach ($layout as $tier => $slots) {
                foreach ($slots as $slotKey => $shoesArray) {
                    $occupancy = count($shoesArray);
                    if ($occupancy >= 4) continue; // Bỏ qua ô đầy

                    $score = 0;
                    if ($occupancy > 0) {
                        // Nếu ô có hàng, chỉ cộng điểm nếu nó chứa chính xác Variant ID này
                        if ($variantId && in_array($variantId, $shoesArray)) $score += 1000;
                        else continue; // Ô đang chứa hàng khác -> Bỏ qua
                    } else {
                        // Ô trống: Chấm điểm theo độ Hot
                        if ($velocityRank == 'SUPER_HOT' && in_array($shelfName, ['A'])) $score += 300;
                        if ($velocityRank == 'HOT' && in_array($shelfName, ['B', 'C'])) $score += 200;
                        if ($velocityRank == 'COLD' && in_array($shelfName, ['E', 'F'])) $score += 300;
                        // Chấm điểm theo Tầng
                        if (in_array($velocityRank, ['SUPER_HOT', 'HOT']) && in_array($tier, ['2', '3'])) $score += 100;
                        if ($velocityRank == 'COLD' && in_array($tier, ['1', '4'])) $score += 50;
                    }
                    $scoredSlots[] = ['code' => "{$shelfName}{$tier}-{$slotKey}", 'score' => $score];
                }
            }
        }

        // Sắp xếp giảm dần theo điểm
        usort($scoredSlots, function($a, $b) { return $b['score'] <=> $a['score']; });
        
        // Cắt lấy top n ô cần thiết để chứa đủ số lượng
        $suggestions = [];
        for ($i = 0; $i < min(count($scoredSlots), ceil($qtyNeeded / 4)); $i++) {
            $suggestions[] = $scoredSlots[$i]['code'];
        }
        return $suggestions;
    }

    // 3. Hàm lưu mảng vị trí vào JSONB (Dùng khi ấn Lưu)
    public function savePutawayToShelves($putawayDataArray, $variantId) {
        foreach ($putawayDataArray as $item) {
            $loc = $item['location']; // Ví dụ: A1-01
            $qty = intval($item['quantity']);
            
            preg_match('/([A-Z])(\d)-(\d{2})/', $loc, $matches);
            if (count($matches) == 4) {
                $shelfName = $matches[1]; $tier = $matches[2]; $slot = $matches[3];
                
                $sql = "SELECT layout FROM shelves WHERE shelf_name = $1";
                $res = pg_query_params($this->conn, $sql, [$shelfName]);
                if ($row = pg_fetch_assoc($res)) {
                    $layout = json_decode($row['layout'], true);
                    if (!isset($layout[$tier][$slot])) $layout[$tier][$slot] = [];
                    
                    for ($k = 0; $k < $qty; $k++) { array_push($layout[$tier][$slot], (int)$variantId); }
                    
                    $jsonb_str = json_encode($layout);
                    pg_query_params($this->conn, "UPDATE shelves SET layout = $1::jsonb WHERE shelf_name = $2", [$jsonb_str, $shelfName]);
                }
            }
        }
    }





    /**
     * Rút variant_id khỏi kệ. 
     * - Nếu $qtyToRemove là số nguyên: Rút đúng số lượng (Dùng cho Xuất kho)
     * - Nếu $qtyToRemove là null: Quét và rút sạch toàn bộ (Dùng cho Xóa biến thể)
     */
    public function removePutawayFromShelves($variantId, $qtyToRemove = null) {
        $sql = "SELECT shelf_name, layout FROM shelves";
        $shelves = pg_fetch_all(pg_query($this->conn, $sql)) ?: [];

        $remainingToRemove = $qtyToRemove;
        $removeAll = ($qtyToRemove === null); // Cờ báo hiệu: Xóa tất cả

        foreach ($shelves as $shelf) {
            // Nếu không phải chế độ xóa tất cả, và đã rút đủ số lượng -> Dừng quét kho
            if (!$removeAll && $remainingToRemove <= 0) break;

            $shelfName = $shelf['shelf_name'];
            $layout = json_decode($shelf['layout'], true);
            $layoutModified = false;

            if ($layout) {
                foreach ($layout as $tier => &$slots) {
                    foreach ($slots as $slotKey => &$shoesArray) {
                        
                        $indexesToDelete = array_keys($shoesArray, (int)$variantId);
                        
                        if (!empty($indexesToDelete)) {
                            foreach ($indexesToDelete as $idx) {
                                if ($removeAll || $remainingToRemove > 0) {
                                    unset($shoesArray[$idx]);
                                    if (!$removeAll) $remainingToRemove--;
                                    $layoutModified = true;
                                } else {
                                    break; // Đủ số lượng thì thoát vòng lặp nhỏ
                                }
                            }
                            // Gắn lại key của mảng để khi lưu JSON không bị biến thành Object
                            $shoesArray = array_values($shoesArray); 
                        }
                    }
                }
            }

            // Lưu kệ này nếu có thay đổi
            if ($layoutModified) {
                $jsonb_str = json_encode($layout);
                pg_query_params($this->conn, "UPDATE shelves SET layout = $1::jsonb WHERE shelf_name = $2", [$jsonb_str, $shelfName]);
            }
        }

        // Trả về true nếu là chế độ xóa sạch, hoặc nếu xuất kho đã rút đủ số lượng
        return $removeAll ? true : ($remainingToRemove == 0);
    }



    // =========================================================================
    // CHỨC NĂNG: ĐIỀU CHUYỂN NỘI BỘ 
    // =========================================================================

    /**
     * Lấy danh sách vị trí kệ của tất cả biến thể thuộc 1 sản phẩm
     */
    /**
     * Quét toàn bộ kho và trả về Map vị trí của tất cả biến thể
     * Định dạng: [variant_id => [['loc' => 'A1-01', 'qty' => 2], ...]]
     */
    public function getAllShelvesLocationsMap()
    {
        $sql = "SELECT shelf_name, layout FROM shelves";
        $shelves = pg_fetch_all(pg_query($this->conn, $sql)) ?: [];
        
        $locationMap = []; 
        
        foreach ($shelves as $shelf) {
            $shelfName = $shelf['shelf_name'];
            $layoutStr = $shelf['layout'];
            
            // Xử lý an toàn 100% chuỗi JSONB từ PostgreSQL (Chống lỗi double-encode)
            $layout = is_string($layoutStr) ? json_decode($layoutStr, true) : $layoutStr;
            if (is_string($layout)) $layout = json_decode($layout, true); 
            
            if (!is_array($layout)) continue;
            
            foreach ($layout as $tier => $slots) {
                if (!is_array($slots)) continue;
                foreach ($slots as $slotKey => $shoesArray) {
                    if (!is_array($shoesArray) || empty($shoesArray)) continue;
                    
                    // Đếm số lượng từng loại giày trong ô
                    $counts = array_count_values($shoesArray);
                    foreach ($counts as $vid => $qty) {
                        if (!isset($locationMap[$vid])) $locationMap[$vid] = [];
                        $locationMap[$vid][] = [
                            'loc' => "{$shelfName}{$tier}-{$slotKey}",
                            'qty' => $qty,
                            'str' => "{$shelfName}{$tier}-{$slotKey} (<b>{$qty}</b>)"
                        ];
                    }
                }
            }
        }
        return $locationMap;
    }

    /**
     * Xử lý Di chuyển hoặc Hoán đổi vị trí (Update JSONB)
     */
    public function movePutawayLocation($variant_id, $from_loc, $to_loc, $qty)
    {
        // 1. Phân tích tọa độ
        preg_match('/([A-Z])(\d)-(\d{2})/', $from_loc, $f_match);
        preg_match('/([A-Z])(\d)-(\d{2})/', $to_loc, $t_match);
        
        if (count($f_match) != 4 || count($t_match) != 4) throw new Exception("Tọa độ không hợp lệ.");
        
        $f_shelf = $f_match[1]; $f_tier = $f_match[2]; $f_slot = $f_match[3];
        $t_shelf = $t_match[1]; $t_tier = $t_match[2]; $t_slot = $t_match[3];

        // 2. Lấy dữ liệu kệ Nguồn và Đích
        $sql = "SELECT shelf_name, layout, max_capacity_per_slot FROM shelves WHERE shelf_name IN ($1, $2)";
        $res = pg_query_params($this->conn, $sql, [$f_shelf, $t_shelf]);
        $shelvesData = pg_fetch_all($res) ?: [];
        
        $shelves = [];
        $maxCap = 4; // Mặc định
        foreach ($shelvesData as $s) {
            $shelves[$s['shelf_name']] = json_decode($s['layout'], true) ?: [];
            $maxCap = (int)$s['max_capacity_per_slot'];
        }

        if (!isset($shelves[$f_shelf]) || !isset($shelves[$t_shelf])) throw new Exception("Không tìm thấy kệ.");

        // 3. Khởi tạo mảng ô nếu chưa có
        if (!isset($shelves[$f_shelf][$f_tier][$f_slot])) $shelves[$f_shelf][$f_tier][$f_slot] = [];
        if (!isset($shelves[$t_shelf][$t_tier][$t_slot])) $shelves[$t_shelf][$t_tier][$t_slot] = [];

        $sourceArr = &$shelves[$f_shelf][$f_tier][$f_slot];
        $destArr = &$shelves[$t_shelf][$t_tier][$t_slot];

        // 4. Đếm số lượng variant cần chuyển trong ô Nguồn
        $sourceCounts = array_count_values($sourceArr);
        $availableQty = $sourceCounts[$variant_id] ?? 0;

        if ($availableQty < $qty) throw new Exception("Ô nguồn $from_loc chỉ có $availableQty đôi, không đủ $qty đôi để chuyển.");

        $destOccupancy = count($destArr);
        $freeSpace = $maxCap - $destOccupancy;

        // ==========================================
        // LOGIC DI CHUYỂN / HOÁN ĐỔI
        // ==========================================
        if ($freeSpace >= $qty) {
            // TRƯỜNG HỢP 1: Đích CÒN ĐỦ CHỖ -> Di chuyển bình thường
            // Rút từ Nguồn
            $removed = 0;
            foreach ($sourceArr as $idx => $vid) {
                if ($vid == $variant_id && $removed < $qty) {
                    unset($sourceArr[$idx]);
                    $removed++;
                }
            }
            $sourceArr = array_values($sourceArr); // Re-index

            // Bơm vào Đích
            for ($i = 0; $i < $qty; $i++) {
                $destArr[] = (int)$variant_id;
            }
        } else {
            // TRƯỜNG HỢP 2: Đích KHÔNG ĐỦ CHỖ -> Kích hoạt chế độ HOÁN ĐỔI (SWAP)
            // Lấy những đôi giày ở ô Đích đang chiếm chỗ (Ưu tiên loại khác loại đang chuyển)
            $shoesToEvict = array_slice($destArr, 0, $qty);
            
            // Rút từ Nguồn
            $removed = 0;
            foreach ($sourceArr as $idx => $vid) {
                if ($vid == $variant_id && $removed < $qty) {
                    unset($sourceArr[$idx]);
                    $removed++;
                }
            }
            $sourceArr = array_values($sourceArr);

            // Rút từ Đích
            array_splice($destArr, 0, $qty);

            // Bơm vào chéo (Hoán đổi)
            for ($i = 0; $i < $qty; $i++) $destArr[] = (int)$variant_id;
            foreach ($shoesToEvict as $evictedVid) $sourceArr[] = (int)$evictedVid;
        }

        // 5. Cập nhật DB
        if ($f_shelf === $t_shelf) {
            // Cùng kệ
            $jsonb = json_encode($shelves[$f_shelf]);
            pg_query_params($this->conn, "UPDATE shelves SET layout = $1::jsonb WHERE shelf_name = $2", [$jsonb, $f_shelf]);
        } else {
            // Khác kệ
            $jsonb_f = json_encode($shelves[$f_shelf]);
            $jsonb_t = json_encode($shelves[$t_shelf]);
            pg_query_params($this->conn, "UPDATE shelves SET layout = $1::jsonb WHERE shelf_name = $2", [$jsonb_f, $f_shelf]);
            pg_query_params($this->conn, "UPDATE shelves SET layout = $1::jsonb WHERE shelf_name = $2", [$jsonb_t, $t_shelf]);
        }
        
        return true;
    }
}
