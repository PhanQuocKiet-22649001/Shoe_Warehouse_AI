<?php
require_once __DIR__ . '/../../config/database.php';

/**
 * Lớp xử lý dữ liệu báo cáo (Report Model)
 * Chịu trách nhiệm truy vấn các thống kê chuyên sâu từ database PostgreSQL
 */
class ReportModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    /**
     * Lấy các con số thống kê tổng quát (KPIs)
     * 1. Tổng tồn kho hiện tại (không lọc ngày)
     * 2. Tổng lượng xuất trong tháng (hoặc theo khoảng ngày)
     * 3. Số lượng mặt hàng sắp hết hàng (tồn < 10)
     */
    public function getGeneralStats($start_date = null, $end_date = null)
    {
        $stats = [];

        // Truy vấn tổng tồn kho thực tế từ bảng biến thể sản phẩm
        $sql_stock = "SELECT SUM(stock) as total FROM product_variants WHERE is_deleted = false";
        $res_stock = pg_query($this->conn, $sql_stock);
        $stats['total_stock'] = pg_fetch_assoc($res_stock)['total'] ?? 0;

        // Truy vấn tổng lượng xuất kho (EXPORT)
        $params = [];
        $sql_export = "SELECT SUM(quantity) as total FROM transactions WHERE transaction_type = 'EXPORT'";

        if ($start_date && $end_date) {
            // Lọc theo khoảng ngày nếu người dùng chọn filter
            $sql_export .= " AND created_at BETWEEN $1 AND $2";
            $res_export = pg_query_params($this->conn, $sql_export, [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        } else {
            // Mặc định lấy dữ liệu của tháng hiện tại
            $sql_export .= " AND date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE)";
            $res_export = pg_query($this->conn, $sql_export);
        }
        $stats['monthly_exports'] = pg_fetch_assoc($res_export)['total'] ?? 0;

        // Đếm số biến thể có lượng tồn thấp hơn 10 (Cảnh báo nhập hàng)
        $sql_shortage = "SELECT COUNT(*) as total FROM product_variants WHERE stock < 10 AND is_deleted = false";
        $stats['shortage_count'] = pg_fetch_assoc(pg_query($this->conn, $sql_shortage))['total'] ?? 0;

        return $stats;
    }

    /**
     * Xác định Top 5 ngày có tổng hoạt động (Nhập + Xuất) cao nhất
     * Dùng để vẽ biểu đồ cột đôi so sánh lưu lượng theo ngày
     */
    public function getTop5BusiestDays()
    {
        $sql = "SELECT DATE(created_at) as work_date, 
                SUM(CASE WHEN transaction_type = 'IMPORT' THEN quantity ELSE 0 END) as total_import,
                SUM(CASE WHEN transaction_type = 'EXPORT' THEN quantity ELSE 0 END) as total_export
                FROM transactions 
                GROUP BY work_date 
                ORDER BY SUM(quantity) DESC 
                LIMIT 5";
        return pg_fetch_all(pg_query($this->conn, $sql)) ?: [];
    }

    /**
     * Lấy Top 5 sản phẩm có tổng lưu lượng giao dịch (Nhập + Xuất) lớn nhất
     * Giúp quản lý biết mẫu giày nào đang luân chuyển sôi nổi nhất
     */
    public function getTop5ProductFlow()
    {
        $sql = "SELECT p.product_name, 
                SUM(CASE WHEN t.transaction_type = 'IMPORT' THEN t.quantity ELSE 0 END) as total_import,
                SUM(CASE WHEN t.transaction_type = 'EXPORT' THEN t.quantity ELSE 0 END) as total_export
                FROM transactions t 
                JOIN product_variants pv ON t.variant_id = pv.variant_id
                JOIN products p ON pv.product_id = p.product_id
                GROUP BY p.product_name 
                ORDER BY (SUM(t.quantity)) DESC 
                LIMIT 5";
        return pg_fetch_all(pg_query($this->conn, $sql)) ?: [];
    }

    /**
     * Lấy chi tiết hoạt động của từng biến thể (Size/Màu)
     * Thống kê xem cụ thể kích cỡ hoặc màu sắc nào của giày được nhập/xuất nhiều nhất
     */
    /**
     * Lấy chi tiết biến thể luân chuyển của ĐÚNG 5 SẢN PHẨM TOP ĐẦU
     */
    public function getVariantFlowDetail()
    {
        // Dùng CTE (WITH) để lấy ra ID của 5 sản phẩm có tổng giao dịch cao nhất trước
        $sql = "WITH TopProducts AS (
                    SELECT p.product_id, SUM(t.quantity) as total_vol
                    FROM transactions t
                    JOIN product_variants pv ON t.variant_id = pv.variant_id
                    JOIN products p ON pv.product_id = p.product_id
                    GROUP BY p.product_id
                    ORDER BY total_vol DESC
                    LIMIT 5
                )
                -- Sau đó JOIN ngược lại để lấy chi tiết từng biến thể của 5 sản phẩm đó
                SELECT p.product_name, pv.size, pv.color, tp.total_vol,
                       SUM(CASE WHEN t.transaction_type = 'IMPORT' THEN t.quantity ELSE 0 END) as imp,
                       SUM(CASE WHEN t.transaction_type = 'EXPORT' THEN t.quantity ELSE 0 END) as exp
                FROM transactions t 
                JOIN product_variants pv ON t.variant_id = pv.variant_id
                JOIN products p ON pv.product_id = p.product_id
                JOIN TopProducts tp ON p.product_id = tp.product_id
                GROUP BY p.product_name, pv.size, pv.color, tp.total_vol
                ORDER BY tp.total_vol DESC, (SUM(t.quantity)) DESC";

        return pg_fetch_all(pg_query($this->conn, $sql)) ?: [];
    }

    /**
     * Truy vấn danh sách tồn kho chi tiết hiện tại
     * Hiển thị bảng kê sản phẩm kèm size, màu và số lượng tồn thực tế trong kho
     */
    public function getDetailedInventory()
    {
        $sql = "SELECT p.product_name, pv.size, pv.color, pv.stock, c.category_name
                FROM product_variants pv 
                JOIN products p ON pv.product_id = p.product_id
                JOIN categories c ON p.category_id = c.category_id
                WHERE pv.is_deleted = false 
                ORDER BY p.product_name ASC, pv.size ASC";
        return pg_fetch_all(pg_query($this->conn, $sql)) ?: [];
    }

    /**
     * Thống kê hiệu suất làm việc của nhân viên (Top 3)
     * Dựa trên số lượng giao dịch (số lần thực hiện) mà nhân viên đó phụ trách
     */
    public function getStaffPerformance()
    {
        $sql = "SELECT u.full_name, COUNT(t.transaction_id) as total_tx 
                FROM transactions t 
                JOIN users u ON t.user_id = u.user_id 
                GROUP BY u.full_name 
                ORDER BY total_tx DESC 
                LIMIT 3";
        return pg_fetch_all(pg_query($this->conn, $sql)) ?: [];
    }

    /**
     * Phân bổ tồn kho theo thương hiệu (Category)
     * Dùng để vẽ biểu đồ tròn (Pie/Doughnut) thể hiện cơ cấu hàng hóa trong kho
     */
    public function getBrandDistribution()
    {
        $sql = "SELECT c.category_name as brand, SUM(pv.stock) as total_stock 
                FROM product_variants pv 
                JOIN products p ON pv.product_id = p.product_id 
                JOIN categories c ON p.category_id = c.category_id 
                GROUP BY c.category_name";
        return pg_fetch_all(pg_query($this->conn, $sql)) ?: [];
    }

    /**
     * Lấy danh sách sản phẩm bán chạy nhất (Top Selling)
     * Thường dùng để hiển thị trên Dashboard chính cho Manager
     * @param int $limit Số lượng sản phẩm muốn lấy (mặc định là 4)
     */
    public function getTopSelling($limit = 4)
    {
        $sql = "SELECT p.product_name, SUM(t.quantity) as total_sold
                FROM transactions t
                JOIN product_variants pv ON t.variant_id = pv.variant_id
                JOIN products p ON pv.product_id = p.product_id
                WHERE t.transaction_type = 'EXPORT'
                GROUP BY p.product_id, p.product_name
                ORDER BY total_sold DESC
                LIMIT $1";
        $result = pg_query_params($this->conn, $sql, [$limit]);
        return pg_fetch_all($result) ?: [];
    }

    /**
     * Lấy dữ liệu mật độ hoạt động (Heatmap)
     * Đếm số lần biến thể sản phẩm được tương tác (Nhập hoặc Xuất)
     * Dùng để xác định vị trí/sản phẩm nào được nhân viên "ghé thăm" nhiều nhất
     */
    public function getHeatmapData()
    {
        $sql = "SELECT variant_id, COUNT(*) as activity_count 
                FROM transactions 
                GROUP BY variant_id 
                ORDER BY activity_count DESC";
        $res = pg_query($this->conn, $sql);
        return pg_fetch_all($res) ?: [];
    }

    /**
     * Lấy xu hướng nhập xuất theo tháng (Monthly Trend)
     * Thống kê tổng lượng Nhập và Xuất trong 6 tháng gần nhất
     * Dùng để vẽ biểu đồ đường (Line Chart) so sánh biến động kho theo thời gian
     */
    public function getMonthlyTrend()
    {
        $sql = "SELECT to_char(created_at, 'Mon') as month_name, 
                       SUM(CASE WHEN transaction_type = 'IMPORT' THEN quantity ELSE 0 END) as total_import, 
                       SUM(CASE WHEN transaction_type = 'EXPORT' THEN quantity ELSE 0 END) as total_export 
                FROM transactions 
                WHERE created_at > CURRENT_DATE - INTERVAL '6 months' 
                GROUP BY month_name, date_trunc('month', created_at) 
                ORDER BY date_trunc('month', created_at) ASC";
        $res = pg_query($this->conn, $sql);
        return pg_fetch_all($res) ?: [];
    }

    /**
     * Lấy tồn kho chi tiết nhưng nhóm theo sản phẩm để hiển thị dạng cây (Tree view)
     */
    public function getDetailedInventoryGrouped()
    {
        $sql = "SELECT p.product_name, pv.size, pv.color, pv.stock, c.category_name
            FROM product_variants pv 
            JOIN products p ON pv.product_id = p.product_id
            JOIN categories c ON p.category_id = c.category_id
            WHERE pv.is_deleted = false 
            ORDER BY p.product_name ASC, pv.size ASC";
        $res = pg_query($this->conn, $sql);
        $data = pg_fetch_all($res) ?: [];

        $grouped = [];
        foreach ($data as $item) {
            $grouped[$item['product_name']]['brand'] = $item['category_name'];
            $grouped[$item['product_name']]['variants'][] = [
                'size' => $item['size'],
                'color' => $item['color'],
                'stock' => $item['stock']
            ];
        }
        return $grouped;
    }
}
