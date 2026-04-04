<?php
// app/controllers/ReportController.php

require_once __DIR__ . '/../models/ReportModel.php';

/**
 * Controller xử lý các yêu cầu liên quan đến báo cáo và thống kê
 * Chịu trách nhiệm điều phối dữ liệu từ Model sang View cho Dashboard và Report
 */
class ReportController
{
    private $reportModel;

    /**
     * Hàm khởi tạo: Kiểm tra quyền truy cập và khởi tạo Model
     */
    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }
        $this->reportModel = new ReportModel();
    }

    /**
     * Chức năng: Xử lý dữ liệu cho trang Dashboard chính
     * Phân quyền: Manager xem được phân tích sâu, Staff xem số liệu tổng quát
     */
    public function index()
    {
        $role = strtoupper($_SESSION['role'] ?? '');

        // Dữ liệu KPIs tổng quát cho mọi đối tượng
        $data = [
            'stats' => $this->reportModel->getGeneralStats(),
        ];

        // Nếu là Manager, lấy thêm dữ liệu phân tích chuyên sâu cho Dashboard
        if ($role === 'MANAGER') {
            // Top 4 sản phẩm bán chạy
            $data['top_selling']   = $this->reportModel->getTopSelling(4);
            // Dữ liệu mật độ hoạt động (Heatmap)
            $data['heatmap']       = $this->reportModel->getHeatmapData();
            // Xu hướng nhập xuất 6 tháng gần nhất
            $data['monthly_trend'] = $this->reportModel->getMonthlyTrend();
        }

        return $data;
    }

    /**
     * Chức năng: Xử lý dữ liệu cho trang "Báo Cáo Chi Tiết"
     * Bao gồm: Lọc theo Ngày/Tuần/Tháng và các bảng biểu phân tích
     */
    public function statistics()
    {
        // Ưu tiên lấy period từ URL
        $period = $_GET['period'] ?? 'day';
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? date('Y-m-d');

        // Nếu không bấm nút Lọc thủ công mà chỉ chọn Combobox
        if (!isset($_GET['start_date'])) {
            if ($period == 'week') {
                $start_date = date('Y-m-d', strtotime('-7 days'));
            } elseif ($period == 'month') {
                $start_date = date('Y-m-d', strtotime('-30 days'));
            } else {
                $start_date = date('Y-m-d');
            }
        }

        return [
            'all_stats'     => $this->reportModel->getGeneralStats($start_date, $end_date),
            'top_5_days'    => $this->reportModel->getTop5BusiestDays(),
            'product_flow'  => $this->reportModel->getTop5ProductFlow(),
            'variant_flow'  => $this->reportModel->getVariantFlowDetail(),
            'inventory'     => $this->reportModel->getDetailedInventory(),
            'brand_dist'    => $this->reportModel->getBrandDistribution(),
            'staff_perf'    => $this->reportModel->getStaffPerformance(),
            'summary_data'  => $this->reportModel->getActivitySummaryByRange($start_date, $end_date),
            'filter'        => ['start' => $start_date, 'end' => $end_date, 'period' => $period]
        ];
    }

    public function getReportDetailsAjax()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $date = $_GET['date'] ?? null;
        // Nếu bồ muốn xem toàn bộ ngày, productId sẽ null
        $productId = $_GET['product_id'] ?? null;
        $details = $this->reportModel->getDetailsByDate($date, $productId);
        echo json_encode($details ?: []);
        exit;
    }

    /**
     * Chức năng: Điều hướng bộ lọc ngày
     */
    public function filterByDate($start, $end)
    {
        header("Location: index.php?page=report&start_date=$start&end_date=$end");
        exit;
    }
}
